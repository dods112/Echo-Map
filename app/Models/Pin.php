<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pin extends Model
{
    protected $fillable = [
        'user_id', 'title', 'body', 'latitude', 'longitude',
        'theme', 'visibility', 'event_date', 'is_hidden',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function media() { return $this->hasMany(Media::class); }
    public function comments() { return $this->hasMany(Comment::class); }
    public function reactions() { return $this->hasMany(Reaction::class); }
    public function reports() { return $this->hasMany(Report::class); }

    public function isVisibleTo(User $user): bool
    {
        if ($this->is_hidden) {
            return false;
        }
        if ((int) $this->user_id === (int) $user->id) {
            return true;
        }
        if ($this->visibility === 'public') {
            return true;
        }
        if ($this->visibility === 'friends') {
            return Friendship::where('status', 'accepted')
                ->where(function ($q) use ($user) {
                    $q->where(function ($a) use ($user) {
                        $a->where('user_id', $user->id)->where('friend_id', $this->user_id);
                    })->orWhere(function ($b) use ($user) {
                        $b->where('user_id', $this->user_id)->where('friend_id', $user->id);
                    });
                })->exists();
        }
        return false;
    }
}