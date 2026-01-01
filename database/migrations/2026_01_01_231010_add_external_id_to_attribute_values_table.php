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
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('external_id')->nullable()->after('id')
                ->comment('Trendyol attribute value ID');
            $table->index('external_id', 'idx_attribute_values_external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropIndex('idx_attribute_values_external_id');
            $table->dropColumn('external_id');
        });
    }
};
