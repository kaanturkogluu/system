<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds price fields (price_sk, price_bayi, price_ozel) and currency_id to product_variants table.
     * These fields store the original prices from XML feed.
     * price_ozel will be used to calculate the final sale price in TRY.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Fiyat alanları (XML'den gelen orijinal fiyatlar)
            $table->decimal('price_sk', 12, 6)->nullable()->after('price')->comment('Fiyat Satış Kuru (orijinal döviz)');
            $table->decimal('price_bayi', 12, 6)->nullable()->after('price_sk')->comment('Fiyat Bayi (orijinal döviz)');
            $table->decimal('price_ozel', 12, 6)->nullable()->after('price_bayi')->comment('Fiyat Özel (orijinal döviz) - satış fiyatı için kullanılacak');
            
            // Currency ID (products tablosundaki gibi)
            $table->unsignedBigInteger('currency_id')->nullable()->after('currency')->comment('FK to currencies.id');
            
            $table->foreign('currency_id', 'fk_product_variants_currency_id')
                ->references('id')
                ->on('currencies')
                ->onDelete('restrict');
            
            $table->index('currency_id', 'idx_product_variants_currency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign('fk_product_variants_currency_id');
            $table->dropIndex('idx_product_variants_currency_id');
            $table->dropColumn(['price_sk', 'price_bayi', 'price_ozel', 'currency_id']);
        });
    }
};
