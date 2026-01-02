<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the global shipping_companies reference table.
     * This table contains only global shipping company data.
     * Marketplace-specific data (tax numbers, external codes) are stored in marketplace_shipping_company_mappings.
     */
    public function up(): void
    {
        Schema::create('shipping_companies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique()->comment('Internal key: yurtici, aras, ptt, dhl, trendyol_express');
            $table->string('name', 255)->comment('Display name');
            $table->enum('status', ['active', 'passive'])->default('active')->comment('Shipping company status');
            $table->timestamps();

            // Indexes
            $table->index('code', 'idx_shipping_companies_code');
            $table->index('status', 'idx_shipping_companies_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_companies');
    }
};
