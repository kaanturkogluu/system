<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_id',
        'key',
        'value',
        'is_sensitive',
    ];

    protected $casts = [
        'marketplace_id' => 'integer',
        'is_sensitive' => 'boolean',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class, 'marketplace_id');
    }
}

