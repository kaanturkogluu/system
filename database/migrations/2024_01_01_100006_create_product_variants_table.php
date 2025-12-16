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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('sku', 100);
            $table->string('barcode', 50)->nullable();
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('TRY');
            $table->integer('stock')->default(0);
            $table->json('attributes')->nullable();
            $table->timestamps();

            // Unique constraints
            $table->unique(['product_id', 'sku'], 'uq_product_sku');
            $table->unique('barcode', 'uq_variant_barcode');

            // Foreign key
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

