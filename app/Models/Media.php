<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = ['pin_id', 'type', 'path'];

    public function pin() { return $this->belongsTo(Pin::class); }
}