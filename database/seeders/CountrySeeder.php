<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['code' => 'TR', 'name' => 'Turkey', 'status' => 'active'],
            ['code' => 'CN', 'name' => 'China', 'status' => 'active'],
            ['code' => 'US', 'name' => 'United States', 'status' => 'active'],
            ['code' => 'TW', 'name' => 'Taiwan', 'status' => 'active'],
            ['code' => 'JP', 'name' => 'Japan', 'status' => 'active'],
            ['code' => 'KR', 'name' => 'South Korea', 'status' => 'active'],
            ['code' => 'DE', 'name' => 'Germany', 'status' => 'active'],
            ['code' => 'NL', 'name' => 'Netherlands', 'status' => 'active'],
            ['code' => 'CH', 'name' => 'Switzerland', 'status' => 'active'],
            ['code' => 'IT', 'name' => 'Italy', 'status' => 'active'],
            ['code' => 'GB', 'name' => 'United Kingdom', 'status' => 'active'],
            ['code' => 'FR', 'name' => 'France', 'status' => 'active'],
            ['code' => 'ES', 'name' => 'Spain', 'status' => 'active'],
            ['code' => 'IN', 'name' => 'India', 'status' => 'active'],
            ['code' => 'BR', 'name' => 'Brazil', 'status' => 'active'],
            ['code' => 'MX', 'name' => 'Mexico', 'status' => 'active'],
            ['code' => 'CA', 'name' => 'Canada', 'status' => 'active'],
            ['code' => 'AU', 'name' => 'Australia', 'status' => 'active'],
            ['code' => 'RU', 'name' => 'Russia', 'status' => 'active'],
            ['code' => 'SG', 'name' => 'Singapore', 'status' => 'active'],
        ];

        foreach ($countries as $countryData) {
            Country::firstOrCreate(
                ['code' => $countryData['code']],
                $countryData
            );
        }

        $this->command->info('✅ ' . count($countries) . ' ülke eklendi/güncellendi.');
    }
}
