<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates marketplace_country_mappings table for origin mapping during export.
     * 
     * BEHAVIORAL RULES:
     * - This table is used ONLY during marketplace export
     * - Maps internal country_id to marketplace-specific country codes/names
     * - No relation to attributes or attribute mappings
     * - Never reads origin from attributes during export
     * 
     * USAGE:
     * - During export: Product → Brand → Country → marketplace_country_mappings → external_country_code
     */
    public function up(): void
    {
        Schema::create('marketplace_country_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('marketplace_id')->comment('Reference to marketplaces.id');
            $table->unsignedBigInteger('country_id')->comment('Reference to countries.id');
            $table->string('external_country_code', 50)->nullable()->comment('Marketplace-specific country code');
            $table->string('external_country_name', 255)->nullable()->comment('Marketplace-specific country name');
            $table->enum('status', ['active', 'passive'])->default('active')->comment('Mapping status');
            $table->timestamps();

            // Unique constraint: one mapping per marketplace per country
            $table->unique(['marketplace_id', 'country_id'], 'uq_marketplace_country_mapping');

            // Foreign keys
            $table->foreign('marketplace_id', 'fk_marketplace_country_mappings_marketplace_id')
                ->references('id')
                ->on('marketplaces')
                ->onDelete('cascade');

            $table->foreign('country_id', 'fk_marketplace_country_mappings_country_id')
                ->references('id')
                ->on('countries')
                ->onDelete('restrict');

            // Indexes
            $table->index('marketplace_id', 'idx_marketplace_country_mappings_marketplace_id');
            $table->index('country_id', 'idx_marketplace_country_mappings_country_id');
            $table->index('status', 'idx_marketplace_country_mappings_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_country_mappings');
    }
};
