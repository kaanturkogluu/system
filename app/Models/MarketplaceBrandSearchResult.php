<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceBrandSearchResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_id',
        'brand_id',
        'query_name',
        'response',
    ];

    protected $casts = [
        'marketplace_id' => 'integer',
        'brand_id' => 'integer',
        'response' => 'array',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class, 'marketplace_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}

