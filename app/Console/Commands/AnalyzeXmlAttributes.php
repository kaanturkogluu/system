<?php

namespace App\Console\Commands;

use App\Models\Attribute;
use App\Models\ImportItem;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AnalyzeXmlAttributes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xml:analyze-attributes 
                            {--limit=1000 : Maximum number of import items to analyze}
                            {--output=report : Output format: report, json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze XML attributes from import_items.payload (READ-ONLY analysis)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== XML Attribute Analysis (READ-ONLY) ===');
        $this->newLine();
        $this->warn('This is an analysis phase only. No database writes will be performed.');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $outputFormat = $this->option('output');

        // Phase 1: Discover XML attributes
        $this->info('Phase 1: Discovering XML attributes from import_items...');
        $xmlAttributes = $this->discoverXmlAttributes($limit);

        if (empty($xmlAttributes)) {
            $this->error('No XML attributes found in import_items.');
            return Command::FAILURE;
        }

        $this->info("Found " . count($xmlAttributes) . " unique XML attribute keys.");
        $this->newLine();

        // Phase 2: Normalization and matching
        $this->info('Phase 2: Normalizing and matching with global attributes...');
        $globalAttributes = Attribute::where('status', 'active')
            ->pluck('code', 'id')
            ->toArray();

        $analysis = $this->analyzeAndMatch($xmlAttributes, $globalAttributes);

        // Phase 3: Prepare mappings (but don't insert)
        $this->info('Phase 3: Preparing mapping data structures...');
        $mappings = $this->prepareMappings($analysis);

        // Output results
        if ($outputFormat === 'json') {
            $this->outputJson($analysis, $mappings);
        } else {
            $this->outputReport($analysis, $mappings);
        }

        $this->newLine();
        $this->info('Analysis complete. No database changes were made.');
        $this->warn('Review the mappings above and create them manually if approved.');

        return Command::SUCCESS;
    }

    /**
     * Phase 1: Discover XML attributes from import_items.payload
     */
    private function discoverXmlAttributes(int $limit): array
    {
        $xmlAttributes = [];
        $processed = 0;

        $importItems = ImportItem::whereNotNull('payload')
            ->limit($limit)
            ->get();

        $this->withProgressBar($importItems, function ($importItem) use (&$xmlAttributes, &$processed) {
            $payload = $importItem->payload;

            if (!is_array($payload)) {
                return;
            }

            // Extract ONLY from TeknikOzellikler structure
            // XML ürün özellikleri için sadece TeknikOzellikler tag'inde gelen veriler kullanılacak
            $this->extractTeknikOzellikler($payload, $xmlAttributes);

            $processed++;
        });

        $this->newLine(2);

        return $xmlAttributes;
    }

    /**
     * Extract attributes from TeknikOzellikler structure
     * Format: TeknikOzellikler.UrunTeknikOzellikler[].Ozellik = "Marka", Deger = "ZEBEX"
     */
    private function extractTeknikOzellikler(array $payload, array &$xmlAttributes): void
    {
        // Find TeknikOzellikler in payload
        $teknikOzellikler = $payload['TeknikOzellikler'] ?? null;

        if (!is_array($teknikOzellikler)) {
            return;
        }

        // Handle UrunTeknikOzellikler array
        $urunTeknikOzellikler = $teknikOzellikler['UrunTeknikOzellikler'] ?? null;

        if (is_array($urunTeknikOzellikler)) {
            // Check if it's a list (indexed array) or single object
            if (isset($urunTeknikOzellikler[0])) {
                // It's an array of objects
                foreach ($urunTeknikOzellikler as $item) {
                    if (is_array($item) && isset($item['Ozellik']) && isset($item['Deger'])) {
                        $ozellik = trim($item['Ozellik']);
                        $deger = $item['Deger'];

                        // Skip "Marka" attribute
                        if (strtolower($ozellik) === 'marka') {
                            continue;
                        }

                        if (empty($ozellik)) {
                            continue;
                        }

                        // Use Ozellik as the attribute key
                        if (!isset($xmlAttributes[$ozellik])) {
                            $xmlAttributes[$ozellik] = [
                                'xml_attribute_key' => $ozellik,
                                'example_values' => [],
                                'product_count' => 0,
                            ];
                        }

                        // Collect example values
                        if (count($xmlAttributes[$ozellik]['example_values']) < 5) {
                            $valueStr = $this->normalizeValue($deger);
                            if (!empty($valueStr) && !in_array($valueStr, $xmlAttributes[$ozellik]['example_values'])) {
                                $xmlAttributes[$ozellik]['example_values'][] = $valueStr;
                            }
                        }

                        $xmlAttributes[$ozellik]['product_count']++;
                    }
                }
            } else {
                // Single object
                if (isset($urunTeknikOzellikler['Ozellik']) && isset($urunTeknikOzellikler['Deger'])) {
                    $ozellik = trim($urunTeknikOzellikler['Ozellik']);
                    $deger = $urunTeknikOzellikler['Deger'];

                    // Skip "Marka" attribute
                    if (strtolower($ozellik) === 'marka') {
                        return;
                    }

                    if (!empty($ozellik)) {
                        if (!isset($xmlAttributes[$ozellik])) {
                            $xmlAttributes[$ozellik] = [
                                'xml_attribute_key' => $ozellik,
                                'example_values' => [],
                                'product_count' => 0,
                            ];
                        }

                        if (count($xmlAttributes[$ozellik]['example_values']) < 5) {
                            $valueStr = $this->normalizeValue($deger);
                            if (!empty($valueStr) && !in_array($valueStr, $xmlAttributes[$ozellik]['example_values'])) {
                                $xmlAttributes[$ozellik]['example_values'][] = $valueStr;
                            }
                        }

                        $xmlAttributes[$ozellik]['product_count']++;
                    }
                }
            }
        }
    }

    /**
     * Extract attributes from payload (handles nested structures)
     */
    private function extractAttributesFromPayload(array $payload, string $prefix = '', bool $skipTeknikOzellikler = false): array
    {
        $attributes = [];

        foreach ($payload as $key => $value) {
            // Skip system keys
            if ($this->isSystemKey($key)) {
                continue;
            }

            // Skip TeknikOzellikler structure (already processed separately)
            if ($skipTeknikOzellikler && $key === 'TeknikOzellikler') {
                continue;
            }

            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                // Recursively extract from nested arrays
                $nested = $this->extractAttributesFromPayload($value, $fullKey, $skipTeknikOzellikler);
                $attributes = array_merge($attributes, $nested);
            } else {
                // This is a leaf value - treat as attribute
                $attributes[$fullKey] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Check if key is a system key (not an attribute)
     */
    private function isSystemKey(string $key): bool
    {
        $systemKeys = [
            'external_id', 'external_category_id', 'raw_path',
            'sku', 'barcode', 'product_code', 'hash',
            'Ad', 'Title', 'ProductTitle', 'Description',
            'Marka', 'Brand', 'Category', 'category',
            'Fiyat', 'Price', 'Fiyat_SK', 'Fiyat_Bayi',
            'Miktar', 'Stock', 'Stok',
            'Kod', 'ProductId', 'Id',
        ];

        $normalizedKey = strtolower($key);
        foreach ($systemKeys as $systemKey) {
            if ($normalizedKey === strtolower($systemKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize value for display
     */
    private function normalizeValue($value): string
    {
        if (is_null($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $str = (string) $value;
        return mb_substr(trim($str), 0, 100); // Limit to 100 chars
    }

    /**
     * Phase 2: Analyze and match with global attributes
     */
    private function analyzeAndMatch(array $xmlAttributes, array $globalAttributes): array
    {
        $analysis = [];

        foreach ($xmlAttributes as $xmlKey => $data) {
            $suggestedCode = $this->suggestNormalizedCode($xmlKey);
            $match = $this->findMatchingAttribute($xmlKey, $suggestedCode, $globalAttributes);

            $analysis[] = [
                'xml_attribute_key' => $xmlKey,
                'suggested_global_code' => $suggestedCode,
                'matched_attribute_id' => $match['attribute_id'] ?? null,
                'matched_attribute_code' => $match['code'] ?? null,
                'confidence' => $match['confidence'] ?? 'LOW',
                'example_values' => $data['example_values'],
                'product_count' => $data['product_count'],
            ];
        }

        return $analysis;
    }

    /**
     * Suggest normalized code from XML key
     */
    private function suggestNormalizedCode(string $xmlKey): string
    {
        // Remove prefix if exists (e.g., "attributes.Ekran Boyutu" -> "Ekran Boyutu")
        $key = $xmlKey;
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $key = end($parts);
        }

        // Convert to snake_case, lowercase
        $code = Str::slug($key, '_');
        $code = strtolower($code);

        // Ensure starts with letter
        if (preg_match('/^[0-9]/', $code)) {
            $code = 'attr_' . $code;
        }

        return $code;
    }

    /**
     * Find matching global attribute
     */
    private function findMatchingAttribute(string $xmlKey, string $suggestedCode, array $globalAttributes): array
    {
        // Exact match on code
        foreach ($globalAttributes as $attributeId => $code) {
            if ($code === $suggestedCode) {
                return [
                    'attribute_id' => $attributeId,
                    'code' => $code,
                    'confidence' => 'HIGH',
                ];
            }
        }

        // Partial match (contains or is contained)
        foreach ($globalAttributes as $attributeId => $code) {
            if (str_contains($code, $suggestedCode) || str_contains($suggestedCode, $code)) {
                return [
                    'attribute_id' => $attributeId,
                    'code' => $code,
                    'confidence' => 'MEDIUM',
                ];
            }
        }

        // Similarity check (simple)
        $bestMatch = null;
        $bestScore = 0;

        foreach ($globalAttributes as $attributeId => $code) {
            similar_text($suggestedCode, $code, $score);
            if ($score > $bestScore && $score > 70) {
                $bestScore = $score;
                $bestMatch = [
                    'attribute_id' => $attributeId,
                    'code' => $code,
                    'confidence' => 'MEDIUM',
                ];
            }
        }

        if ($bestMatch) {
            return $bestMatch;
        }

        return ['confidence' => 'LOW'];
    }

    /**
     * Phase 3: Prepare mappings (but don't insert)
     */
    private function prepareMappings(array $analysis): array
    {
        $mappings = [];

        foreach ($analysis as $item) {
            if ($item['confidence'] === 'HIGH' && $item['matched_attribute_id']) {
                $mappings[] = [
                    'source_type' => 'xml',
                    'source_attribute_key' => $item['xml_attribute_key'],
                    'attribute_id' => $item['matched_attribute_id'],
                    'status' => 'active',
                    'confidence' => $item['confidence'],
                ];
            }
        }

        return $mappings;
    }

    /**
     * Output as report
     */
    private function outputReport(array $analysis, array $mappings): void
    {
        // Group by status
        $matched = [];
        $needsReview = [];
        $unmapped = [];

        foreach ($analysis as $item) {
            if ($item['confidence'] === 'HIGH' && $item['matched_attribute_id']) {
                $matched[] = $item;
            } elseif ($item['confidence'] === 'MEDIUM' || ($item['confidence'] === 'LOW' && $item['matched_attribute_id'])) {
                $needsReview[] = $item;
            } else {
                $unmapped[] = $item;
            }
        }

        // Matched
        if (!empty($matched)) {
            $this->info('=== MATCHED (HIGH CONFIDENCE) ===');
            $this->table(
                ['XML Key', 'Suggested Code', 'Matched Attribute', 'Products', 'Examples'],
                array_map(function ($item) {
                    return [
                        $item['xml_attribute_key'],
                        $item['suggested_global_code'],
                        $item['matched_attribute_code'] ?? '—',
                        $item['product_count'],
                        implode(', ', array_slice($item['example_values'], 0, 3)),
                    ];
                }, $matched)
            );
            $this->newLine();
        }

        // Needs Review
        if (!empty($needsReview)) {
            $this->warn('=== NEEDS REVIEW (MEDIUM/LOW CONFIDENCE) ===');
            $this->table(
                ['XML Key', 'Suggested Code', 'Matched Attribute', 'Confidence', 'Products', 'Examples'],
                array_map(function ($item) {
                    return [
                        $item['xml_attribute_key'],
                        $item['suggested_global_code'],
                        $item['matched_attribute_code'] ?? '—',
                        $item['confidence'],
                        $item['product_count'],
                        implode(', ', array_slice($item['example_values'], 0, 3)),
                    ];
                }, $needsReview)
            );
            $this->newLine();
        }

        // Unmapped
        if (!empty($unmapped)) {
            $this->error('=== UNMAPPED (NO MATCH FOUND) ===');
            $this->table(
                ['XML Key', 'Suggested Code', 'Products', 'Examples'],
                array_map(function ($item) {
                    return [
                        $item['xml_attribute_key'],
                        $item['suggested_global_code'],
                        $item['product_count'],
                        implode(', ', array_slice($item['example_values'], 0, 3)),
                    ];
                }, $unmapped)
            );
            $this->newLine();
        }

        // Summary
        $this->info('=== SUMMARY ===');
        $this->table(
            ['Status', 'Count'],
            [
                ['Matched (HIGH)', count($matched)],
                ['Needs Review', count($needsReview)],
                ['Unmapped', count($unmapped)],
                ['Total', count($analysis)],
            ]
        );

        // Prepared mappings
        if (!empty($mappings)) {
            $this->newLine();
            $this->info('=== PREPARED MAPPINGS (NOT INSERTED) ===');
            $this->warn('These mappings are ready but NOT inserted. Review and create manually.');
            $this->table(
                ['Source Key', 'Attribute ID', 'Attribute Code'],
                array_map(function ($mapping) {
                    return [
                        $mapping['source_attribute_key'],
                        $mapping['attribute_id'],
                        Attribute::find($mapping['attribute_id'])->code ?? '—',
                    ];
                }, $mappings)
            );
        }
    }

    /**
     * Output as JSON
     */
    private function outputJson(array $analysis, array $mappings): void
    {
        $output = [
            'analysis' => $analysis,
            'prepared_mappings' => $mappings,
            'summary' => [
                'total_xml_attributes' => count($analysis),
                'matched_high' => count(array_filter($analysis, fn($a) => $a['confidence'] === 'HIGH' && $a['matched_attribute_id'])),
                'needs_review' => count(array_filter($analysis, fn($a) => $a['confidence'] === 'MEDIUM' || ($a['confidence'] === 'LOW' && $a['matched_attribute_id']))),
                'unmapped' => count(array_filter($analysis, fn($a) => $a['confidence'] === 'LOW' && !$a['matched_attribute_id'])),
            ],
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
