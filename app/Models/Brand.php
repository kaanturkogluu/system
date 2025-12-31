<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'brands';

    protected $fillable = [
        'name',
        'normalized_name',
        'slug',
        'status',
        'origin_country_id',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function marketplaceMappings()
    {
        return $this->hasMany(MarketplaceBrandMapping::class, 'brand_id');
    }

    public function originCountry()
    {
        return $this->belongsTo(Country::class, 'origin_country_id');
    }
}

