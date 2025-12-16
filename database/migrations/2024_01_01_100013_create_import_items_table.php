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
        Schema::create('import_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feed_run_id');
            $table->string('external_id', 100)->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 50)->nullable();
            $table->json('payload');
            $table->char('hash', 64);
            $table->enum('status', ['PENDING', 'NORMALIZED', 'UPSERTED', 'NEEDS_MAPPING', 'SKIPPED', 'FAILED'])->default('PENDING');
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['feed_run_id', 'status'], 'idx_feed_status');
            $table->index('sku', 'idx_sku');
            $table->index('barcode', 'idx_barcode');

            // Foreign key
            $table->foreign('feed_run_id')
                ->references('id')
                ->on('feed_runs')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_items');
    }
};

