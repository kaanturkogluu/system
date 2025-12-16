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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedTinyInteger('level')->default(0);
            $table->string('name', 150);
            $table->string('slug', 180);
            $table->string('path', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_leaf')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint: slug ve parent_id kombinasyonu unique olmalı
            $table->unique(['slug', 'parent_id'], 'uq_slug_parent');
            
            // Indexes
            $table->index('parent_id', 'idx_parent');
            $table->index('level', 'idx_level');
            $table->index('path', 'idx_path');

            // Foreign key constraint
            $table->foreign('parent_id', 'fk_categories_parent')
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
        Schema::dropIfExists('categories');
    }
};

