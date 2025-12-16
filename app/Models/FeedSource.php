<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'type',
        'schedule',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Feed runs ilişkisi
     */
    public function feedRuns()
    {
        return $this->hasMany(FeedRun::class, 'feed_source_id');
    }

    /**
     * Son başarılı run
     */
    public function lastSuccessfulRun()
    {
        return $this->hasOne(FeedRun::class, 'feed_source_id')
            ->where('status', 'DONE')
            ->latest('id');
    }
}

