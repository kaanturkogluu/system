<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('normalized_name', 150)->nullable()->after('name');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('slug');
            
            $table->unique('normalized_name', 'uq_brands_normalized_name');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique('uq_brands_normalized_name');
            $table->dropColumn(['normalized_name', 'status']);
        });
    }
};

