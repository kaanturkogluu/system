<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Services\TrendyolCategoryAttributeService;
use Illuminate\Console\Command;

class ImportTrendyolCategoryAttributesCommand extends Command
{
    protected $signature = 'app:import-trendyol-category-attributes
                            {--category-id= : Import attributes for specific category ID}
                            {--all : Import attributes for all mapped categories}
                            {--limit= : Limit number of categories to process}';

    protected $description = 'Import Trendyol category attributes from API';

    private TrendyolCategoryAttributeService $service;

    public function __construct(TrendyolCategoryAttributeService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $this->info('🔄 Starting Trendyol category attributes import...');

        try {
            if ($this->option('category-id')) {
                // Import for specific category
                $categoryId = (int) $this->option('category-id');
                $category = Category::find($categoryId);

                if (!$category) {
                    $this->error("❌ Category not found: {$categoryId}");
                    return Command::FAILURE;
                }

                $this->info("📦 Importing attributes for category: {$category->name} (ID: {$categoryId})");
                
                $stats = $this->service->importAttributesForCategory($categoryId);

                $this->displayStats($stats);

            } elseif ($this->option('all')) {
                // Import for all mapped categories
                $limit = $this->option('limit') ? (int) $this->option('limit') : null;
                
                $this->info('📦 Importing attributes for all mapped categories...');
                
                $stats = $this->service->importAttributesForAllMappedCategories($limit);

                $this->displayStats($stats);

            } else {
                $this->error('❌ Please specify --category-id or --all option');
                $this->info('');
                $this->info('Examples:');
                $this->info('  php artisan app:import-trendyol-category-attributes --category-id=1');
                $this->info('  php artisan app:import-trendyol-category-attributes --all');
                $this->info('  php artisan app:import-trendyol-category-attributes --all --limit=10');
                return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function displayStats(array $stats): void
    {
        $this->newLine();
        $this->info('✅ Import completed!');
        $this->newLine();
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Categories Processed', $stats['categories_processed'] ?? 0],
                ['Attributes Created', $stats['attributes_created'] ?? 0],
                ['Attributes Updated', $stats['attributes_updated'] ?? 0],
                ['Values Created', $stats['values_created'] ?? 0],
            ]
        );

        if (!empty($stats['errors'])) {
            $this->newLine();
            $this->error('⚠️  Errors occurred:');
            foreach ($stats['errors'] as $error) {
                $this->error('  - ' . $error);
            }
        }
    }
}

