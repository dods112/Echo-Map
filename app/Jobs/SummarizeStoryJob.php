<?php

namespace App\Jobs;

use App\Models\LocationStory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SummarizeStoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 2;
    public int $timeout = 90;

    public function __construct(public int $storyId) {}

    public function handle(): void
    {
        $story = LocationStory::find($this->storyId);
        if (! $story || $story->status === 'ready') {
            return;
        }

        // ---- HARD PRIVACY RULE ----
        $pins = $story->publicPins()->get(['id', 'title', 'body', 'theme']);

        if ($pins->count() < 3) {
            $story->update(['status' => 'failed', 'error' => 'Not enough public pins to summarize.']);
            return;
        }

        $bullets = $pins->map(function ($p) {
            $t = trim((string) $p->title);
            $b = trim((string) $p->body);
            $line = "- [{$p->theme}] {$t}";
            if ($b !== '') { $line .= ' - ' . mb_substr($b, 0, 180); }
            return $line;
        })->implode("\n");

        $system = 'You summarize clusters of community notes pinned to one real-world spot on a map. '
                . 'Write a warm, factual 2-3 sentence summary with a short 3-6 word headline. '
                . 'Do not invent facts. Only use what is in the notes. '
                . 'Return STRICT JSON: {"title": "...", "summary": "..."}';

        $user = "Notes:\n{$bullets}";
        $provider = config('services.llm_provider', 'stub');

        if ($provider === 'stub') {
            $this->persist($story, [
                'title'   => 'Story at ' . number_format((float) $story->center_lat, 3) . ', ' . number_format((float) $story->center_lng, 3),
                'summary' => '[stub] ' . $pins->count() . ' public notes cluster here.',
                'model'   => 'stub',
            ]);
            return;
        }

        try {
            $text = match ($provider) {
                'groq'      => $this->callOpenAICompatible(
                    (string) config('services.groq.url'),
                    (string) config('services.groq.key'),
                    (string) config('services.groq.model'),
                    $system, $user),
                'ollama'    => $this->callOpenAICompatible(
                    (string) config('services.ollama.url'),
                    null,
                    (string) config('services.ollama.model'),
                    $system, $user),
                'anthropic' => $this->callAnthropic($system, $user),
                default     => throw new \RuntimeException("Unknown LLM_PROVIDER '{$provider}'"),
            };

            $parsed = $this->extractJson($text);

            // If the model double-encoded (returned JSON inside a "summary" field),
            // dig one level deeper.
            if ($parsed && isset($parsed['summary']) && is_string($parsed['summary'])) {
                $inner = $this->extractJson($parsed['summary']);
                if ($inner && isset($inner['summary'])) {
                    $parsed = $inner;
                }
            }

            $title   = $parsed['title']   ?? 'Location story';
            $summary = $parsed['summary'] ?? trim($text);

            // Safety: if the summary is still obviously raw JSON, strip it down to
            // something readable so the popup never shows escaped braces.
            if (is_string($summary) && preg_match('/^\s*[\{\[]/', $summary)) {
                $fallback = $this->extractJson($summary);
                $summary = $fallback['summary'] ?? trim(strip_tags($summary));
            }

            $model = match ($provider) {
                'groq'      => config('services.groq.model'),
                'ollama'    => config('services.ollama.model'),
                'anthropic' => config('services.anthropic.model'),
                default     => 'unknown',
            };

            $this->persist($story, [
                'title'   => mb_substr($title, 0, 120),
                'summary' => $summary,
                'model'   => $provider . ':' . $model,
            ]);
        } catch (\Throwable $e) {
            Log::warning('SummarizeStoryJob failed', ['story' => $story->id, 'provider' => $provider, 'err' => $e->getMessage()]);
            $story->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
        }
    }

    /**
     * Extract the first well-formed JSON object from arbitrary model output.
     * Handles: raw JSON, ```json fenced blocks, prose + JSON, nested braces.
     */
    private function extractJson(string $text): ?array
    {
        $t = trim($text);

        // Strip common code fences.
        $t = preg_replace('/^```(?:json)?\s*/i', '', $t);
        $t = preg_replace('/\s*```\s*$/', '', $t);

        // Direct decode.
        $direct = json_decode($t, true);
        if (is_array($direct)) {
            return $direct;
        }

        // Otherwise, scan for the first balanced {...} block.
        $len = strlen($t);
        $start = null; $depth = 0; $inStr = false; $esc = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = $t[$i];

            if ($inStr) {
                if ($esc)               { $esc = false; }
                else if ($ch === '\\')  { $esc = true; }
                else if ($ch === '"')   { $inStr = false; }
                continue;
            }

            if ($ch === '"') { $inStr = true; continue; }
            if ($ch === '{') {
                if ($depth === 0) { $start = $i; }
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0 && $start !== null) {
                    $candidate = substr($t, $start, $i - $start + 1);
                    $decoded = json_decode($candidate, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                    $start = null;
                }
            }
        }

        return null;
    }
    private function callOpenAICompatible(string $url, ?string $key, string $model, string $system, string $user): string
    {
        $headers = ['content-type' => 'application/json'];
        if ($key) { $headers['authorization'] = 'Bearer ' . $key; }

        $res = Http::withHeaders($headers)->timeout(60)->post($url, [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $user],
            ],
            'temperature' => 0.4,
            'max_tokens'  => 400,
        ]);

        if (! $res->ok()) {
            throw new \RuntimeException('LLM error: ' . $res->status() . ' ' . $res->body());
        }
        return (string) ($res->json('choices.0.message.content') ?? '');
    }

    private function callAnthropic(string $system, string $user): string
    {
        $apiKey = config('services.anthropic.key');
        if (! $apiKey) { throw new \RuntimeException('ANTHROPIC_API_KEY missing.'); }

        $res = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 400,
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $user]],
        ]);

        if (! $res->ok()) { throw new \RuntimeException('Anthropic error: ' . $res->status() . ' ' . $res->body()); }
        return (string) ($res->json('content.0.text') ?? '');
    }

    private function persist(LocationStory $story, array $data): void
    {
        $story->update(array_merge([
            'status' => 'ready', 'generated_at' => now(), 'error' => null,
        ], $data));
    }
}