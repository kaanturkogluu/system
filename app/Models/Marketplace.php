<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marketplace extends Model
{
    use HasFactory;

    protected $table = 'marketplaces';

    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    public function brandMappings()
    {
        return $this->hasMany(MarketplaceBrandMapping::class, 'marketplace_id');
    }

    public function settings()
    {
        return $this->hasMany(MarketplaceSetting::class, 'marketplace_id');
    }

    public function countryMappings()
    {
        return $this->hasMany(MarketplaceCountryMapping::class, 'marketplace_id');
    }
}

