<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $table = 'currencies';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'rate_to_try',
        'is_default',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'rate_to_try' => 'decimal:6',
        'is_default' => 'boolean',
    ];

    /**
     * Get products using this currency
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'currency_id');
    }
}
