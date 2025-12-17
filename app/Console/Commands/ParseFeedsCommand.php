<?php

namespace App\Console\Commands;

use App\Models\ExternalCategory;
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

            // Kategori bilgisini çıkar ve external_categories'e kaydet
            $categoryRawPath = $this->extractCategoryPath($xml, $payload);
            $externalCategoryId = null;

            if ($categoryRawPath) {
                $sourceType = $this->getSourceType($feedRun);
                $externalId = $this->generateExternalCategoryId($sourceType . '|' . $categoryRawPath);
                $level = $this->calculateCategoryLevel($categoryRawPath);

                try {
                    $externalCategory = ExternalCategory::firstOrCreate(
                        [
                            'source_type' => $sourceType,
                            'external_id' => $externalId,
                        ],
                        [
                            'raw_path' => $categoryRawPath,
                            'level' => $level,
                        ]
                    );

                    $externalCategoryId = $externalCategory->id;

                    Log::channel('imports')->debug('External category created/retrieved', [
                        'feed_run_id' => $feedRun->id,
                        'external_category_id' => $externalCategoryId,
                        'source_type' => $sourceType,
                        'raw_path' => $categoryRawPath,
                        'level' => $level,
                    ]);
                } catch (\Exception $e) {
                    Log::channel('imports')->error('External category creation failed', [
                        'feed_run_id' => $feedRun->id,
                        'source_type' => $sourceType,
                        'raw_path' => $categoryRawPath,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::channel('imports')->warning('Category path not found in XML', [
                    'feed_run_id' => $feedRun->id,
                    'feed_id' => $feedRun->feed_source_id,
                    'xml_keys' => array_keys($payload),
                ]);
            }

            // Payload'a external_category_id ve raw_path ekle (root seviyesinde)
            $payload['external_category_id'] = $externalCategoryId;
            $payload['raw_path'] = $categoryRawPath;
            
            // Ayrıca category altına da ekle (geriye dönük uyumluluk için)
            if (!isset($payload['category'])) {
                $payload['category'] = [];
            }
            $payload['category']['external_category_id'] = $externalCategoryId;
            $payload['category']['raw_path'] = $categoryRawPath;

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
                'product_code' => $payload['Kod'] ?? null,
                'external_category_id' => $externalCategoryId,
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
     * Extract category path from XML
     */
    private function extractCategoryPath(\SimpleXMLElement $xml, array $payload): ?string
    {
        // 1) Önce payload'dan nested path'leri dene
        $possiblePaths = [
            ['CategoryPath'],
            ['Category', 'Path'],
            ['Category', 'CategoryPath'],
            ['ProductCategory', 'Path'],
            ['ProductCategory', 'CategoryPath'],
            ['Kategori', 'Path'],
            ['Kategori', 'KategoriYolu'],
            ['CategoryPath', 'Value'],
            ['Category', 'FullPath'],
        ];

        foreach ($possiblePaths as $path) {
            $value = $this->getNestedValue($payload, $path);
            if (!empty($value) && is_string($value)) {
                $normalized = $this->normalizeCategoryPath($value);
                if ($normalized) {
                    return $normalized;
                }
            }
        }

        // 2) XML'den direkt alanları dene
        $directFields = [
            'CategoryPath', 'Category', 'ProductCategory', 
            'Kategori', 'KategoriYolu', 'CategoryName',
            'CategoryFullPath', 'ProductCategoryPath'
        ];
        
        foreach ($directFields as $field) {
            $value = $this->extractValue($xml, [$field]);
            if (!empty($value)) {
                $normalized = $this->normalizeCategoryPath($value);
                if ($normalized) {
                    return $normalized;
                }
            }
        }

        // 3) Kategori hiyerarşisini oluşturmayı dene (Category1, Category2, Category3 gibi)
        $categoryHierarchy = $this->extractCategoryHierarchy($xml, $payload);
        if ($categoryHierarchy) {
            return $categoryHierarchy;
        }

        // 4) XML'deki tüm kategori benzeri alanları ara
        $categoryFields = $this->findCategoryFields($xml, $payload);
        if ($categoryFields) {
            return $categoryFields;
        }

        return null;
    }

    /**
     * Normalize category path string
     */
    private function normalizeCategoryPath(string $path): ?string
    {
        if (empty(trim($path))) {
            return null;
        }

        // Trim whitespace
        $path = trim($path);

        // Farklı separator'ları normalize et
        $path = str_replace(['/', '\\', '|', '::', '->'], '>', $path);
        
        // Birden fazla > karakterini tek > yap
        $path = preg_replace('/\s*>\s*>/', '>', $path);
        $path = preg_replace('/\s*>\s*/', ' > ', $path);
        
        // Her segment'i trim et
        $parts = explode('>', $path);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, function($part) {
            return !empty($part);
        });

        if (empty($parts)) {
            return null;
        }

        return implode(' > ', $parts);
    }

    /**
     * Extract category hierarchy from XML (Category1, Category2, Category3, etc.)
     */
    private function extractCategoryHierarchy(\SimpleXMLElement $xml, array $payload): ?string
    {
        $categories = [];

        // Payload'dan Category1, Category2, Category3 gibi alanları bul
        for ($i = 1; $i <= 10; $i++) {
            $categoryKey = "Category{$i}";
            $value = $this->getNestedValue($payload, [$categoryKey]);
            if (empty($value)) {
                $value = $this->extractValue($xml, [$categoryKey]);
            }
            
            if (!empty($value) && is_string($value)) {
                $categories[] = trim($value);
            } else {
                break;
            }
        }

        if (empty($categories)) {
            // Kategori1, Kategori2 gibi Türkçe alanları dene
            for ($i = 1; $i <= 10; $i++) {
                $categoryKey = "Kategori{$i}";
                $value = $this->getNestedValue($payload, [$categoryKey]);
                if (empty($value)) {
                    $value = $this->extractValue($xml, [$categoryKey]);
                }
                
                if (!empty($value) && is_string($value)) {
                    $categories[] = trim($value);
                } else {
                    break;
                }
            }
        }

        if (count($categories) > 0) {
            return implode(' > ', $categories);
        }

        return null;
    }

    /**
     * Find category fields in XML by searching for common patterns
     */
    private function findCategoryFields(\SimpleXMLElement $xml, array $payload): ?string
    {
        // XML'deki tüm child node'ları kontrol et
        $categoryKeywords = ['category', 'kategori', 'cat', 'path', 'yol'];
        
        foreach ($xml->children() as $child) {
            $name = strtolower($child->getName());
            $value = trim((string) $child);
            
            // Kategori ile ilgili bir alan mı?
            foreach ($categoryKeywords as $keyword) {
                if (strpos($name, $keyword) !== false && !empty($value)) {
                    $normalized = $this->normalizeCategoryPath($value);
                    if ($normalized && strlen($normalized) > 3) {
                        return $normalized;
                    }
                }
            }
        }

        // Payload'da kategori benzeri alanları ara
        foreach ($payload as $key => $value) {
            if (is_string($value) && !empty($value)) {
                $keyLower = strtolower($key);
                foreach ($categoryKeywords as $keyword) {
                    if (strpos($keyLower, $keyword) !== false) {
                        $normalized = $this->normalizeCategoryPath($value);
                        if ($normalized && strlen($normalized) > 3) {
                            return $normalized;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get source type from feed run
     */
    private function getSourceType(FeedRun $feedRun): string
    {
        $feedSource = $feedRun->feedSource;
        $sourceName = strtolower(str_replace(' ', '_', $feedSource->name ?? 'unknown'));
        return $sourceName . '_xml';
    }

    /**
     * Generate external category ID from raw path
     */
    private function generateExternalCategoryId(string $rawPath): string
    {
        return hash('sha256', $rawPath);
    }

    /**
     * Calculate category level from raw path
     */
    private function calculateCategoryLevel(string $rawPath): int
    {
        $parts = explode('>', $rawPath);
        $parts = array_filter(array_map('trim', $parts), function($part) {
            return !empty($part);
        });
        return count($parts);
    }

    /**
     * Get nested value from array using array path
     */
    private function getNestedValue(array $array, array $path): mixed
    {
        $current = $array;

        foreach ($path as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $current;
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

