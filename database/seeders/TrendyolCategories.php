<?php

namespace Database\Seeders;

use App\Models\Marketplace;
use App\Models\Category;
use App\Models\MarketplaceCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrendyolCategories extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Trendyol marketplace kaydını kontrol et veya oluştur
        $marketplace = Marketplace::firstOrCreate(
            ['slug' => 'trendyol'],
            ['name' => 'Trendyol']
        );
    
        $json = json_decode(
            file_get_contents(base_path('trendyol_categories.json')),
            true
        );
    
        if (empty($json) || !isset($json['categories'])) {
            $this->command->error('❌ trendyol_categories.json dosyası bulunamadı veya geçersiz!');
            return;
        }
    
        $this->command->info('📦 Trendyol kategorileri aktarılıyor...');
    
        DB::transaction(function () use ($json, $marketplace) {
            $this->importTrendyolCategories(
                $json['categories'],
                $marketplace->id
            );
        });
    
        $this->command->info('✅ Trendyol kategorileri global sisteme aktarıldı.');
    }
    
    private function makeSlug(string $name): string
{
    return Str::slug($name, '-', 'tr');
}

private function buildPath(?string $parentPath, int $id): string
{
    return $parentPath ? $parentPath . '/' . $id : (string) $id;
}
private function importTrendyolCategories(
    array $categories,
    int $marketplaceId,
    ?int $marketplaceParentId = null,
    ?int $globalParentId = null,
    int $level = 0,
    ?string $globalPath = null
) {
    foreach ($categories as $cat) {

        /** 1️⃣ GLOBAL CATEGORY (var mı kontrol et) */
        $globalCategory = Category::where('slug', $this->makeSlug($cat['name']))
            ->where('parent_id', $globalParentId)
            ->first();

        if (!$globalCategory) {
            $globalCategory = Category::create([
                'parent_id' => $globalParentId,
                'level'     => $level,
                'name'      => $cat['name'],
                'slug'      => $this->makeSlug($cat['name']),
                'path'      => null, // birazdan set edilecek
                'is_leaf'   => empty($cat['subCategories']),
            ]);
        }

        /** 2️⃣ PATH UPDATE */
        $globalCategory->update([
            'path' => $this->buildPath($globalPath, $globalCategory->id)
        ]);

        /** 3️⃣ MARKETPLACE CATEGORY */
        $marketplaceCategory = MarketplaceCategory::create([
            'marketplace_id'          => $marketplaceId,
            'marketplace_category_id' => $cat['id'],
            'marketplace_parent_id'   => $marketplaceParentId,
            'name'                    => $cat['name'],
            'level'                   => $level,
            'path'                    => $this->buildPath(
                $marketplaceParentId ? MarketplaceCategory::where('marketplace_category_id', $marketplaceParentId)->value('path') : null,
                $cat['id']
            ),
            'global_category_id'      => $globalCategory->id,
            'is_mapped'               => 1
        ]);

        /** 4️⃣ RECURSIVE */
        if (!empty($cat['subCategories'])) {
            $this->importTrendyolCategories(
                $cat['subCategories'],
                $marketplaceId,
                $cat['id'],
                $globalCategory->id,
                $level + 1,
                $globalCategory->path
            );
        }
    }
}

}
