@extends('admin.layouts.app')

@section('title', 'Yeni Özellik')
@section('page-title', 'Yeni Özellik Oluştur')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.attributes.store') }}">
            @csrf

            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Özellik Adı <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name') }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Kod <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="code" 
                            name="code" 
                            value="{{ old('code') }}"
                            required
                            pattern="^[a-z][a-z0-9_]*$"
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        >
                        <button type="button" onclick="generateCode()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                            Oluştur
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">snake_case format, oluşturulduktan sonra değiştirilemez</p>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="data_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Veri Tipi <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="data_type" 
                        name="data_type" 
                        required
                        onchange="toggleEnumValues()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="string" {{ old('data_type') == 'string' ? 'selected' : '' }}>String</option>
                        <option value="number" {{ old('data_type') == 'number' ? 'selected' : '' }}>Number</option>
                        <option value="enum" {{ old('data_type') == 'enum' ? 'selected' : '' }}>Enum</option>
                        <option value="boolean" {{ old('data_type') == 'boolean' ? 'selected' : '' }}>Boolean</option>
                    </select>
                    @error('data_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="enum-values-section" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Enum Değerleri
                    </label>
                    <div id="enum-values-container" class="space-y-2">
                        <div class="flex gap-2">
                            <input type="text" name="enum_values[0][value]" placeholder="Görünen Değer" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <input type="text" name="enum_values[0][normalized_value]" placeholder="Normalize Değer" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <button type="button" onclick="removeEnumValue(this)" class="px-4 py-2 bg-red-600 text-white rounded-lg">Sil</button>
                        </div>
                    </div>
                    <button type="button" onclick="addEnumValue()" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg">Değer Ekle</button>
                </div>

                <div>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="is_filterable" 
                            value="1"
                            {{ old('is_filterable') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Filtrelenebilir</span>
                    </label>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Durum <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="status" 
                        name="status" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="passive" {{ old('status') == 'passive' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                        Oluştur
                    </button>
                    <a href="{{ route('admin.attributes.index') }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold py-2 px-6 rounded-lg transition duration-200">
                        İptal
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function generateCode() {
    const name = document.getElementById('name').value;
    if (!name) return;
    
    let code = name.toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
    
    if (/^[0-9]/.test(code)) {
        code = 'attr_' + code;
    }
    
    document.getElementById('code').value = code;
}

function toggleEnumValues() {
    const dataType = document.getElementById('data_type').value;
    const section = document.getElementById('enum-values-section');
    section.style.display = dataType === 'enum' ? 'block' : 'none';
}

let enumValueIndex = 1;
function addEnumValue() {
    const container = document.getElementById('enum-values-container');
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input type="text" name="enum_values[${enumValueIndex}][value]" placeholder="Görünen Değer" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
        <input type="text" name="enum_values[${enumValueIndex}][normalized_value]" placeholder="Normalize Değer" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
        <button type="button" onclick="removeEnumValue(this)" class="px-4 py-2 bg-red-600 text-white rounded-lg">Sil</button>
    `;
    container.appendChild(div);
    enumValueIndex++;
}

function removeEnumValue(button) {
    button.parentElement.remove();
}

toggleEnumValues();
</script>
@endsection

