<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds external_country_id column to store marketplace-specific country ID
     */
    public function up(): void
    {
        Schema::table('marketplace_country_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('external_country_id')->nullable()->after('country_id')
                ->comment('Marketplace-specific country ID (e.g., Trendyol attribute value ID)');
            
            $table->index('external_country_id', 'idx_marketplace_country_mappings_external_country_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_country_mappings', function (Blueprint $table) {
            $table->dropIndex('idx_marketplace_country_mappings_external_country_id');
            $table->dropColumn('external_country_id');
        });
    }
};
