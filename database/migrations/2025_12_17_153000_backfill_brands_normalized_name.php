<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $brands = DB::table('brands')->whereNull('normalized_name')->get();
        
        foreach ($brands as $brand) {
            $normalizedName = mb_strtolower($brand->name, 'UTF-8');
            $normalizedName = preg_replace('/[^a-z0-9]/', '', $normalizedName);
            
            if (empty($normalizedName)) {
                $normalizedName = 'brand_' . $brand->id;
            }
            
            $counter = 1;
            $originalNormalizedName = $normalizedName;
            
            while (DB::table('brands')
                ->where('normalized_name', $normalizedName)
                ->where('id', '!=', $brand->id)
                ->exists()) {
                $normalizedName = $originalNormalizedName . '_' . $counter;
                $counter++;
            }
            
            DB::table('brands')
                ->where('id', $brand->id)
                ->update(['normalized_name' => $normalizedName]);
        }
    }

    public function down(): void
    {
        DB::table('brands')->update(['normalized_name' => null]);
    }
};

