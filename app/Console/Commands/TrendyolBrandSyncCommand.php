<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Marketplace;
use App\Models\MarketplaceBrandSearchResult;
use App\Services\TrendyolBrandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrendyolBrandSyncCommand extends Command
{
    protected $signature = 'app:trendyol-brand-sync
                            {--limit= : Limit number of brands to process}
                            {--delay=1 : Delay between API calls in seconds}';

    protected $description = 'Sync brand search results from Trendyol API';

    private TrendyolBrandService $trendyolService;
    private ?Marketplace $trendyolMarketplace = null;

    public function __construct(TrendyolBrandService $trendyolService)
    {
        parent::__construct();
        $this->trendyolService = $trendyolService;
    }

    public function handle(): int
    {
        $this->info('🔄 Starting Trendyol Brand Sync...');

        // Get Trendyol marketplace
        $this->trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();

        if (!$this->trendyolMarketplace) {
            $this->error('❌ Trendyol marketplace not found');
            return Command::FAILURE;
        }

        // Fetch active brands
        $query = Brand::where('status', 'active');
        
        $limit = $this->option('limit');
        if ($limit) {
            $query->limit((int) $limit);
        }

        $brands = $query->get();

        if ($brands->isEmpty()) {
            $this->warn('⚠️  No active brands found');
            return Command::SUCCESS;
        }

        $this->info("📦 Found {$brands->count()} active brand(s)");

        $processed = 0;
        $skipped = 0;
        $failed = 0;
        $delay = (float) $this->option('delay');

        foreach ($brands as $brand) {
            // Check if search result already exists
            $existing = MarketplaceBrandSearchResult::where('marketplace_id', $this->trendyolMarketplace->id)
                ->where('brand_id', $brand->id)
                ->first();

            if ($existing) {
                $this->line("⏭️  Skipping {$brand->name} (already cached)");
                $skipped++;
                continue;
            }

            $this->line("🔍 Searching for brand: {$brand->name}");

            try {
                // Call Trendyol API
                $response = $this->trendyolService->searchBrandsByName($brand->name);

                if ($response === null) {
                    $this->warn("⚠️  Failed to get response for {$brand->name}");
                    $failed++;
                    continue;
                }

                // Store result (even if empty array)
                DB::transaction(function () use ($brand, $response) {
                    MarketplaceBrandSearchResult::create([
                        'marketplace_id' => $this->trendyolMarketplace->id,
                        'brand_id' => $brand->id,
                        'query_name' => $brand->name,
                        'response' => $response,
                    ]);
                });

                $resultCount = is_array($response) ? count($response) : 0;
                $this->info("✅ Saved {$brand->name} ({$resultCount} result(s))");
                $processed++;

                // Rate limiting
                if ($delay > 0 && $processed < $brands->count()) {
                    sleep((int) $delay);
                }

            } catch (\Exception $e) {
                Log::channel('imports')->error('Trendyol brand sync error', [
                    'brand_id' => $brand->id,
                    'brand_name' => $brand->name,
                    'error' => $e->getMessage(),
                ]);

                $this->error("❌ Error processing {$brand->name}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->line("   Processed: {$processed}");
        $this->line("   Skipped: {$skipped}");
        $this->line("   Failed: {$failed}");

        return Command::SUCCESS;
    }
}

