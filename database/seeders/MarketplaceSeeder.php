<?php

namespace Database\Seeders;

use App\Models\Marketplace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarketplaceSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Trendyol pazaryerini ekle
        $marketplace = Marketplace::firstOrCreate(
            ['slug' => 'trendyol'],
            [
                'name' => 'Trendyol',
                'status' => 'active',
            ]
        );

        if ($marketplace->wasRecentlyCreated) {
            $this->command->info('✅ Trendyol pazaryeri eklendi.');
        } else {
            $this->command->info('ℹ️  Trendyol pazaryeri zaten mevcut.');
        }
    }
}

