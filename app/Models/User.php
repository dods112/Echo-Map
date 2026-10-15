<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

        public function pins()
    {
        return $this->hasMany(\App\Models\Pin::class);
    }

    public function sentRequests()
    {
        return $this->hasMany(\App\Models\Friendship::class, 'sender_id');
    }

    public function receivedRequests()
    {
        return $this->hasMany(\App\Models\Friendship::class, 'receiver_id');
    }

    /** IDs of accepted friends (both directions). */
    public function friendIds(): array
    {
        $sent = \App\Models\Friendship::where('sender_id', $this->id)
            ->where('status', 'accepted')->pluck('receiver_id')->all();

        $recv = \App\Models\Friendship::where('receiver_id', $this->id)
            ->where('status', 'accepted')->pluck('sender_id')->all();

        return array_values(array_unique(array_merge($sent, $recv)));
    }

    public function isFriendsWith(User $other): bool
    {
        return \App\Models\Friendship::where('status', 'accepted')
            ->where(function ($q) use ($other) {
                $q->where('sender_id', $this->id)->where('receiver_id', $other->id);
            })
            ->orWhere(function ($q) use ($other) {
                $q->where('sender_id', $other->id)->where('receiver_id', $this->id);
            })
            ->exists();
    }
}
