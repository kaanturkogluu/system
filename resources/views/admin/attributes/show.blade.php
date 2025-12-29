@extends('admin.layouts.app')

@section('title', 'Özellik Detayı')
@section('page-title', $attribute->name)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.attributes.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
            ← Özelliklere Dön
        </a>
        <a href="{{ route('admin.attributes.edit', $attribute) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg">
            Düzenle
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Özellik Detayları</h3>
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">İsim</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $attribute->name }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Kod</dt>
                <dd class="mt-1 text-sm"><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $attribute->code }}</code></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Veri Tipi</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($attribute->data_type) }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Durum</dt>
                <dd class="mt-1 text-sm">
                    <span class="px-2 py-1 rounded-full {{ $attribute->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $attribute->status === 'active' ? 'Aktif' : 'Pasif' }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Filtrelenebilir</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $attribute->is_filterable ? 'Evet' : 'Hayır' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Kategoriler</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $attribute->categories_count }}</dd>
            </div>
        </dl>
    </div>

    @if($attribute->data_type === 'enum' && $attribute->values->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Enum Değerleri</h3>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left">Görünen Değer</th>
                    <th class="px-4 py-2 text-left">Normalize Değer</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attribute->values as $value)
                <tr>
                    <td class="px-4 py-2">{{ $value->value }}</td>
                    <td class="px-4 py-2"><code class="text-xs">{{ $value->normalized_value }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection

