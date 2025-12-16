<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_source_id',
        'status',
        'started_at',
        'ended_at',
        'file_path',
        'file_hash',
        'file_size',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Feed source ilişkisi
     */
    public function feedSource()
    {
        return $this->belongsTo(FeedSource::class, 'feed_source_id');
    }
}

