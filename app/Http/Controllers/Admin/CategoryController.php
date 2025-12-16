<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

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
}

