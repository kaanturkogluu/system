<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XmlAttributeMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'source_attribute_key',
        'attribute_id',
        'status',
    ];

    protected $casts = [
        'attribute_id' => 'integer',
    ];

    /**
     * Attribute relationship
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
