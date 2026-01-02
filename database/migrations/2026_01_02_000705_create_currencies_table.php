<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the currencies table for managing currency rates.
     * This is a LOCKED currency system where:
     * - All internal calculations are based on TRY
     * - TRY must always have rate_to_try = 1.000000
     * - Only one currency can be default (application-level guarantee)
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique()->comment('ISO currency code: TRY, USD, EUR, etc.');
            $table->string('name', 100)->comment('Currency name');
            $table->string('symbol', 10)->nullable()->comment('Currency symbol: ₺, $, €, etc.');
            $table->decimal('rate_to_try', 12, 6)->comment('Exchange rate to TRY. TRY = 1.000000');
            $table->boolean('is_default')->default(false)->comment('Default currency flag (only one can be true)');
            $table->enum('status', ['active', 'passive'])->default('active')->comment('Currency status');
            $table->timestamps();

            // Indexes
            $table->index('code', 'idx_currencies_code');
            $table->index('is_default', 'idx_currencies_is_default');
            $table->index('status', 'idx_currencies_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
