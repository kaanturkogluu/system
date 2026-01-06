<?php

namespace Database\Seeders;

use App\Models\FeedSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeedSourceSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Gunes XML feed kaynağını ekle
        $feedSource = FeedSource::firstOrCreate(
            ['url' => 'https://api.gunes.net/api/Urunler/XmlUrunListesi/17656'],
            [
                'name' => 'Gunes XML',
                'type' => 'xml',
                'schedule' => '0 2 * * * *',
                'is_active' => true,
            ]
        );

        if ($feedSource->wasRecentlyCreated) {
            $this->command->info('✅ Gunes XML feed kaynağı eklendi.');
        } else {
            $this->command->info('ℹ️  Gunes XML feed kaynağı zaten mevcut.');
        }
    }
}

