<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_category_id',
        'category_id',
        'status',
        'confidence',
    ];

    protected $casts = [
        'confidence' => 'integer',
    ];

    public function externalCategory(): BelongsTo
    {
        return $this->belongsTo(ExternalCategory::class, 'external_category_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
