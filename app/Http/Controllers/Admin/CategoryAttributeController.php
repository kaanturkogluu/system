<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\CategoryAttribute;
use Illuminate\Http\Request;

class CategoryAttributeController extends Controller
{
    public function index(Request $request)
    {
        // Get category_id from request and ensure it's an integer
        $categoryId = $request->get('category_id');
        if ($categoryId) {
            $categoryId = (int) $categoryId;
        }
        
        $category = null;
        $categoryAttributes = collect();
        $categoryPath = [];

        if ($categoryId) {
            // Load category with all parents
            $category = Category::with('parent')->find($categoryId);
            
            if (!$category) {
                abort(404, 'Kategori bulunamadı: ' . $categoryId);
            }
            
            // Build category path (parent -> child hierarchy)
            $current = $category;
            while ($current) {
                array_unshift($categoryPath, $current);
                $current = $current->parent;
            }
            
            $categoryAttributes = CategoryAttribute::where('category_id', $categoryId)
                ->with('attribute')
                ->orderBy('is_required', 'desc')
                ->orderBy('id')
                ->get();
        }

        // Load all categories recursively
        $categories = Category::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
        
        // Recursively load all nested children
        $this->loadChildrenRecursively($categories);
        
        // Get search query
        $searchQuery = $request->get('search', '');

        $allAttributes = Attribute::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.category-attributes.index', compact(
            'categories',
            'category',
            'categoryAttributes',
            'allAttributes',
            'searchQuery',
            'categoryId',
            'categoryPath'
        ));
    }

    public function store(Request $request, Category $category)
    {
        $validated = $request->validate([
            'attribute_id' => 'required|exists:attributes,id',
            'is_required' => 'boolean',
        ]);

        CategoryAttribute::updateOrCreate(
            [
                'category_id' => $category->id,
                'attribute_id' => $validated['attribute_id'],
            ],
            [
                'is_required' => $validated['is_required'] ?? false,
            ]
        );

        return back()->with('success', 'Özellik kategoriye eklendi.');
    }

    public function update(Request $request, CategoryAttribute $categoryAttribute)
    {
        $validated = $request->validate([
            'is_required' => 'required|boolean',
        ]);

        $categoryAttribute->update($validated);

        return back()->with('success', 'Özellik güncellendi.');
    }

    public function destroy(CategoryAttribute $categoryAttribute)
    {
        $categoryAttribute->delete();

        return back()->with('success', 'Özellik kategoriden kaldırıldı.');
    }

    /**
     * Recursively load all children for categories
     */
    private function loadChildrenRecursively($categories)
    {
        foreach ($categories as $category) {
            if ($category->children->count() > 0) {
                $category->load(['children' => function ($query) {
                    $query->orderBy('name');
                }]);
                $this->loadChildrenRecursively($category->children);
            }
        }
    }
}

