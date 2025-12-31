<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds origin_country_id to brands table.
     * 
     * BEHAVIORAL RULES:
     * 1) Product origin is derived as: Product → Brand → Country
     * 2) Brand is the authoritative source for product origin
     * 3) Products inherit origin from brand (never stored per product)
     * 4) Origin is NOT stored in products table
     * 5) Origin is NOT an attribute
     * 6) XML origin values:
     *    - May be used to SET brand.origin_country_id if empty
     *    - Must NOT be stored per product
     */
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedBigInteger('origin_country_id')->nullable()->after('status');
            
            $table->foreign('origin_country_id', 'fk_brands_origin_country_id')
                ->references('id')
                ->on('countries')
                ->onDelete('set null');
            
            $table->index('origin_country_id', 'idx_brands_origin_country_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropForeign('fk_brands_origin_country_id');
            $table->dropIndex('idx_brands_origin_country_id');
            $table->dropColumn('origin_country_id');
        });
    }
};
