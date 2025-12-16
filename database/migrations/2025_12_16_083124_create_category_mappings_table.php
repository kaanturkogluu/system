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
        Schema::create('category_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_category_id');
            $table->unsignedBigInteger('category_id');
            $table->enum('status', ['mapped', 'pending'])->default('pending');
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->timestamps();

            $table->unique('external_category_id', 'uq_external_category');
            $table->index('category_id', 'idx_category');
            $table->index('status', 'idx_status');

            $table->foreign('external_category_id')
                ->references('id')
                ->on('external_categories')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_mappings');
    }
};
