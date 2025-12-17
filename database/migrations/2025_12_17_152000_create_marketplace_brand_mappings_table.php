<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_brand_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('marketplace_id');
            $table->unsignedBigInteger('brand_id');
            $table->string('marketplace_brand_id', 100)->nullable();
            $table->string('marketplace_brand_name', 255)->nullable();
            $table->enum('status', ['mapped', 'pending', 'disabled'])->default('pending');
            $table->timestamps();

            $table->unique(['marketplace_id', 'brand_id'], 'uq_marketplace_brand');
            $table->index('marketplace_brand_id', 'idx_marketplace_brand_id');

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

    public function down(): void
    {
        Schema::dropIfExists('marketplace_brand_mappings');
    }
};

