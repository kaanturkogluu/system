<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds commission_rate to marketplace_categories table.
     * Pazaryeri kategori bazlı komisyon oranı.
     */
    public function up(): void
    {
        Schema::table('marketplace_categories', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->after('is_mapped')->comment('Pazaryeri kategori bazlı komisyon oranı (%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_categories', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
