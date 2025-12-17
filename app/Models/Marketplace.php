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
}

