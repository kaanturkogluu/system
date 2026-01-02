<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds currency_id to products table.
     * Products store their native currency via currency_id.
     * Pricing logic will use currencies.rate_to_try for calculations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('currency')->comment('FK to currencies.id');
            
            $table->foreign('currency_id', 'fk_products_currency_id')
                ->references('id')
                ->on('currencies')
                ->onDelete('restrict');
            
            $table->index('currency_id', 'idx_products_currency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('fk_products_currency_id');
            $table->dropIndex('idx_products_currency_id');
            $table->dropColumn('currency_id');
        });
    }
};
