@extends('admin.layouts.app')

@section('title', 'Özellik Düzenle')
@section('page-title', 'Özellik Düzenle: ' . $attribute->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.attributes.update', $attribute) }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Özellik Adı <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name', $attribute->name) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Kod
                    </label>
                    <input 
                        type="text" 
                        id="code" 
                        value="{{ $attribute->code }}"
                        disabled
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400"
                    >
                    <p class="mt-1 text-sm text-gray-500">Kod oluşturulduktan sonra değiştirilemez</p>
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
                        <option value="string" {{ old('data_type', $attribute->data_type) == 'string' ? 'selected' : '' }}>String</option>
                        <option value="number" {{ old('data_type', $attribute->data_type) == 'number' ? 'selected' : '' }}>Number</option>
                        <option value="enum" {{ old('data_type', $attribute->data_type) == 'enum' ? 'selected' : '' }}>Enum</option>
                        <option value="boolean" {{ old('data_type', $attribute->data_type) == 'boolean' ? 'selected' : '' }}>Boolean</option>
                    </select>
                </div>

                @if($attribute->data_type === 'enum')
                <div id="enum-values-section">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Enum Değerleri
                    </label>
                    <div id="enum-values-container" class="space-y-2">
                        @foreach($attribute->allValues as $value)
                        <div class="flex gap-2">
                            <input type="hidden" name="enum_values[{{ $value->id }}][id]" value="{{ $value->id }}">
                            <input type="text" name="enum_values[{{ $value->id }}][value]" value="{{ $value->value }}" placeholder="Görünen Değer" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <input type="text" name="enum_values[{{ $value->id }}][normalized_value]" value="{{ $value->normalized_value }}" placeholder="Normalize Değer" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <select name="enum_values[{{ $value->id }}][status]" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="active" {{ $value->status == 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="passive" {{ $value->status == 'passive' ? 'selected' : '' }}>Pasif</option>
                            </select>
                            <button type="button" onclick="removeEnumValue(this)" class="px-4 py-2 bg-red-600 text-white rounded-lg">Sil</button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" onclick="addEnumValue()" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg">Değer Ekle</button>
                </div>
                @endif

                <div>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="is_filterable" 
                            value="1"
                            {{ old('is_filterable', $attribute->is_filterable) ? 'checked' : '' }}
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
                        <option value="active" {{ old('status', $attribute->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="passive" {{ old('status', $attribute->status) == 'passive' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                        Güncelle
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
let enumValueIndex = {{ $attribute->allValues->max('id') ?? 0 }} + 1;
function addEnumValue() {
    const container = document.getElementById('enum-values-container');
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input type="text" name="enum_values[new_${enumValueIndex}][value]" placeholder="Görünen Değer" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
        <input type="text" name="enum_values[new_${enumValueIndex}][normalized_value]" placeholder="Normalize Değer" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
        <button type="button" onclick="removeEnumValue(this)" class="px-4 py-2 bg-red-600 text-white rounded-lg">Sil</button>
    `;
    container.appendChild(div);
    enumValueIndex++;
}

function removeEnumValue(button) {
    button.parentElement.remove();
}

function toggleEnumValues() {
    const dataType = document.getElementById('data_type').value;
    const section = document.getElementById('enum-values-section');
    if (section) {
        section.style.display = dataType === 'enum' ? 'block' : 'none';
    }
}
</script>
@endsection

