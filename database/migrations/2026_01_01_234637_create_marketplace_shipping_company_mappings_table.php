<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the marketplace_shipping_company_mappings table.
     * This table stores marketplace-specific shipping company data:
     * - External IDs and codes from marketplace APIs
     * - Tax numbers (marketplace-specific)
     * - Marketplace-specific names
     * 
     * This table is NOT related to attributes or XML mappings.
     */
    public function up(): void
    {
        Schema::create('marketplace_shipping_company_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('marketplace_id')->comment('FK to marketplaces.id');
            $table->unsignedBigInteger('shipping_company_id')->comment('FK to shipping_companies.id');
            $table->integer('external_id')->nullable()->comment('Marketplace-side numeric ID');
            $table->string('external_code', 100)->nullable()->comment('Marketplace-side code (e.g., SENDEOMP, YKMP)');
            $table->string('external_name', 255)->nullable()->comment('Marketplace-side name (e.g., Kolay Gelsin Marketplace)');
            $table->string('tax_number', 50)->nullable()->comment('Tax number (marketplace-specific)');
            $table->enum('status', ['active', 'passive'])->default('active')->comment('Mapping status');
            $table->timestamps();

            // Foreign keys
            $table->foreign('marketplace_id', 'fk_marketplace_shipping_company_mappings_marketplace_id')
                ->references('id')
                ->on('marketplaces')
                ->onDelete('cascade');

            $table->foreign('shipping_company_id', 'fk_marketplace_shipping_company_mappings_shipping_company_id')
                ->references('id')
                ->on('shipping_companies')
                ->onDelete('restrict');

            // Unique constraints
            $table->unique(['marketplace_id', 'shipping_company_id'], 'uq_marketplace_shipping_company');
            $table->unique(['marketplace_id', 'external_code'], 'uq_marketplace_external_code');

            // Indexes
            $table->index('marketplace_id', 'idx_marketplace_shipping_company_mappings_marketplace_id');
            $table->index('shipping_company_id', 'idx_marketplace_shipping_company_mappings_shipping_company_id');
            $table->index('external_id', 'idx_marketplace_shipping_company_mappings_external_id');
            $table->index('status', 'idx_marketplace_shipping_company_mappings_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_shipping_company_mappings');
    }
};
