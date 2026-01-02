<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Seeds currencies table with TRY and USD.
     * This seeder is idempotent and safe to re-run.
     */
    public function run(): void
    {
        $this->command->info('💰 Para birimleri ekleniyor...');

        // Ensure only one default currency
        // If setting a new default, unset others first
        $currencies = [
            [
                'code' => 'TRY',
                'name' => 'Türk Lirası',
                'symbol' => '₺',
                'rate_to_try' => 1.000000,
                'is_default' => true,
                'status' => 'active',
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'rate_to_try' => 0.000000, // Placeholder, to be updated
                'is_default' => false,
                'status' => 'active',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($currencies as $currencyData) {
            // If this currency is being set as default, unset other defaults first
            if ($currencyData['is_default']) {
                Currency::where('is_default', true)
                    ->where('code', '!=', $currencyData['code'])
                    ->update(['is_default' => false]);
            }

            $currency = Currency::updateOrCreate(
                ['code' => $currencyData['code']],
                $currencyData
            );

            if ($currency->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->command->info("✅ {$created} yeni para birimi oluşturuldu.");
        if ($updated > 0) {
            $this->command->info("🔄 {$updated} para birimi güncellendi.");
        }

        $this->command->newLine();
        $this->command->info('✅ Para birimleri başarıyla eklendi.');
    }
}
