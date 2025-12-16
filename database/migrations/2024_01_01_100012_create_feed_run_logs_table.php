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
        Schema::create('feed_run_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feed_run_id');
            $table->string('level', 20)->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

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
        Schema::dropIfExists('feed_run_logs');
    }
};

