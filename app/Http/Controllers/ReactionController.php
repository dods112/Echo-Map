<?php

namespace App\Http\Controllers;

use App\Models\Pin;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function toggle(Request $request, Pin $pin)
    {
        $user = $request->user();
        abort_unless($pin->isVisibleTo($user), 404);

        $existing = $pin->reactions()
            ->where('user_id', $user->id)
            ->where('type', 'echo')
            ->first();

        if ($existing) {
            $existing->delete();
            $echoed = false;
        } else {
            $pin->reactions()->create(['user_id' => $user->id, 'type' => 'echo']);
            $echoed = true;
        }

        return response()->json([
            'echoed' => $echoed,
            'count' => $pin->reactions()->count(),
        ]);
    }
}