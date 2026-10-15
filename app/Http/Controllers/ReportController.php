<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Pin;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function reportPin(Request $request, Pin $pin)
    {
        $user = $request->user();
        abort_unless($pin->isVisibleTo($user), 404);

        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        // Idempotent: one report per user per pin (unique index enforces it too).
        try {
            Report::create([
                'pin_id'      => $pin->id,
                'comment_id'  => null,
                'reporter_id' => $user->id,
                'reason'      => $data['reason'],
                'status'      => 'pending',
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json(['ok' => true, 'already' => true], 200);
        }

        return response()->json(['ok' => true], 201);
    }

    public function reportComment(Request $request, Comment $comment)
    {
        $user = $request->user();
        abort_unless($comment->pin->isVisibleTo($user), 404);

        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            Report::create([
                'pin_id'      => null,
                'comment_id'  => $comment->id,
                'reporter_id' => $user->id,
                'reason'      => $data['reason'],
                'status'      => 'pending',
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json(['ok' => true, 'already' => true], 200);
        }

        return response()->json(['ok' => true], 201);
    }
}