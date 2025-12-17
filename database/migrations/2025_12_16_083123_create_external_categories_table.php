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
        if (Schema::hasTable('external_categories')) {
            return;
        }

        Schema::create('external_categories', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 50);
            $table->string('external_id', 255);
            $table->text('raw_path');
            $table->unsignedTinyInteger('level')->default(0);
            $table->timestamps();

            $table->index(['source_type', 'external_id'], 'idx_source_external');
            $table->index('source_type', 'idx_source_type');
            $table->index('level', 'idx_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_categories');
    }
};
