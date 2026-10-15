<?php

namespace App\Jobs;

use App\Models\LocationStory;
use App\Models\Pin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ClusterPinsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public ?string $from = null,
        public ?string $to   = null,
        public int     $minPins = 3,
        public int     $radiusM = 150,
    ) {}

    public function handle(): void
    {
        // ---- HARD PRIVACY RULE ----
        // Only PUBLIC, non-hidden pins ever enter the pipeline.
        $query = Pin::query()
            ->where('visibility', 'public')
            ->where('is_hidden', false);

        if ($this->from) {
            $query->where(function ($w) {
                $w->whereDate('event_date', '>=', $this->from)
                  ->orWhere(fn ($x) => $x->whereNull('event_date')->whereDate('created_at', '>=', $this->from));
            });
        }
        if ($this->to) {
            $query->where(function ($w) {
                $w->whereDate('event_date', '<=', $this->to)
                  ->orWhere(fn ($x) => $x->whereNull('event_date')->whereDate('created_at', '<=', $this->to));
            });
        }

        $pins = $query->get(['id', 'latitude', 'longitude', 'title', 'body', 'theme']);
        if ($pins->isEmpty()) {
            return;
        }

        // ---- Simple grid clustering by (lat, lng) cell ----
        // 1 degree lat ≈ 111_000 m. So cell = radiusM / 111000.
        $cell = max(0.0005, $this->radiusM / 111000);
        $buckets = [];

        foreach ($pins as $p) {
            $key = round(((float) $p->latitude)  / $cell) . ':' . round(((float) $p->longitude) / $cell);
            $buckets[$key][] = $p;
        }

        // ---- Upsert a story per bucket with enough pins ----
        foreach ($buckets as $group) {
            if (count($group) < $this->minPins) {
                continue;
            }

            $ids  = array_map(fn ($p) => $p->id, $group);
            sort($ids);

            $lat = array_sum(array_map(fn ($p) => (float) $p->latitude,  $group)) / count($group);
            $lng = array_sum(array_map(fn ($p) => (float) $p->longitude, $group)) / count($group);

            // Fingerprint so we don't duplicate the same cluster on re-run.
            $fp = md5(json_encode($ids));

            $story = LocationStory::firstOrCreate(
                ['pin_ids' => $ids], // Eloquent casts to JSON on write
                [
                    'center_lat' => $lat,
                    'center_lng' => $lng,
                    'radius_m'   => $this->radiusM,
                    'pin_count'  => count($ids),
                    'status'     => 'pending',
                ]
            );

            // If it already existed but is still pending, dispatch the summarizer.
            if ($story->status === 'pending') {
                SummarizeStoryJob::dispatch($story->id);
            }
        }
    }
}