<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marketplace extends Model
{
    use HasFactory;

    protected $table = 'marketplaces';

    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
    ];
}

