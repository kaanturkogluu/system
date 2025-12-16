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
        Schema::create('marketplace_product_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('marketplace_id');
            $table->string('marketplace_product_id', 100)->nullable();
            $table->enum('status', ['PENDING', 'ACTIVE', 'PASSIVE', 'FAILED'])->default('PENDING');
            $table->timestamps();

            // Unique constraint
            $table->unique(['product_id', 'marketplace_id'], 'uq_product_market');

            // Foreign keys
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('marketplace_id')
                ->references('id')
                ->on('marketplaces')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_map');
    }
};

