<?php

namespace App\Services;

use App\Helpers\MarketplaceConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;

class TrendyolBrandService
{
    private const MARKETPLACE_SLUG = 'trendyol';
    
    private ?string $baseUrl = null;
    private ?string $supplierId = null;
    private ?string $apiKey = null;
    private ?string $apiSecret = null;
    private bool $configLoaded = false;

    /**
     * Load configuration from database (lazy loading)
     *
     * @return void
     */
    private function loadConfiguration(): void
    {
        if ($this->configLoaded) {
            return;
        }

        try {
            $this->baseUrl = MarketplaceConfig::get(self::MARKETPLACE_SLUG, 'base_url');
            $this->supplierId = MarketplaceConfig::get(self::MARKETPLACE_SLUG, 'supplier_id');
            $this->apiKey = MarketplaceConfig::get(self::MARKETPLACE_SLUG, 'api_key');
            $this->apiSecret = MarketplaceConfig::get(self::MARKETPLACE_SLUG, 'api_secret');
            $this->configLoaded = true;
        } catch (InvalidArgumentException $e) {
            Log::channel('imports')->error('Trendyol API configuration missing', [
                'error' => $e->getMessage(),
            ]);
            $this->configLoaded = true; // Mark as loaded to prevent repeated attempts
        }
    }

    /**
     * Validate that all required configuration is present
     *
     * @return bool
     */
    private function isConfigured(): bool
    {
        $this->loadConfiguration();
        return !empty($this->baseUrl) 
            && !empty($this->supplierId) 
            && !empty($this->apiKey) 
            && !empty($this->apiSecret);
    }

    /**
     * Search brands by name
     *
     * @param string $brandName
     * @return array|null
     */
    public function searchBrandsByName(string $brandName): ?array
    {
        if (!$this->isConfigured()) {
            Log::channel('imports')->error('Trendyol API credentials not configured', [
                'missing_config' => [
                    'base_url' => empty($this->baseUrl),
                    'supplier_id' => empty($this->supplierId),
                    'api_key' => empty($this->apiKey),
                    'api_secret' => empty($this->apiSecret),
                ],
            ]);
            return null;
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/integration/product/brands/by-name?name=';
            
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->get($url, [
                    'name' => $brandName,
                ]);

            if (!$response->successful()) {
                Log::channel('imports')->warning('Trendyol brand search failed', [
                    'brand_name' => $brandName,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            
            return $data ?? [];

        } catch (Exception $e) {
            Log::channel('imports')->error('Trendyol brand search exception', [
                'brand_name' => $brandName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

