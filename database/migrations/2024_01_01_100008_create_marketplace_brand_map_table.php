<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marketplace_brand_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('marketplace_id');
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('marketplace_brand_id');
            $table->timestamps();

            // Unique constraints
            $table->unique(['marketplace_id', 'marketplace_brand_id'], 'uq_market_brand');
            $table->unique(['marketplace_id', 'brand_id'], 'uq_global_brand');

            // Foreign keys
            $table->foreign('marketplace_id')
                ->references('id')
                ->on('marketplaces')
                ->onDelete('restrict');

            $table->foreign('brand_id')
                ->references('id')
                ->on('brands')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_brand_map');
    }
};

