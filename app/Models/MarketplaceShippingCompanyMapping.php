<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceShippingCompanyMapping extends Model
{
    use HasFactory;

    protected $table = 'marketplace_shipping_company_mappings';

    protected $fillable = [
        'marketplace_id',
        'shipping_company_id',
        'external_id',
        'external_code',
        'external_name',
        'tax_number',
        'status',
        'is_default',
    ];

    protected $casts = [
        'marketplace_id' => 'integer',
        'shipping_company_id' => 'integer',
        'external_id' => 'integer',
        'is_default' => 'boolean',
    ];

    /**
     * Get the marketplace that owns this mapping
     */
    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class, 'marketplace_id');
    }

    /**
     * Get the shipping company that owns this mapping
     */
    public function shippingCompany(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id');
    }
}
