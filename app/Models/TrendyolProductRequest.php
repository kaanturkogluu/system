<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrendyolProductRequest extends Model
{
    protected $table = 'trendyol_product_requests';

    protected $fillable = [
        'product_id',
        'batch_request_id',
        'request_data',
        'response_data',
        'batch_status_data',
        'status',
        'error_message',
        'items_count',
        'success_count',
        'failed_count',
        'sent_at',
        'completed_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'batch_status_data' => 'array',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Ürün ilişkisi
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Status'a göre scope'lar
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
