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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 30)->comment('xml, manual, api');
            $table->string('source_reference', 100)->nullable();
            $table->string('sku', 100);
            $table->string('barcode', 50)->nullable();
            $table->string('title', 255);
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->enum('product_type', ['simple', 'variant_parent'])->default('simple');
            $table->decimal('reference_price', 12, 2)->nullable();
            $table->char('currency', 3)->default('TRY');
            $table->enum('status', ['DRAFT', 'IMPORTED', 'READY', 'EXPORTED', 'PASSIVE'])->default('IMPORTED');
            $table->json('raw_xml')->nullable();
            $table->timestamps();

            // Unique constraints
            $table->unique(['source_type', 'sku'], 'uq_source_sku');
            $table->unique('barcode', 'uq_barcode');

            // Indexes
            $table->index('category_id', 'idx_category');
            $table->index('brand_id', 'idx_brand');
            $table->index('status', 'idx_status');

            // Foreign keys
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('restrict');

            $table->foreign('brand_id')
                ->references('id')
                ->on('brands')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

