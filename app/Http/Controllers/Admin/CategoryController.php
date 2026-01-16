<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Marketplace;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SoapClient;
use SoapFault;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');
        }

        // Filter by level
        if ($request->has('level') && $request->level !== '') {
            $query->where('level', $request->level);
        }

        // Filter by parent
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Order by
        $query->orderBy('level')->orderBy('name');

        $categories = $query->paginate(50);
        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories', 'parentCategories'));
    }

    /**
     * Download Trendyol categories from API and save to JSON file
     */
    public function downloadTrendyolCategories(Request $request)
    {
        try {
            $response = Http::get('https://apigw.trendyol.com/integration/product/product-categories');

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trendyol API\'den veri alınamadı. HTTP Status: ' . $response->status(),
                ], 400);
            }

            $jsonData = $response->json();

            if (empty($jsonData) || !isset($jsonData['categories'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geçersiz JSON yapısı veya kategoriler bulunamadı.',
                ], 400);
            }

            // Save to file
            $filePath = base_path('trendyol_categories.json');
            file_put_contents($filePath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            // Extract main categories (parentId is null)
            $mainCategories = array_filter($jsonData['categories'], function ($cat) {
                return ($cat['parentId'] ?? null) === null;
            });

            return response()->json([
                'success' => true,
                'message' => 'Kategoriler başarıyla indirildi ve kaydedildi.',
                'main_categories' => array_values($mainCategories),
                'total_categories' => count($jsonData['categories']),
            ]);
        } catch (\Exception $e) {
            Log::error('Trendyol categories download error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get main categories from downloaded JSON file
     */
    public function getMainCategories()
    {
        $filePath = base_path('trendyol_categories.json');

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'trendyol_categories.json dosyası bulunamadı. Önce kategorileri indirin.',
            ], 404);
        }

        $jsonData = json_decode(file_get_contents($filePath), true);

        if (empty($jsonData) || !isset($jsonData['categories'])) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz JSON yapısı.',
            ], 400);
        }

        // Extract main categories (parentId is null)
        $mainCategories = array_filter($jsonData['categories'], function ($cat) {
            return ($cat['parentId'] ?? null) === null;
        });

        return response()->json([
            'success' => true,
            'main_categories' => array_values($mainCategories),
        ]);
    }

    /**
     * Import selected categories from Trendyol
     */
    public function importCategories(Request $request)
    {
        $validated = $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'integer',
        ]);

        $filePath = base_path('trendyol_categories.json');

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'trendyol_categories.json dosyası bulunamadı.',
            ], 404);
        }

        $jsonData = json_decode(file_get_contents($filePath), true);

        if (empty($jsonData) || !isset($jsonData['categories'])) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz JSON yapısı.',
            ], 400);
        }

        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();

        if (!$trendyolMarketplace) {
            return response()->json([
                'success' => false,
                'message' => 'Trendyol pazaryeri bulunamadı.',
            ], 404);
        }

        $imported = 0;
        $updated = 0;
        $errors = [];

        try {
            DB::transaction(function () use ($jsonData, $validated, $trendyolMarketplace, &$imported, &$updated, &$errors) {
                foreach ($validated['category_ids'] as $categoryId) {
                    $categoryData = $this->findCategoryById($jsonData['categories'], $categoryId);

                    if (!$categoryData) {
                        $errors[] = "Kategori ID {$categoryId} bulunamadı.";
                        continue;
                    }

                    // Import category and subcategories recursively
                    $this->importCategoryRecursive(
                        $categoryData,
                        null, // parent_id
                        0, // level
                        null, // parent_path
                        $trendyolMarketplace->id,
                        null, // marketplace_parent_id
                        $imported,
                        $updated
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => "İşlem tamamlandı. {$imported} kategori eklendi, {$updated} kategori güncellendi.",
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            Log::error('Category import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find category by ID in nested structure
     */
    private function findCategoryById(array $categories, int $targetId): ?array
    {
        foreach ($categories as $category) {
            if (isset($category['id']) && $category['id'] === $targetId) {
                return $category;
            }

            if (!empty($category['subCategories'])) {
                $found = $this->findCategoryById($category['subCategories'], $targetId);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Import category and subcategories recursively
     */
    private function importCategoryRecursive(
        array $categoryData,
        ?int $parentId,
        int $level,
        ?string $parentPath,
        int $marketplaceId,
        ?int $marketplaceParentId,
        int &$imported,
        int &$updated
    ): void {
        $name = $categoryData['name'] ?? '';
        $slug = Str::slug($name, '-', 'tr');
        $trendyolCategoryId = $categoryData['id'] ?? null;

        if (empty($name) || !$trendyolCategoryId) {
            return;
        }

        // Check if global category exists
        $globalCategory = Category::where('slug', $slug)
            ->where('parent_id', $parentId)
            ->first();

        if ($globalCategory) {
            // Update name if different
            if ($globalCategory->name !== $name) {
                $globalCategory->update(['name' => $name]);
                $updated++;
            }
        } else {
            // Create new global category
            $globalCategory = Category::create([
                'parent_id' => $parentId,
                'level' => $level,
                'name' => $name,
                'slug' => $slug,
                'path' => null, // Will be updated below
                'is_leaf' => empty($categoryData['subCategories']),
                'is_active' => true,
            ]);
            $imported++;
        }

        // Update path
        $path = $this->buildPath($parentPath, $globalCategory->id);
        $globalCategory->update(['path' => $path]);

        // Build marketplace path
        $marketplacePath = $this->buildMarketplacePath($marketplaceParentId, $trendyolCategoryId, $marketplaceId);

        // Create or update marketplace category mapping
        $marketplaceCategory = MarketplaceCategory::updateOrCreate(
            [
                'marketplace_id' => $marketplaceId,
                'marketplace_category_id' => $trendyolCategoryId,
            ],
            [
                'marketplace_parent_id' => $marketplaceParentId,
                'name' => $name,
                'level' => $level,
                'path' => $marketplacePath,
                'global_category_id' => $globalCategory->id,
                'is_mapped' => true,
            ]
        );

        // Process subcategories recursively
        if (!empty($categoryData['subCategories'])) {
            foreach ($categoryData['subCategories'] as $subCategory) {
                $this->importCategoryRecursive(
                    $subCategory,
                    $globalCategory->id,
                    $level + 1,
                    $path,
                    $marketplaceId,
                    $trendyolCategoryId,
                    $imported,
                    $updated
                );
            }
        }
    }

    /**
     * Build category path
     */
    private function buildPath(?string $parentPath, int $id): string
    {
        return $parentPath ? $parentPath . '/' . $id : (string) $id;
    }

    /**
     * Build marketplace category path
     */
    private function buildMarketplacePath(?int $marketplaceParentId, int $categoryId, int $marketplaceId): string
    {
        if ($marketplaceParentId) {
            $parentCategory = MarketplaceCategory::where('marketplace_id', $marketplaceId)
                ->where('marketplace_category_id', $marketplaceParentId)
                ->first();

            if ($parentCategory) {
                // If parent has a path, append to it
                if ($parentCategory->path) {
                    return $parentCategory->path . '/' . $categoryId;
                }
                // If parent exists but no path, create path from parent ID
                return (string) $marketplaceParentId . '/' . $categoryId;
            }
        }

        return (string) $categoryId;
    }

    /**
     * Update category commission or VAT rate
     */
    public function updateRate(Request $request, Category $category)
    {
        $validated = $request->validate([
            'field' => 'required|in:commission_rate,vat_rate',
            'value' => 'nullable|numeric',
        ]);

        $field = $validated['field'];
        $value = $validated['value'] !== null && $validated['value'] !== '' 
            ? ($field === 'commission_rate' ? (float) $validated['value'] : (int) $validated['value'])
            : null;

        $category->update([$field => $value]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori oranı başarıyla güncellendi.',
        ]);
    }

    /**
     * Download N11 categories from SOAP API and save to JSON file
     */
    public function downloadN11Categories(Request $request)
    {
        try {
            // SOAP extension kontrolü
            if (!extension_loaded('soap') || !class_exists('SoapClient')) {
                return response()->json([
                    'success' => false,
                    'message' => 'SOAP extension yüklü değil. Lütfen PHP SOAP extension\'ını yükleyin. XAMPP için: php.ini dosyasında "extension=soap" satırının başındaki ";" işaretini kaldırın ve Apache\'yi yeniden başlatın.',
                ], 400);
            }

            // N11 pazaryerini bul
            $n11Marketplace = Marketplace::where('slug', 'n11')->first();
            
            if (!$n11Marketplace) {
                return response()->json([
                    'success' => false,
                    'message' => 'N11 pazaryeri bulunamadı. Lütfen önce pazaryeri kaydını oluşturun.',
                ], 400);
            }

            // Veritabanından N11 ayarlarını al (foreign key ile)
            $apiKeySetting = MarketplaceSetting::where('marketplace_id', $n11Marketplace->id)
                ->where('key', 'api_key')
                ->first();
            
            $apiSecretSetting = MarketplaceSetting::where('marketplace_id', $n11Marketplace->id)
                ->where('key', 'api_secret')
                ->first();

            $appKey = $apiKeySetting ? $apiKeySetting->value : null;
            $appSecret = $apiSecretSetting ? $apiSecretSetting->value : null;

            if (!$appKey || !$appSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'N11 API anahtarları yapılandırılmamış. Lütfen pazaryeri ayarlarından N11 api_key ve api_secret değerlerini ayarlayın.',
                ], 400);
            }

            // SOAP client oluştur
            $wsdl = 'https://api.n11.com/ws/CategoryService.wsdl';
            $soapClient = new SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]);

            // Ana kategorileri çek
            $topLevelResponse = $soapClient->GetTopLevelCategories([
                'auth' => [
                    'appKey' => $appKey,
                    'appSecret' => $appSecret,
                ],
            ]);

            if (!isset($topLevelResponse->result->status) || $topLevelResponse->result->status !== 'success') {
                $errorMessage = $topLevelResponse->result->errorMessage ?? 'Bilinmeyen hata';
                return response()->json([
                    'success' => false,
                    'message' => 'N11 API hatası: ' . $errorMessage,
                ], 400);
            }

            $allCategories = [];
            $topCategories = $topLevelResponse->categoryList->category ?? [];

            // Her ana kategori için recursive olarak alt kategorileri çek
            foreach ($topCategories as $topCategory) {
                $category = $this->fetchN11CategoryRecursive(
                    $soapClient,
                    $appKey,
                    $appSecret,
                    $topCategory->id,
                    $topCategory->name,
                    null
                );
                if ($category) {
                    $allCategories[] = $category;
                }
            }

            // JSON formatına dönüştür (Trendyol formatına benzer)
            $jsonData = [
                'categories' => $this->flattenN11Categories($allCategories),
            ];

            // Dosyaya kaydet
            $filePath = base_path('n11_categories.json');
            file_put_contents(
                $filePath,
                json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            // Ana kategorileri filtrele
            $mainCategories = array_filter($jsonData['categories'], function ($cat) {
                return ($cat['parentId'] ?? null) === null;
            });

            return response()->json([
                'success' => true,
                'message' => 'N11 kategorileri başarıyla indirildi ve kaydedildi.',
                'main_categories' => array_values($mainCategories),
                'total_categories' => count($jsonData['categories']),
            ]);

        } catch (SoapFault $e) {
            Log::error('N11 categories download SOAP error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SOAP hatası: ' . $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            Log::error('N11 categories download error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recursive olarak N11 kategorilerini çek
     */
    private function fetchN11CategoryRecursive($soapClient, $appKey, $appSecret, $categoryId, $categoryName, $parentId, $level = 0)
    {
        $category = [
            'id' => $categoryId,
            'name' => $categoryName,
            'parentId' => $parentId,
            'level' => $level,
            'subCategories' => [],
        ];

        try {
            // Alt kategorileri çek
            $subCategoriesResponse = $soapClient->GetSubCategories([
                'auth' => [
                    'appKey' => $appKey,
                    'appSecret' => $appSecret,
                ],
                'categoryId' => $categoryId,
            ]);

            if (isset($subCategoriesResponse->result->status) && $subCategoriesResponse->result->status === 'success') {
                $subCategories = $subCategoriesResponse->category ?? [];
                
                if (!is_array($subCategories)) {
                    $subCategories = [$subCategories];
                }

                foreach ($subCategories as $subCategory) {
                    $subCategoryData = $this->fetchN11CategoryRecursive(
                        $soapClient,
                        $appKey,
                        $appSecret,
                        $subCategory->id,
                        $subCategory->name,
                        $categoryId,
                        $level + 1
                    );
                    if ($subCategoryData) {
                        $category['subCategories'][] = $subCategoryData;
                    }
                }
            }
        } catch (SoapFault $e) {
            Log::warning('N11 subcategory fetch error', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);
            // Hata olsa bile kategoriyi döndür
        }

        return $category;
    }

    /**
     * N11 kategorilerini düzleştir (Trendyol formatına benzer)
     */
    private function flattenN11Categories($categories, $result = [])
    {
        foreach ($categories as $category) {
            $flatCategory = [
                'id' => $category['id'],
                'name' => $category['name'],
                'parentId' => $category['parentId'],
            ];

            $result[] = $flatCategory;

            // Alt kategorileri recursive olarak ekle
            if (!empty($category['subCategories'])) {
                $result = $this->flattenN11Categories($category['subCategories'], $result);
            }
        }

        return $result;
    }
}

