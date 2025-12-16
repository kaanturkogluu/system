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
        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('marketplace_id');
            $table->unsignedBigInteger('marketplace_category_id');
            $table->unsignedBigInteger('marketplace_parent_id')->nullable();
            $table->string('name', 150);
            $table->unsignedTinyInteger('level')->nullable();
            $table->string('path', 500)->nullable();
            $table->unsignedBigInteger('global_category_id')->nullable();
            $table->boolean('is_mapped')->default(false);
            $table->timestamps();

            // Unique constraint: marketplace_id ve marketplace_category_id kombinasyonu unique
            $table->unique(['marketplace_id', 'marketplace_category_id'], 'uq_market_cat');
            
            // Indexes
            $table->index('global_category_id', 'idx_global');
            $table->index('marketplace_parent_id', 'idx_market_parent');

            // Foreign key constraints
            $table->foreign('marketplace_id', 'fk_mc_marketplace')
                ->references('id')
                ->on('marketplaces')
                ->onDelete('restrict');

            $table->foreign('global_category_id', 'fk_mc_global')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_categories');
    }
};

