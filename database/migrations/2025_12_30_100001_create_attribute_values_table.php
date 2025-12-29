<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('value');
            $table->string('normalized_value');
            $table->enum('status', ['active', 'passive'])->default('active');
            $table->timestamps();

            $table->unique(['attribute_id', 'normalized_value']);
            $table->index('status');

            $table->foreign('attribute_id', 'fk_attribute_values_attribute_id')
                ->references('id')
                ->on('attributes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};

