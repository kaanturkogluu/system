<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use HasFactory;

    protected $table = 'attributes';

    protected $fillable = [
        'code',
        'name',
        'data_type',
        'is_filterable',
        'status',
        'external_id',
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
    ];

    public function values()
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')
            ->where('status', 'active')
            ->orderBy('value');
    }

    public function allValues()
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')
            ->orderBy('value');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_attributes', 'attribute_id', 'category_id')
            ->withPivot('is_required')
            ->withTimestamps();
    }
}

