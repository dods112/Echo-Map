<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $friends = User::whereIn('id', $me->friendIds())->orderBy('name')->get();

        $incoming = $me->receivedRequests()->with('sender')->latest()->get();
        $outgoing = $me->sentRequests()->with('receiver')->latest()->get();

        return view('friends.index', compact('friends', 'incoming', 'outgoing'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $me     = $request->user();
        $target = User::where('email', $data['email'])->firstOrFail();

        if ($target->id === $me->id) {
            return back()->with('error', 'You cannot add yourself.');
        }

        // If they already sent us a pending request, auto-accept.
        $reverse = Friendship::where('sender_id', $target->id)
            ->where('receiver_id', $me->id)
            ->where('status', 'pending')
            ->first();

        if ($reverse) {
            $reverse->update(['status' => 'accepted']);
            return back()->with('status', "You are now friends with {$target->name}.");
        }

        // Already friends / already pending?
        $existing = Friendship::where('sender_id', $me->id)
            ->where('receiver_id', $target->id)
            ->first();

        if ($existing) {
            return back()->with('error', $existing->status === 'pending'
                ? 'Request already sent.'
                : 'Already friends.');
        }

        Friendship::create([
            'sender_id'   => $me->id,
            'receiver_id' => $target->id,
            'status'      => 'pending',
        ]);

        return back()->with('status', "Friend request sent to {$target->name}.");
    }

    public function accept(Request $request, Friendship $friendship)
    {
        abort_unless((int) $friendship->receiver_id === (int) $request->user()->id, 403);
        abort_unless($friendship->status === 'pending', 400);

        $friendship->update(['status' => 'accepted']);

        return back()->with('status', 'Friend request accepted.');
    }

    public function decline(Request $request, Friendship $friendship)
    {
        abort_unless((int) $friendship->receiver_id === (int) $request->user()->id, 403);

        $friendship->delete();

        return back()->with('status', 'Friend request declined.');
    }

    public function destroy(Request $request, User $user)
    {
        $me = $request->user();

        Friendship::where('status', 'accepted')
            ->where(function ($q) use ($me, $user) {
                $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
            })
            ->orWhere(function ($q) use ($me, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
            })
            ->delete();

        return back()->with('status', "Removed {$user->name} from friends.");
    }
}
