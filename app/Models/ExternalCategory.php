<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExternalCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'external_id',
        'raw_path',
        'level',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    public function mapping(): HasOne
    {
        return $this->hasOne(CategoryMapping::class, 'external_category_id');
    }
}
