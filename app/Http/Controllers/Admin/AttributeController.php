<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $query = Attribute::withCount(['values', 'categories']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('data_type') && $request->data_type !== '') {
            $query->where('data_type', $request->data_type);
        }

        $attributes = $query->orderBy('name')->paginate(25);

        return view('admin.attributes.index', compact('attributes'));
    }

    public function create()
    {
        return view('admin.attributes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:attributes,code|regex:/^[a-z][a-z0-9_]*$/',
            'data_type' => 'required|in:string,number,enum,boolean',
            'is_filterable' => 'boolean',
            'status' => 'required|in:active,passive',
        ]);

        $attribute = Attribute::create($validated);

        if ($validated['data_type'] === 'enum' && $request->has('enum_values')) {
            $this->saveEnumValues($attribute, $request->enum_values);
        }

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Özellik başarıyla oluşturuldu.');
    }

    public function show(Attribute $attribute)
    {
        $attribute->load(['values', 'categories']);
        return view('admin.attributes.show', compact('attribute'));
    }

    public function edit(Attribute $attribute)
    {
        $attribute->load('allValues');
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'data_type' => 'required|in:string,number,enum,boolean',
            'is_filterable' => 'boolean',
            'status' => 'required|in:active,passive',
        ]);

        $attribute->update($validated);

        if ($validated['data_type'] === 'enum' && $request->has('enum_values')) {
            $this->saveEnumValues($attribute, $request->enum_values);
        }

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Özellik başarıyla güncellendi.');
    }

    public function destroy(Attribute $attribute)
    {
        if ($attribute->categories()->count() > 0) {
            return back()->with('error', 'Bu özellik kategorilerde kullanıldığı için silinemez.');
        }

        $attribute->delete();
        return redirect()->route('admin.attributes.index')
            ->with('success', 'Özellik başarıyla silindi.');
    }

    private function saveEnumValues(Attribute $attribute, array $enumValues): void
    {
        $existingIds = [];

        foreach ($enumValues as $valueData) {
            if (isset($valueData['id']) && $valueData['id']) {
                $value = $attribute->allValues()->find($valueData['id']);
                if ($value) {
                    $value->update([
                        'value' => $valueData['value'],
                        'normalized_value' => $valueData['normalized_value'] ?? Str::slug($valueData['value'], '_'),
                        'status' => $valueData['status'] ?? 'active',
                    ]);
                    $existingIds[] = $value->id;
                }
            } else {
                if (empty($valueData['value'])) {
                    continue;
                }
                
                $normalized = $valueData['normalized_value'] ?? Str::slug($valueData['value'], '_');
                
                if (empty($normalized)) {
                    $normalized = Str::slug($valueData['value'], '_');
                }
                
                $baseNormalized = $normalized;
                $counter = 1;
                while ($attribute->allValues()->where('normalized_value', $normalized)->exists()) {
                    $normalized = $baseNormalized . '_' . $counter;
                    $counter++;
                }

                $newValue = $attribute->allValues()->create([
                    'value' => $valueData['value'],
                    'normalized_value' => $normalized,
                    'status' => $valueData['status'] ?? 'active',
                ]);
                $existingIds[] = $newValue->id;
            }
        }

        $attribute->allValues()
            ->whereNotIn('id', $existingIds)
            ->delete();
    }
}

