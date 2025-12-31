<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    public function brands()
    {
        return $this->hasMany(Brand::class, 'origin_country_id');
    }

    public function marketplaceMappings()
    {
        return $this->hasMany(MarketplaceCountryMapping::class, 'country_id');
    }
}
