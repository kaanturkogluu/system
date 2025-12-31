<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceCountryMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_id',
        'country_id',
        'external_country_id',
        'external_country_code',
        'external_country_name',
        'status',
    ];

    protected $casts = [
        'marketplace_id' => 'integer',
        'country_id' => 'integer',
        'external_country_id' => 'integer',
    ];

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class, 'marketplace_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
