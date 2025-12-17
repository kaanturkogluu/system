<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('marketplace_id');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();

            $table->unique(['marketplace_id', 'key'], 'uq_marketplace_setting');
            $table->index('marketplace_id', 'idx_marketplace_id');

            $table->foreign('marketplace_id')
                ->references('id')
                ->on('marketplaces')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_settings');
    }
};

