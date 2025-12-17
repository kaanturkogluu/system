<?php

namespace App\Helpers;

class BrandNormalizer
{
    /**
     * Normalize brand name for unique identification
     * 
     * Rules:
     * - Convert to lowercase
     * - Remove all non-alphanumeric characters
     * 
     * Examples:
     * - "Apple®" → "apple"
     * - "APPLE Inc." → "appleinc"
     * - "Samsung Electronics" → "samsungelectronics"
     * 
     * @param string $name
     * @return string
     */
    public static function normalize(string $name): string
    {
        $normalized = mb_strtolower($name, 'UTF-8');
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);
        
        return $normalized;
    }

    /**
     * Generate SEO-friendly slug from brand name
     * 
     * @param string $name
     * @return string
     */
    public static function slug(string $name): string
    {
        return \Illuminate\Support\Str::slug($name, '-', 'tr');
    }
}

