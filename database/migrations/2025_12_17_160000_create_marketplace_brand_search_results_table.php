<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_brand_search_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('marketplace_id');
            $table->unsignedBigInteger('brand_id');
            $table->string('query_name', 255);
            $table->json('response');
            $table->timestamps();

            $table->unique(['marketplace_id', 'brand_id'], 'uq_marketplace_brand_search');
            $table->index('marketplace_id', 'idx_marketplace_id');
            $table->index('brand_id', 'idx_brand_id');

            $table->foreign('marketplace_id')
                ->references('id')
                ->on('marketplaces')
                ->onDelete('cascade');

            $table->foreign('brand_id')
                ->references('id')
                ->on('brands')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_brand_search_results');
    }
};

