<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates product_attributes table for storing product attribute values.
     * 
     * BEHAVIORAL RULES:
     * - product_attributes is the SINGLE source of truth for product attributes
     * - No JSON attribute storage
     * - No auto-creation of attributes or enum values
     * - Safety over automation
     */
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('attribute_id');
            $table->string('value_string')->nullable()->comment('For string data_type');
            $table->decimal('value_number', 15, 4)->nullable()->comment('For number data_type');
            $table->unsignedBigInteger('attribute_value_id')->nullable()->comment('For enum data_type - FK to attribute_values.id');
            $table->timestamps();

            // Unique constraint: one attribute per product
            $table->unique(['product_id', 'attribute_id'], 'uq_product_attribute');

            // Foreign keys
            $table->foreign('product_id', 'fk_product_attributes_product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('attribute_id', 'fk_product_attributes_attribute_id')
                ->references('id')
                ->on('attributes')
                ->onDelete('restrict');

            $table->foreign('attribute_value_id', 'fk_product_attributes_attribute_value_id')
                ->references('id')
                ->on('attribute_values')
                ->onDelete('restrict');

            // Indexes
            $table->index('product_id', 'idx_product_attributes_product_id');
            $table->index('attribute_id', 'idx_product_attributes_attribute_id');
            $table->index('attribute_value_id', 'idx_product_attributes_attribute_value_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};

