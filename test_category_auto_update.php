<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CategoryMapping;
use App\Models\Product;
use App\Models\Category;

echo "Testing Automatic Product Category Update\n";
echo str_repeat("=", 60) . "\n\n";

// Find a category mapping to test
$mapping = CategoryMapping::where('status', 'mapped')
    ->with(['category', 'externalCategory'])
    ->first();

if (!$mapping) {
    echo "No mapped category found. Create one first.\n";
    exit;
}

echo "Test Mapping:\n";
echo "  External Category ID: {$mapping->external_category_id}\n";
echo "  Category ID: {$mapping->category_id}\n";
echo "  Category Name: {$mapping->category->name}\n";
echo "  Is Leaf: " . ($mapping->category->is_leaf ? 'Yes' : 'No') . "\n\n";

// Count products that will be affected
$affectedCount = \DB::select("
    SELECT COUNT(*) as count
    FROM products p
    INNER JOIN import_items ii ON JSON_UNQUOTE(JSON_EXTRACT(ii.payload, '$.Kod')) = p.source_reference
    WHERE p.source_type = 'xml'
      AND p.category_id IS NULL
      AND JSON_EXTRACT(ii.payload, '$.external_category_id') = ?
", [$mapping->external_category_id])[0]->count ?? 0;

echo "Products without category (will be updated): {$affectedCount}\n\n";

if ($affectedCount > 0) {
    echo "Triggering update by touching the mapping...\n";
    $mapping->touch();
    
    echo "\nDone! Check logs/imports-*.log for results.\n";
    echo "Expected log entry: 'Products automatically updated with category'\n";
} else {
    echo "No products to update. They might already have categories assigned.\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "To reset test: UPDATE products SET category_id = NULL WHERE source_type = 'xml';\n";

