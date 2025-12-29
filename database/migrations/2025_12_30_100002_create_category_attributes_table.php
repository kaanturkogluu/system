<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('attribute_id');
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->unique(['category_id', 'attribute_id']);

            $table->foreign('category_id', 'fk_category_attributes_category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');

            $table->foreign('attribute_id', 'fk_category_attributes_attribute_id')
                ->references('id')
                ->on('attributes')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_attributes');
    }
};

