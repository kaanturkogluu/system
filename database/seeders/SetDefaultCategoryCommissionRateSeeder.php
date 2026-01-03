<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class SetDefaultCategoryCommissionRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mevcut kategorilere default %20 komisyon oranı atar (eğer null ise)
     */
    public function run(): void
    {
        $updated = Category::whereNull('commission_rate')
            ->update(['commission_rate' => 20.00]);
        
        $this->command->info("{$updated} kategoriye default %20 komisyon oranı atandı.");
    }
}
