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
        Schema::create('feed_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feed_source_id');
            $table->enum('status', ['PENDING', 'RUNNING', 'DONE', 'PARSED', 'FAILED', 'SKIPPED'])->default('PENDING');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('feed_source_id')
                ->references('id')
                ->on('feed_sources')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_runs');
    }
};

