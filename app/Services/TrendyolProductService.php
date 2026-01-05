<?php

namespace App\Services;

use App\Helpers\MarketplaceConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;

class TrendyolProductService
{
    private const MARKETPLACE_SLUG = 'trendyol';
    private const API_BASE_URL = 'https://apigw.trendyol.com';
    
    private ?string $supplierId = null;
    private ?string $apiKey = null;
    private ?string $apiSecret = null;
    private bool $configLoaded = false;

    /**
     * Load configuration from database (lazy loading)
     */
    private function loadConfiguration(): void
    {
        if ($this->configLoaded) {
            return;
        }

        try {
            $this->supplierId = MarketplaceConfig::get(self::MARKETPLACE_SLUG, 'supplier_id');
            $this->apiKey = MarketplaceConfig::get(self::MARKETPLACE_SLUG, 'api_key');
            $this->apiSecret = MarketplaceConfig::get(self::MARKETPLACE_SLUG, 'api_secret');
            $this->configLoaded = true;
        } catch (InvalidArgumentException $e) {
            Log::channel('imports')->error('Trendyol API configuration missing', [
                'error' => $e->getMessage(),
            ]);
            $this->configLoaded = true;
        }
    }

    /**
     * Validate that all required configuration is present
     */
    private function isConfigured(): bool
    {
        $this->loadConfiguration();
        return !empty($this->supplierId) 
            && !empty($this->apiKey) 
            && !empty($this->apiSecret);
    }

    /**
     * Get headers for API requests
     */
    private function getHeaders(): array
    {
        $this->loadConfiguration();
        
        return [
            'Content-Type' => 'application/json',
            'User-Agent' => $this->supplierId . ' - SelfIntegration',
        ];
    }

    /**
     * Send products to Trendyol API
     *
     * @param array $items Product items array
     * @return array|null Response with batchRequestId or null on error
     */
    public function sendProducts(array $items): ?array
    {
        if (!$this->isConfigured()) {
            Log::channel('imports')->error('Trendyol API credentials not configured');
            return null;
        }

        try {
            $url = self::API_BASE_URL . '/integration/product/sellers/' . $this->supplierId . '/products';
            
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->withHeaders($this->getHeaders())
                ->timeout(60)
                ->post($url, $items);

            if (!$response->successful()) {
                Log::channel('imports')->error('Trendyol product send failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'status' => $response->status(),
                    'error' => $response->body(),
                ];
            }

            $data = $response->json();
            
            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (Exception $e) {
            Log::channel('imports')->error('Trendyol product send exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check batch request status
     *
     * @param string $batchRequestId
     * @return array|null Response data or null on error
     */
    public function checkBatchStatus(string $batchRequestId): ?array
    {
        if (!$this->isConfigured()) {
            Log::channel('imports')->error('Trendyol API credentials not configured');
            return null;
        }

        try {
            $url = self::API_BASE_URL . '/integration/product/sellers/' . $this->supplierId . '/products/batch-requests/' . $batchRequestId;
            
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->withHeaders($this->getHeaders())
                ->timeout(30)
                ->get($url);

            if (!$response->successful()) {
                Log::channel('imports')->error('Trendyol batch status check failed', [
                    'batch_request_id' => $batchRequestId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'status' => $response->status(),
                    'error' => $response->body(),
                ];
            }

            $data = $response->json();
            
            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (Exception $e) {
            Log::channel('imports')->error('Trendyol batch status check exception', [
                'batch_request_id' => $batchRequestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

