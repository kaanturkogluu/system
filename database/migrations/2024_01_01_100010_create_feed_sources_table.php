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
        Schema::create('feed_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('url', 500);
            $table->enum('type', ['xml', 'json', 'api'])->default('xml');
            $table->string('schedule', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_sources');
    }
};

