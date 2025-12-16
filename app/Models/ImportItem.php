<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportItem extends Model
{
    use HasFactory;

    protected $table = 'import_items';

    protected $fillable = [
        'feed_run_id',
        'external_id',
        'sku',
        'barcode',
        'payload',
        'hash',
        'status',
        'error_message',
    ];

    protected $casts = [
        'feed_run_id' => 'integer',
        'payload' => 'array',
    ];

    /**
     * Feed run ilişkisi
     */
    public function feedRun()
    {
        return $this->belongsTo(FeedRun::class, 'feed_run_id');
    }
}

