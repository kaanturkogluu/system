<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'parent_id',
        'level',
        'name',
        'slug',
        'path',
        'sort_order',
        'is_leaf',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'sort_order' => 'integer',
        'is_leaf' => 'boolean',
        'is_active' => 'boolean',
        'parent_id' => 'integer',
    ];

    /**
     * Üst kategori ilişkisi
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Alt kategoriler ilişkisi
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Tüm alt kategoriler (recursive)
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Aktif alt kategoriler
     */
    public function activeChildren()
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Get attributes for this category
     */
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'category_attributes', 'category_id', 'attribute_id')
            ->withPivot('is_required')
            ->withTimestamps();
    }

    /**
     * Get category attributes pivot
     */
    public function categoryAttributes()
    {
        return $this->hasMany(CategoryAttribute::class, 'category_id');
    }

    /**
     * Get Trendyol marketplace category mapping
     */
    public function trendyolCategory()
    {
        return $this->hasOne(MarketplaceCategory::class, 'global_category_id', 'id')
            ->whereHas('marketplace', function ($query) {
                $query->where('slug', 'trendyol');
            });
    }
}

