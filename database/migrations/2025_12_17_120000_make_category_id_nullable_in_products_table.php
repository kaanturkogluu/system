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
        Schema::table('products', function (Blueprint $table) {
            // category_id foreign key'i kaldır
            $table->dropForeign(['category_id']);
            
            // category_id'yi nullable yap
            $table->unsignedBigInteger('category_id')->nullable()->change();
            
            // Foreign key'i tekrar ekle (nullable olarak)
            $table->foreign('category_id')
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
        Schema::table('products', function (Blueprint $table) {
            // Foreign key'i kaldır
            $table->dropForeign(['category_id']);
            
            // category_id'yi tekrar NOT NULL yap
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            
            // Foreign key'i tekrar ekle (restrict olarak)
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('restrict');
        });
    }
};

