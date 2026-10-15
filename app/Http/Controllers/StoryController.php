<?php

namespace App\Http\Controllers;

use App\Models\LocationStory;
use App\Models\Pin;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function list(Request $request)
    {
        // Only READY stories reach the browser.
        $stories = LocationStory::query()
            ->where('status', 'ready')
            ->whereNotNull('summary')
            ->get();

        // Build the same visibility scope used by /pins so we can check
        // how many of a story's public pins survive the current filters.
        $user = $request->user();

        $baseQuery = function () use ($user) {
            return Pin::query()
                ->where('visibility', 'public')
                ->where('is_hidden', false);
        };

        $filteredIds = (clone $baseQuery())
            ->when($request->filled('theme'), fn ($q) => $q->where('theme', $request->theme))
            ->when($request->filled('from'), function ($q) use ($request) {
                $from = $request->from;
                $q->where(function ($w) use ($from) {
                    $w->whereDate('event_date', '>=', $from)
                      ->orWhere(fn ($x) => $x->whereNull('event_date')->whereDate('created_at', '>=', $from));
                });
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $to = $request->to;
                $q->where(function ($w) use ($to) {
                    $w->whereDate('event_date', '<=', $to)
                      ->orWhere(fn ($x) => $x->whereNull('event_date')->whereDate('created_at', '<=', $to));
                });
            })
            ->pluck('id')
            ->all();

        $visible = array_flip($filteredIds);

        $out = $stories->map(function (LocationStory $s) use ($visible) {
            $ids       = $s->pinIdList();
            $survivors = array_values(array_filter($ids, fn ($id) => isset($visible[$id])));

            return [
                'id'         => $s->id,
                'title'      => $s->title,
                'summary'    => $s->summary,
                'center_lat' => (float) $s->center_lat,
                'center_lng' => (float) $s->center_lng,
                'radius_m'   => (int) $s->radius_m,
                'pin_count'  => count($survivors),
                'pin_ids'    => $survivors,
                'generated_at' => optional($s->generated_at)->toIso8601String(),
            ];
        })
        // Only show a halo if 3+ of its public pins are actually visible.
        ->filter(fn ($s) => $s['pin_count'] >= 3)
        ->values();

        return response()->json($out);
    }
}