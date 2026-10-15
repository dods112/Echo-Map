<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['pin_id', 'user_id', 'body'];

    public function pin() { return $this->belongsTo(Pin::class); }
    public function user() { return $this->belongsTo(User::class); }
}