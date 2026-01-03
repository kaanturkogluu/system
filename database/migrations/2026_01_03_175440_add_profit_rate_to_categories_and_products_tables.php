<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds profit_rate (kar oranı) to categories and products tables.
     * Özel kar oranı kategori bazlı tanımlanabilir, yoksa genel kar oranı kullanılır.
     */
    public function up(): void
    {
        // Categories tablosuna profit_rate ekle
        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('profit_rate', 5, 2)->nullable()->after('vat_rate')->comment('Kategori bazlı kar oranı (%)');
        });

        // Products tablosuna profit_rate ekle
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('profit_rate', 5, 2)->nullable()->after('vat_rate')->comment('Ürüne özel kar oranı (%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('profit_rate');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('profit_rate');
        });
    }
};
