<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationStory extends Model
{
    protected $fillable = [
        'center_lat', 'center_lng', 'radius_m',
        'title', 'summary', 'pin_ids', 'pin_count',
        'status', 'model', 'error', 'generated_at',
    ];

    protected $casts = [
        'pin_ids'      => 'array',
        'generated_at' => 'datetime',
    ];

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->summary;
    }

    /**
     * Normalized pin id list — always an array of ints, even if the
     * underlying column returned a JSON string or a comma list.
     */
    public function pinIdList(): array
    {
        $v = $this->pin_ids;

        if (is_array($v)) {
            return array_values(array_map('intval', $v));
        }

        if (is_string($v) && $v !== '') {
            $decoded = json_decode($v, true);
            if (is_array($decoded)) {
                return array_values(array_map('intval', $decoded));
            }
            return array_values(array_filter(array_map('intval', explode(',', $v))));
        }

        return [];
    }

    /** Only public pin rows that belong to this story. */
    public function publicPins()
    {
        return Pin::whereIn('id', $this->pinIdList())
            ->where('visibility', 'public')
            ->where('is_hidden', false);
    }
}