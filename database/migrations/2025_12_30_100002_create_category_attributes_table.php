<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('category_attributes')) {
            // Table already exists, modify it
            Schema::table('category_attributes', function (Blueprint $table) {
                // Drop old attribute_key column if it exists
                if (Schema::hasColumn('category_attributes', 'attribute_key')) {
                    $table->dropColumn('attribute_key');
                }
                
                // Add attribute_id column if it doesn't exist
                if (!Schema::hasColumn('category_attributes', 'attribute_id')) {
                    $table->unsignedBigInteger('attribute_id')->after('category_id');
                }
            });
            
            // Add unique constraint and foreign keys
            Schema::table('category_attributes', function (Blueprint $table) {
                // Check if unique constraint exists by trying to add it (will fail silently if exists)
                try {
                    $table->unique(['category_id', 'attribute_id'], 'category_attributes_category_id_attribute_id_unique');
                } catch (\Exception $e) {
                    // Constraint already exists, ignore
                }
            });
            
            // Add foreign key for attribute_id if it doesn't exist
            Schema::table('category_attributes', function (Blueprint $table) {
                try {
                    $table->foreign('attribute_id', 'fk_category_attributes_attribute_id')
                        ->references('id')
                        ->on('attributes')
                        ->onDelete('restrict');
                } catch (\Exception $e) {
                    // Foreign key already exists, ignore
                }
            });
        } else {
            // Table doesn't exist, create it
            Schema::create('category_attributes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('category_id');
                $table->unsignedBigInteger('attribute_id');
                $table->boolean('is_required')->default(false);
                $table->timestamps();

                $table->unique(['category_id', 'attribute_id']);

                $table->foreign('category_id', 'fk_category_attributes_category_id')
                    ->references('id')
                    ->on('categories')
                    ->onDelete('cascade');

                $table->foreign('attribute_id', 'fk_category_attributes_attribute_id')
                    ->references('id')
                    ->on('attributes')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_attributes');
    }
};

