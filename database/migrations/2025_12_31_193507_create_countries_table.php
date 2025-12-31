<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the countries reference table for product origin.
     * This is a small, stable reference table for country codes and names.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique()->comment('ISO-2 country code (e.g., TR, CN, DE)');
            $table->string('name', 100)->comment('Country name');
            $table->enum('status', ['active', 'passive'])->default('active')->comment('Country status');
            $table->timestamps();

            // Indexes
            $table->index('code', 'idx_countries_code');
            $table->index('status', 'idx_countries_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
