<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds is_default column to marketplace_shipping_company_mappings table.
     * This allows marking a shipping company as default for a marketplace.
     */
    public function up(): void
    {
        Schema::table('marketplace_shipping_company_mappings', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('status')->comment('Mark as default shipping company for this marketplace');
        });

        // Add index for faster queries
        Schema::table('marketplace_shipping_company_mappings', function (Blueprint $table) {
            $table->index(['marketplace_id', 'is_default'], 'idx_marketplace_shipping_company_mappings_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_shipping_company_mappings', function (Blueprint $table) {
            $table->dropIndex('idx_marketplace_shipping_company_mappings_default');
            $table->dropColumn('is_default');
        });
    }
};
