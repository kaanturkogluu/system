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
        Schema::table('import_items', function (Blueprint $table) {
            $table->string('product_code', 100)->nullable()->after('external_id');
            $table->unsignedBigInteger('external_category_id')->nullable()->after('product_code');
            
            $table->index('product_code', 'idx_product_code');
            $table->index('external_category_id', 'idx_external_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_items', function (Blueprint $table) {
            $table->dropIndex('idx_product_code');
            $table->dropIndex('idx_external_category_id');
            $table->dropColumn(['product_code', 'external_category_id']);
        });
    }
};

