<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'pin_id', 'comment_id', 'reporter_id',
        'reason', 'status', 'reviewed_by', 'reviewed_at', 'admin_note',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function pin()      { return $this->belongsTo(Pin::class); }
    public function comment()  { return $this->belongsTo(Comment::class); }
    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function targetLabel(): string
    {
        if ($this->pin_id)     return 'Pin #' . $this->pin_id;
        if ($this->comment_id) return 'Comment #' . $this->comment_id;
        return 'Unknown';
    }
}