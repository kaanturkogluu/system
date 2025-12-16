<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceCategory extends Model
{
    use HasFactory;

    protected $table = 'marketplace_categories';

    protected $fillable = [
        'marketplace_id',
        'marketplace_category_id',
        'marketplace_parent_id',
        'name',
        'level',
        'path',
        'global_category_id',
        'is_mapped',
    ];

    protected $casts = [
        'marketplace_id' => 'integer',
        'marketplace_category_id' => 'integer',
        'marketplace_parent_id' => 'integer',
        'level' => 'integer',
        'global_category_id' => 'integer',
        'is_mapped' => 'boolean',
    ];

    /**
     * Marketplace ilişkisi
     */
    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class, 'marketplace_id');
    }

    /**
     * Global kategori ilişkisi
     */
    public function globalCategory()
    {
        return $this->belongsTo(Category::class, 'global_category_id');
    }

    /**
     * Marketplace parent kategori ilişkisi
     */
    public function marketplaceParent()
    {
        return $this->belongsTo(MarketplaceCategory::class, 'marketplace_parent_id');
    }

    /**
     * Marketplace alt kategoriler
     */
    public function marketplaceChildren()
    {
        return $this->hasMany(MarketplaceCategory::class, 'marketplace_parent_id');
    }
}

