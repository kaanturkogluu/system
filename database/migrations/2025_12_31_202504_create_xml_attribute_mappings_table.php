<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates xml_attribute_mappings table for mapping XML attribute keys to global attributes.
     * 
     * BEHAVIORAL RULES:
     * - This table stores mappings from XML source attributes to global attributes
     * - XML attributes are NOT canonical - they are data sources only
     * - Mappings must be created manually after analysis
     * - DO NOT auto-create mappings
     */
    public function up(): void
    {
        Schema::create('xml_attribute_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 30)->default('xml')->comment('Source type (xml, api, etc.)');
            $table->string('source_attribute_key', 255)->comment('Original XML attribute key');
            $table->unsignedBigInteger('attribute_id')->comment('Reference to attributes.id');
            $table->enum('status', ['active', 'passive'])->default('active')->comment('Mapping status');
            $table->timestamps();

            // Unique constraint: one mapping per source type and attribute key
            $table->unique(['source_type', 'source_attribute_key'], 'uq_xml_attribute_mapping');

            // Foreign key
            $table->foreign('attribute_id', 'fk_xml_attribute_mappings_attribute_id')
                ->references('id')
                ->on('attributes')
                ->onDelete('restrict');

            // Indexes
            $table->index('source_type', 'idx_xml_attribute_mappings_source_type');
            $table->index('source_attribute_key', 'idx_xml_attribute_mappings_source_key');
            $table->index('attribute_id', 'idx_xml_attribute_mappings_attribute_id');
            $table->index('status', 'idx_xml_attribute_mappings_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xml_attribute_mappings');
    }
};
