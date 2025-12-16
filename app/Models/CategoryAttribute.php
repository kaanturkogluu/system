<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryAttribute extends Model
{
    use HasFactory;

    protected $table = 'category_attributes';

    protected $fillable = [
        'category_id',
        'attribute_key',
        'is_required',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'is_required' => 'boolean',
    ];

    /**
     * Kategori ilişkisi
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}

