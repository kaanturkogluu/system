<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingCompany extends Model
{
    use HasFactory;

    protected $table = 'shipping_companies';

    protected $fillable = [
        'code',
        'name',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    /**
     * Get marketplace mappings for this shipping company
     */
    public function marketplaceMappings(): HasMany
    {
        return $this->hasMany(MarketplaceShippingCompanyMapping::class, 'shipping_company_id');
    }
}
