@extends('admin.layouts.app')

@section('title', 'Kategori Özellikleri')
@section('page-title', 'Kategori Özellikleri')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Category Tree -->
    <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <h3 class="font-semibold mb-4">Kategoriler</h3>
        <div class="space-y-1 max-h-96 overflow-y-auto">
            @foreach($categories as $cat)
                <a href="{{ route('admin.category-attributes.index', ['category_id' => $cat->id]) }}" 
                   class="block px-3 py-2 rounded {{ request('category_id') == $cat->id ? 'bg-blue-100 dark:bg-blue-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    {{ $cat->name }}
                </a>
                @foreach($cat->children as $child)
                    <a href="{{ route('admin.category-attributes.index', ['category_id' => $child->id]) }}" 
                       class="block px-3 py-2 rounded ml-4 {{ request('category_id') == $child->id ? 'bg-blue-100 dark:bg-blue-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        {{ $child->name }}
                    </a>
                @endforeach
            @endforeach
        </div>
    </div>

    <!-- Attribute List -->
    <div class="lg:col-span-3">
        @if($category)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Kategori: {{ $category->name }}</h3>
                    <button onclick="document.getElementById('add-attribute-modal').classList.remove('hidden')" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        Özellik Ekle
                    </button>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 dark:bg-green-900/20 border border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Özellik</th>
                            <th class="px-4 py-2 text-left">Tip</th>
                            <th class="px-4 py-2 text-left">Zorunlu</th>
                            <th class="px-4 py-2 text-left">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoryAttributes as $ca)
                            <tr>
                                <td class="px-4 py-2">{{ $ca->attribute->name }}</td>
                                <td class="px-4 py-2">{{ ucfirst($ca->attribute->data_type) }}</td>
                                <td class="px-4 py-2">
                                    <form method="POST" action="{{ route('admin.category-attributes.update', $ca) }}" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="is_required" value="{{ $ca->is_required ? '0' : '1' }}">
                                        <button type="submit" class="px-2 py-1 rounded {{ $ca->is_required ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $ca->is_required ? 'Evet' : 'Hayır' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-2">
                                    <form method="POST" action="{{ route('admin.category-attributes.destroy', $ca) }}" class="inline" onsubmit="return confirm('Bu özelliği kategoriden kaldırmak istediğinize emin misiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Kaldır</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500">Özellik atanmamış</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center text-gray-500">
                Bir kategori seçin
            </div>
        @endif
    </div>
</div>

<!-- Add Attribute Modal -->
@if($category)
<div id="add-attribute-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold mb-4">Özellik Ekle</h3>
        <form method="POST" action="{{ route('admin.category-attributes.store', $category) }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Özellik</label>
                    <select name="attribute_id" required class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        @foreach($allAttributes as $attr)
                            <option value="{{ $attr->id }}">{{ $attr->name }} ({{ $attr->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_required" value="1" class="rounded">
                        <span class="ml-2">Zorunlu</span>
                    </label>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Ekle</button>
                    <button type="button" onclick="document.getElementById('add-attribute-modal').classList.add('hidden')" class="flex-1 bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-lg">İptal</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

