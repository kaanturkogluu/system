<?php

namespace App\Console\Commands;

use App\Models\FeedRun;
use App\Models\ImportItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use XMLReader;

class ParseFeedsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:feeds:parse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'İndirilmiş XML dosyalarını parse et ve import_items tablosuna yaz';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('XML parse işlemi başlatılıyor...');

        // DONE durumunda ve file_path'i olan feed_run'ları al
        $feedRuns = FeedRun::where('status', 'DONE')
            ->whereNotNull('file_path')
            ->with('feedSource')
            ->get();

        if ($feedRuns->isEmpty()) {
            $this->warn('Parse edilecek feed run bulunamadı.');
            return Command::SUCCESS;
        }

        $this->info("{$feedRuns->count()} feed run bulundu.");

        $parsedCount = 0;
        $failedCount = 0;

        foreach ($feedRuns as $feedRun) {
            try {
                $result = $this->parseFeedRun($feedRun);
                
                if ($result['success']) {
                    $this->info("Feed Run #{$feedRun->id} parse edildi - {$result['items_count']} item eklendi.");
                    $parsedCount++;
                } else {
                    $this->error("Feed Run #{$feedRun->id} parse edilemedi: {$result['error']}");
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Feed Run #{$feedRun->id} işlenirken hata: " . $e->getMessage());
                $this->markFeedRunAsFailed($feedRun, $e->getMessage());
                $failedCount++;
            }
        }

        $this->newLine();
        $this->info("İşlem tamamlandı:");
        $this->info("  - Parse edilen: {$parsedCount}");
        $this->info("  - Başarısız: {$failedCount}");

        return Command::SUCCESS;
    }

    /**
     * Parse a single feed run
     */
    private function parseFeedRun(FeedRun $feedRun): array
    {
        $filePath = $feedRun->file_path;

        // Dosya var mı kontrol et
        if (!Storage::exists($filePath)) {
            $error = "Dosya bulunamadı: {$filePath}";
            $this->markFeedRunAsFailed($feedRun, $error);
            return ['success' => false, 'error' => $error];
        }

        $fullPath = Storage::path($filePath);

        // XMLReader ile stream okuma
        $reader = new XMLReader();
        
        if (!$reader->open($fullPath)) {
            $error = "XML dosyası açılamadı: {$filePath}";
            $this->markFeedRunAsFailed($feedRun, $error);
            return ['success' => false, 'error' => $error];
        }

        $itemsCount = 0;
        $skippedCount = 0;

        try {
            // XMLUrunView node'larını bul
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'XMLUrunView') {
                    $xmlString = $reader->readOuterXML();
                    
                    if ($xmlString) {
                        $result = $this->processProductNode($feedRun, $xmlString);
                        
                        if ($result['inserted']) {
                            $itemsCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                }
            }

            // Parse işlemi başarılı, feed_run'ı güncelle
            $feedRun->update([
                'status' => 'PARSED',
                'ended_at' => now(),
            ]);

            Log::channel('imports')->info('Feed run parsed successfully', [
                'feed_run_id' => $feedRun->id,
                'feed_id' => $feedRun->feed_source_id,
                'file_path' => $filePath,
                'items_count' => $itemsCount,
                'skipped_count' => $skippedCount,
            ]);

            return [
                'success' => true,
                'items_count' => $itemsCount,
                'skipped_count' => $skippedCount,
            ];

        } catch (\Exception $e) {
            $this->markFeedRunAsFailed($feedRun, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        } finally {
            $reader->close();
        }
    }

    /**
     * Process a single XMLUrunView node
     */
    private function processProductNode(FeedRun $feedRun, string $xmlString): array
    {
        try {
            // XML'i SimpleXML ile parse et
            $xml = simplexml_load_string($xmlString);
            
            if ($xml === false) {
                Log::channel('imports')->warning('XML parse hatası', [
                    'feed_run_id' => $feedRun->id,
                    'feed_id' => $feedRun->feed_source_id,
                    'file_path' => $feedRun->file_path,
                ]);
                return ['inserted' => false, 'reason' => 'XML parse error'];
            }

            // Normalize payload oluştur
            $payload = $this->normalizePayload($xml);

            // Hash oluştur (unicode ve slash escape kapalı)
            $jsonString = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $hash = hash('sha256', $jsonString);

            // Aynı feed_run_id + hash kontrolü
            $existing = ImportItem::where('feed_run_id', $feedRun->id)
                ->where('hash', $hash)
                ->first();

            if ($existing) {
                return ['inserted' => false, 'reason' => 'Duplicate hash'];
            }

            // import_items tablosuna kayıt at
            ImportItem::create([
                'feed_run_id' => $feedRun->id,
                'external_id' => $this->extractValue($xml, ['ProductId', 'ExternalId', 'Id']),
                'sku' => $this->extractValue($xml, ['Sku', 'SKU', 'ProductCode']),
                'barcode' => $this->extractValue($xml, ['Barcode', 'Barkod', 'GTIN']),
                'payload' => $payload,
                'hash' => $hash,
                'status' => 'PENDING',
            ]);

            return ['inserted' => true];

        } catch (\Exception $e) {
            Log::channel('imports')->error('Product node işleme hatası', [
                'feed_run_id' => $feedRun->id,
                'feed_id' => $feedRun->feed_source_id,
                'file_path' => $feedRun->file_path,
                'error' => $e->getMessage(),
            ]);
            return ['inserted' => false, 'reason' => $e->getMessage()];
        }
    }

    /**
     * Normalize XML to canonical JSON payload
     */
    private function normalizePayload(\SimpleXMLElement $xml): array
    {
        $payload = [];

        // Tüm XML node'larını recursive olarak normalize et
        $this->normalizeNode($xml, $payload);

        return $payload;
    }

    /**
     * Recursive node normalization
     */
    private function normalizeNode(\SimpleXMLElement $node, array &$result): void
    {
        foreach ($node->children() as $child) {
            $name = $child->getName();
            $value = (string) $child;

            // Eğer child'ın kendi child'ları varsa, recursive devam et
            if ($child->children()->count() > 0) {
                $childArray = [];
                $this->normalizeNode($child, $childArray);
                
                // Aynı isimde birden fazla node varsa array olarak ekle
                if (isset($result[$name])) {
                    if (!is_array($result[$name]) || !isset($result[$name][0])) {
                        $result[$name] = [$result[$name]];
                    }
                    $result[$name][] = $childArray;
                } else {
                    $result[$name] = $childArray;
                }
            } else {
                // Leaf node - değeri ekle
                if (isset($result[$name])) {
                    // Aynı isimde birden fazla node varsa array yap
                    if (!is_array($result[$name])) {
                        $result[$name] = [$result[$name]];
                    }
                    $result[$name][] = $value;
                } else {
                    $result[$name] = $value;
                }
            }
        }
    }

    /**
     * Extract value from XML by trying multiple possible field names
     */
    private function extractValue(\SimpleXMLElement $xml, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            if (isset($xml->$name)) {
                $value = (string) $xml->$name;
                return !empty($value) ? $value : null;
            }
        }
        return null;
    }

    /**
     * Mark feed run as failed
     */
    private function markFeedRunAsFailed(FeedRun $feedRun, string $error): void
    {
        $feedRun->update([
            'status' => 'FAILED',
            'ended_at' => now(),
        ]);

        Log::channel('imports')->error('Feed run parse failed', [
            'feed_run_id' => $feedRun->id,
            'feed_id' => $feedRun->feed_source_id,
            'file_path' => $feedRun->file_path,
            'error' => $error,
        ]);
    }
}

