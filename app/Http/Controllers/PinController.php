<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\Pin;
use Illuminate\Http\Request;

class PinController extends Controller
{
    public function index()
    {
        return view('map');
    }

    public function list(Request $request)
    {
        $user = $request->user();

        // Uses Step 1's User::friendIds() - sender_id / receiver_id schema.
        $friendIds = $user->friendIds();

        $pins = Pin::with(['user:id,name', 'media'])
            ->withCount(['comments', 'reactions'])
            ->withExists(['reactions as echoed' => fn ($q) => $q->where('user_id', $user->id)])
            ->where('is_hidden', false)
            ->where(function ($q) use ($user, $friendIds) {
                $q->where('visibility', 'public')
                  ->orWhere('user_id', $user->id)
                  ->orWhere(function ($q2) use ($friendIds) {
                      $q2->where('visibility', 'friends')->whereIn('user_id', $friendIds);
                  });
            })
            ->when($request->filled('theme'), fn ($q) => $q->where('theme', $request->theme))
            ->when($request->filled('from'), function ($q) use ($request) {
                $from = $request->from;
                $q->where(function ($w) use ($from) {
                    $w->whereDate('event_date', '>=', $from)
                      ->orWhere(function ($x) use ($from) {
                          $x->whereNull('event_date')->whereDate('created_at', '>=', $from);
                      });
                });
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $to = $request->to;
                $q->where(function ($w) use ($to) {
                    $w->whereDate('event_date', '<=', $to)
                      ->orWhere(function ($x) use ($to) {
                          $x->whereNull('event_date')->whereDate('created_at', '<=', $to);
                      });
                });
            })            ->latest()
            ->get();

        return response()->json($pins);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'body' => 'nullable|string|max:2000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'theme' => 'nullable|string|max:50',
            'visibility' => 'required|in:private,friends,public',
            'event_date' => 'nullable|date',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac|max:10240',
        ]);

        $pin = $request->user()->pins()->create(
            collect($data)->except(['photos', 'audio'])->all()
        );

        foreach ($request->file('photos', []) as $photo) {
            $pin->media()->create([
                'type' => 'photo',
                'path' => $photo->store('pins', 'public'),
            ]);
        }

        if ($request->hasFile('audio')) {
            $pin->media()->create([
                'type' => 'audio',
                'path' => $request->file('audio')->store('pins', 'public'),
            ]);
        }

        return response()->json($pin->load(['user:id,name', 'media']), 201);
    }
}