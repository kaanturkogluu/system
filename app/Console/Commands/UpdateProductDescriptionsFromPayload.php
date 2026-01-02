<?php

namespace App\Console\Commands;

use App\Models\ImportItem;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateProductDescriptionsFromPayload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-descriptions-from-payload 
                            {--dry-run : Sadece test et, değişiklik yapma}
                            {--limit= : İşlenecek maksimum kayıt sayısı}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mevcut ürünlerin description alanlarını import_items tablosundaki payload\'lardan "Detay" verisi ile günceller';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info('📦 Ürün description güncelleme işlemi başlatılıyor...');
        
        if ($isDryRun) {
            $this->warn('⚠️  DRY-RUN modu: Değişiklikler yapılmayacak, sadece test edilecek.');
        }

        // ImportItem'ları al (payload'ı olan tüm kayıtlar)
        $query = ImportItem::whereNotNull('payload');

        if ($limit) {
            $query->limit($limit);
        }

        $importItems = $query->get();
        $totalItems = $importItems->count();

        if ($totalItems === 0) {
            $this->warn('❌ İşlenecek import item bulunamadı.');
            return Command::FAILURE;
        }

        $this->info("📊 Toplam {$totalItems} import item bulundu.");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalItems);
        $progressBar->start();

        $updated = 0;
        $skipped = 0;
        $notFound = 0;
        $noDetay = 0;
        $noSku = 0;

        foreach ($importItems as $importItem) {
            try {
                $payload = $importItem->payload;
                
                if (empty($payload)) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Payload'dan "Detay" bilgisini al
                $detay = $payload['Detay'] ?? null;

                if (empty($detay) || trim($detay) === '') {
                    $noDetay++;
                    $progressBar->advance();
                    continue;
                }

                // Payload'dan SKU'yu çıkar (ImportItemJob'daki mantıkla aynı)
                $sku = $this->extractSkuFromPayload($payload, $importItem);

                if (empty($sku)) {
                    $noSku++;
                    $progressBar->advance();
                    continue;
                }

                // SKU'ya göre Product'ı bul
                $product = Product::where('source_type', 'xml')
                    ->where('sku', $sku)
                    ->first();

                // SKU ile bulunamazsa barcode ile dene
                if (!$product && !empty($payload['Barkod'])) {
                    $product = Product::where('source_type', 'xml')
                        ->where('barcode', $payload['Barkod'])
                        ->first();
                }

                // Hala bulunamazsa product_code ile dene
                if (!$product && !empty($importItem->product_code)) {
                    $product = Product::where('source_type', 'xml')
                        ->where('source_reference', $importItem->product_code)
                        ->first();
                }

                if (!$product) {
                    $notFound++;
                    $progressBar->advance();
                    continue;
                }

                // Description'ı güncelle (sadece boşsa veya dry-run değilse)
                if (!$isDryRun) {
                    $product->description = trim($detay);
                    $product->save();
                }

                $updated++;
                $progressBar->advance();

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Hata (ImportItem ID: {$importItem->id}): " . $e->getMessage());
                $skipped++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Özet
        $this->info('✅ İşlem tamamlandı!');
        $this->newLine();
            $this->table(
            ['Durum', 'Sayı'],
            [
                ['Güncellenen', $updated],
                ['Detay bulunamayan', $noDetay],
                ['SKU çıkarılamayan', $noSku],
                ['Ürün bulunamayan', $notFound],
                ['Atlanan', $skipped],
                ['Toplam', $totalItems],
            ]
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn('💡 Bu bir DRY-RUN idi. Gerçek güncelleme için --dry-run seçeneğini kaldırın.');
        }

        return Command::SUCCESS;
    }

    /**
     * Payload'dan SKU çıkar (ImportItemJob'daki mantıkla aynı)
     */
    private function extractSkuFromPayload(array $payload, ImportItem $importItem): ?string
    {
        // SKU adayları - hem nested hem de düz payload yapısını destekle
        $skuCandidates = [
            // İç içe yapı (product.*)
            $this->getNestedValue($payload, ['product', 'sku']),
            $this->getNestedValue($payload, ['product', 'stock_code']),
            $this->getNestedValue($payload, ['product', 'barcode']),
            $this->getNestedValue($payload, ['product', 'external_id']),
            
            // Düz yapı (XML'den gelen - Türkçe field isimleri)
            $payload['Kod'] ?? null,
            $payload['StokKodu'] ?? null,
            $payload['UrunKodu'] ?? null,
            $payload['Barkod'] ?? null,
            $payload['Barcode'] ?? null,
            
            // İngilizce field isimleri
            $payload['Sku'] ?? null,
            $payload['SKU'] ?? null,
            $payload['ProductCode'] ?? null,
            $payload['ProductId'] ?? null,
            $payload['Id'] ?? null,
            $payload['ExternalId'] ?? null,
            
            // ImportItem'daki SKU (eğer varsa)
            $importItem->sku,
        ];

        // Her adayı kontrol et
        foreach ($skuCandidates as $candidate) {
            if ($candidate !== null && trim($candidate) !== '') {
                $normalized = $this->normalizeSku($candidate);
                if ($normalized !== null && trim($normalized) !== '') {
                    return $normalized;
                }
            }
        }

        // Hiçbiri çalışmadıysa, fallback: GN-{feed_run_id}-{import_item_id}
        return sprintf('GN-%d-%d', $importItem->feed_run_id ?? 0, $importItem->id ?? 0);
    }

    /**
     * Nested array'den değer al
     */
    private function getNestedValue(array $array, array $keys): ?string
    {
        $current = $array;
        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }
        return is_string($current) || is_numeric($current) ? (string) $current : null;
    }

    /**
     * SKU'yu normalize et
     */
    private function normalizeSku(?string $sku): ?string
    {
        if ($sku === null) {
            return null;
        }

        $normalized = trim($sku);
        
        if (empty($normalized)) {
            return null;
        }

        // Maksimum uzunluk kontrolü
        if (strlen($normalized) > 100) {
            $normalized = substr($normalized, 0, 100);
        }

        return $normalized;
    }
}
