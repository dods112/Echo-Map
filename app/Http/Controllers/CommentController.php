<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Pin;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    private function shape(Comment $c, $user): array
    {
        return [
            'id' => $c->id,
            'body' => $c->body,
            'author' => $c->user->name,
            'created_at' => $c->created_at->toIso8601String(),
            'can_delete' => (int) $c->user_id === (int) $user->id || (bool) $user->is_admin,
        ];
    }

    public function index(Request $request, Pin $pin)
    {
        $user = $request->user();
        abort_unless($pin->isVisibleTo($user), 404);

        $items = $pin->comments()->with('user:id,name')->oldest()->get()
            ->map(fn ($c) => $this->shape($c, $user));

        return response()->json($items);
    }

    public function store(Request $request, Pin $pin)
    {
        $user = $request->user();
        abort_unless($pin->isVisibleTo($user), 404);

        $data = $request->validate(['body' => 'required|string|max:500']);

        $comment = $pin->comments()->create([
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);
        $comment->load('user:id,name');

        return response()->json($this->shape($comment, $user), 201);
    }

    public function destroy(Request $request, Comment $comment)
    {
        $user = $request->user();
        abort_unless((int) $comment->user_id === (int) $user->id || $user->is_admin, 403);

        $comment->delete();

        return response()->noContent();
    }
}