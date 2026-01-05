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
        Schema::create('trendyol_product_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->string('batch_request_id')->nullable()->index();
            $table->json('request_data')->nullable()->comment('Gönderilen ürün verileri');
            $table->json('response_data')->nullable()->comment('API\'den dönen yanıt');
            $table->json('batch_status_data')->nullable()->comment('Batch durum kontrolü yanıtı');
            $table->enum('status', ['pending', 'sent', 'success', 'failed', 'partial'])->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->integer('items_count')->default(0)->comment('Gönderilen ürün sayısı');
            $table->integer('success_count')->nullable()->comment('Başarılı ürün sayısı');
            $table->integer('failed_count')->nullable()->comment('Başarısız ürün sayısı');
            $table->timestamp('sent_at')->nullable()->comment('Gönderim zamanı');
            $table->timestamp('completed_at')->nullable()->comment('Tamamlanma zamanı');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendyol_product_requests');
    }
};
