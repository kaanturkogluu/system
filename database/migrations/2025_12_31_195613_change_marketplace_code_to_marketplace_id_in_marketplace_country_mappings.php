<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes marketplace_code column to marketplace_id with foreign key to marketplaces table.
     */
    public function up(): void
    {
        // Check if marketplace_code column exists
        if (Schema::hasColumn('marketplace_country_mappings', 'marketplace_code')) {
            // Step 1: Add new marketplace_id column (nullable first)
            Schema::table('marketplace_country_mappings', function (Blueprint $table) {
                $table->unsignedInteger('marketplace_id')->nullable()->after('id');
            });

            // Step 2: Migrate data from marketplace_code to marketplace_id
            // Map marketplace codes to IDs
            $marketplaceMap = DB::table('marketplaces')
                ->pluck('id', 'slug')
                ->toArray();

            // Update existing records
            $mappings = DB::table('marketplace_country_mappings')->get();
            foreach ($mappings as $mapping) {
                if (isset($marketplaceMap[$mapping->marketplace_code])) {
                    DB::table('marketplace_country_mappings')
                        ->where('id', $mapping->id)
                        ->update(['marketplace_id' => $marketplaceMap[$mapping->marketplace_code]]);
                }
            }

            // Step 3: Drop old unique constraint and indexes
            Schema::table('marketplace_country_mappings', function (Blueprint $table) {
                $table->dropUnique('uq_marketplace_country_mapping');
                $table->dropIndex('idx_marketplace_country_mappings_marketplace_code');
            });

            // Step 4: Drop marketplace_code column
            Schema::table('marketplace_country_mappings', function (Blueprint $table) {
                $table->dropColumn('marketplace_code');
            });

            // Step 5: Make marketplace_id not nullable and add constraints
            Schema::table('marketplace_country_mappings', function (Blueprint $table) {
                $table->unsignedInteger('marketplace_id')->nullable(false)->change();
            });

            // Step 6: Add new unique constraint and foreign key
            Schema::table('marketplace_country_mappings', function (Blueprint $table) {
                // Unique constraint: one mapping per marketplace per country
                $table->unique(['marketplace_id', 'country_id'], 'uq_marketplace_country_mapping');

                // Foreign key to marketplaces
                $table->foreign('marketplace_id', 'fk_marketplace_country_mappings_marketplace_id')
                    ->references('id')
                    ->on('marketplaces')
                    ->onDelete('cascade');

                // Index
                $table->index('marketplace_id', 'idx_marketplace_country_mappings_marketplace_id');
            });
        } else {
            // If marketplace_code doesn't exist, just add marketplace_id
            Schema::table('marketplace_country_mappings', function (Blueprint $table) {
                $table->unsignedInteger('marketplace_id')->after('id');

                // Unique constraint
                $table->unique(['marketplace_id', 'country_id'], 'uq_marketplace_country_mapping');

                // Foreign key
                $table->foreign('marketplace_id', 'fk_marketplace_country_mappings_marketplace_id')
                    ->references('id')
                    ->on('marketplaces')
                    ->onDelete('cascade');

                // Index
                $table->index('marketplace_id', 'idx_marketplace_country_mappings_marketplace_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if marketplace_id column exists
        if (Schema::hasColumn('marketplace_country_mappings', 'marketplace_id')) {
            // Step 1: Drop foreign key and constraints
            Schema::table('marketplace_country_mappings', function (Blueprint $table) {
                $table->dropForeign('fk_marketplace_country_mappings_marketplace_id');
                $table->dropUnique('uq_marketplace_country_mapping');
                $table->dropIndex('idx_marketplace_country_mappings_marketplace_id');
            });

            // Step 2: Add marketplace_code column
            Schema::table('marketplace_country_mappings', function (Blueprint $table) {
                $table->string('marketplace_code', 50)->nullable()->after('id');
            });

            // Step 3: Migrate data from marketplace_id to marketplace_code
            $marketplaceMap = DB::table('marketplaces')
                ->pluck('slug', 'id')
                ->toArray();

            $mappings = DB::table('marketplace_country_mappings')->get();
            foreach ($mappings as $mapping) {
                if (isset($marketplaceMap[$mapping->marketplace_id])) {
                    DB::table('marketplace_country_mappings')
                        ->where('id', $mapping->id)
                        ->update(['marketplace_code' => $marketplaceMap[$mapping->marketplace_id]]);
                }
            }

            // Step 4: Make marketplace_code not nullable and add constraints
            Schema::table('marketplace_country_mappings', function (Blueprint $table) {
                $table->string('marketplace_code', 50)->nullable(false)->change();
                
                // Unique constraint
                $table->unique(['marketplace_code', 'country_id'], 'uq_marketplace_country_mapping');
                
                // Index
                $table->index('marketplace_code', 'idx_marketplace_country_mappings_marketplace_code');
            });

            // Step 5: Drop marketplace_id column
            Schema::table('marketplace_country_mappings', function (Blueprint $table) {
                $table->dropColumn('marketplace_id');
            });
        }
    }
};
