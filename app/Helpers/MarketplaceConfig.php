<?php

namespace App\Helpers;

use App\Models\Marketplace;
use App\Models\MarketplaceSetting;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class MarketplaceConfig
{
    /**
     * Cache key prefix for marketplace settings
     */
    private const CACHE_PREFIX = 'marketplace_config:';

    /**
     * Cache TTL in seconds (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * In-memory cache for current request
     */
    private static array $memoryCache = [];

    /**
     * Get a marketplace setting value by slug and key
     *
     * @param string $marketplaceSlug
     * @param string $key
     * @param mixed $default
     * @return string|null
     * @throws InvalidArgumentException
     */
    public static function get(string $marketplaceSlug, string $key, $default = null): ?string
    {
        $cacheKey = self::CACHE_PREFIX . $marketplaceSlug . ':' . $key;

        // Check memory cache first
        if (isset(self::$memoryCache[$cacheKey])) {
            return self::$memoryCache[$cacheKey];
        }

        // Check application cache
        $value = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($marketplaceSlug, $key) {
            $marketplace = Marketplace::where('slug', $marketplaceSlug)->first();

            if (!$marketplace) {
                throw new InvalidArgumentException(
                    "Marketplace not found: {$marketplaceSlug}"
                );
            }

            $setting = MarketplaceSetting::where('marketplace_id', $marketplace->id)
                ->where('key', $key)
                ->first();

            return $setting ? $setting->value : null;
        });

        // Store in memory cache
        self::$memoryCache[$cacheKey] = $value;

        if ($value === null && $default === null) {
            throw new InvalidArgumentException(
                "Setting '{$key}' not found for marketplace '{$marketplaceSlug}'"
            );
        }

        return $value ?? $default;
    }

    /**
     * Get all settings for a marketplace as key-value array
     *
     * @param string $marketplaceSlug
     * @return array
     * @throws InvalidArgumentException
     */
    public static function all(string $marketplaceSlug): array
    {
        $marketplace = Marketplace::where('slug', $marketplaceSlug)->first();

        if (!$marketplace) {
            throw new InvalidArgumentException(
                "Marketplace not found: {$marketplaceSlug}"
            );
        }

        return MarketplaceSetting::where('marketplace_id', $marketplace->id)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Check if a setting exists
     *
     * @param string $marketplaceSlug
     * @param string $key
     * @return bool
     */
    public static function has(string $marketplaceSlug, string $key): bool
    {
        try {
            self::get($marketplaceSlug, $key);
            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Clear cache for a marketplace
     *
     * @param string $marketplaceSlug
     * @return void
     */
    public static function clearCache(string $marketplaceSlug): void
    {
        $marketplace = Marketplace::where('slug', $marketplaceSlug)->first();

        if (!$marketplace) {
            return;
        }

        $settings = MarketplaceSetting::where('marketplace_id', $marketplace->id)
            ->pluck('key')
            ->toArray();

        foreach ($settings as $key) {
            $cacheKey = self::CACHE_PREFIX . $marketplaceSlug . ':' . $key;
            Cache::forget($cacheKey);
            unset(self::$memoryCache[$cacheKey]);
        }
    }

    /**
     * Clear all marketplace config cache
     *
     * @return void
     */
    public static function clearAllCache(): void
    {
        self::$memoryCache = [];
        // Note: Full cache clear would require iterating all cache keys
        // For production, consider using cache tags if available
    }
}

