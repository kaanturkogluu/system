<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('data_type', ['string', 'number', 'enum', 'boolean']);
            $table->boolean('is_filterable')->default(false);
            $table->enum('status', ['active', 'passive'])->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index('data_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};

