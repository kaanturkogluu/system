@extends('admin.layouts.app')

@section('title', 'Batch Detayları')
@section('page-title', 'Batch Detayları')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Batch Detayları</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $product->title }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">SKU: {{ $product->sku }}</p>
        </div>
        <a 
            href="{{ route('admin.ready-products.index') }}"
            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition duration-200 flex items-center space-x-2"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Geri Dön</span>
        </a>
    </div>

    <!-- Product Info -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ürün Bilgileri</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-sm text-gray-600 dark:text-gray-400">Başlık:</span>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->title }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-600 dark:text-gray-400">SKU:</span>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->sku }}</p>
            </div>
            @if($product->category)
            <div>
                <span class="text-sm text-gray-600 dark:text-gray-400">Kategori:</span>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->category->name }}</p>
            </div>
            @endif
            @if($product->brand)
            <div>
                <span class="text-sm text-gray-600 dark:text-gray-400">Marka:</span>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->brand->name }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Batch Requests -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Batch İstekleri</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Toplam {{ $requests->count() }} istek kaydı</p>
        </div>

        @if($requests->isEmpty())
            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                Bu ürün için henüz batch isteği bulunmamaktadır.
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($requests as $index => $request)
                    <div class="p-6 {{ $index === 0 ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500' : '' }}">
                        @if($index === 0)
                            <div class="mb-3">
                                <span class="px-2 py-1 bg-blue-600 text-white text-xs font-semibold rounded">EN SON YANIT</span>
                            </div>
                        @endif

                        <!-- Request Info -->
                        <div class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                                İstek #{{ $requests->count() - $index }} - {{ $request->created_at->format('Y-m-d H:i:s') }}
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Durum:</span>
                                    <span class="ml-2 font-semibold 
                                        @if($request->status === 'success') text-green-600 dark:text-green-400
                                        @elseif($request->status === 'failed') text-red-600 dark:text-red-400
                                        @elseif($request->status === 'sent') text-yellow-600 dark:text-yellow-400
                                        @else text-gray-600 dark:text-gray-400
                                        @endif">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Batch ID:</span>
                                    <span class="ml-2 font-mono text-gray-900 dark:text-white text-xs">{{ $request->batch_request_id ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Ürün Sayısı:</span>
                                    <span class="ml-2 font-semibold text-gray-900 dark:text-white">{{ $request->items_count }}</span>
                                </div>
                                @if($request->success_count !== null)
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Başarılı:</span>
                                    <span class="ml-2 font-semibold text-green-600 dark:text-green-400">{{ $request->success_count }}</span>
                                </div>
                                @endif
                                @if($request->failed_count !== null)
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Başarısız:</span>
                                    <span class="ml-2 font-semibold text-red-600 dark:text-red-400">{{ $request->failed_count }}</span>
                                </div>
                                @endif
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Gönderim:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white text-xs">{{ $request->sent_at ? $request->sent_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Tamamlanma:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white text-xs">{{ $request->completed_at ? $request->completed_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Response Data -->
                        @if($request->response_data)
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-3">
                                <h5 class="text-xs font-semibold text-gray-900 dark:text-white mb-2">API Yanıtı (Response Data)</h5>
                                <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded overflow-x-auto max-h-60 overflow-y-auto">{{ json_encode($request->response_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif

                        <!-- Batch Status Data -->
                        @if($request->batch_status_data)
                            @php
                                // Description alanını "..." ile değiştir
                                $batchStatusData = $request->batch_status_data;
                                if (isset($batchStatusData['description'])) {
                                    $batchStatusData['description'] = '...';
                                }
                                // Items array'indeki description'ları da "..." ile değiştir
                                if (isset($batchStatusData['items']) && is_array($batchStatusData['items'])) {
                                    foreach ($batchStatusData['items'] as &$item) {
                                        if (isset($item['description'])) {
                                            $item['description'] = '...';
                                        }
                                    }
                                }
                            @endphp
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-3">
                                <h5 class="text-xs font-semibold text-gray-900 dark:text-white mb-2">Batch Durum Yanıtı (Batch Status Data)</h5>
                                <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded overflow-x-auto max-h-96 overflow-y-auto">{{ json_encode($batchStatusData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif

                        <!-- Request Data -->
                        @if($request->request_data)
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-3">
                                <h5 class="text-xs font-semibold text-gray-900 dark:text-white mb-2">Gönderilen Veri (Request Data)</h5>
                                <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded overflow-x-auto max-h-96 overflow-y-auto">{{ json_encode($request->request_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif

                        <!-- Error Message -->
                        @if($request->error_message)
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 border border-red-200 dark:border-red-800">
                                <h5 class="text-xs font-semibold text-red-900 dark:text-red-200 mb-1">Hata Mesajı</h5>
                                <p class="text-sm text-red-800 dark:text-red-300">{{ $request->error_message }}</p>
                            </div>
                        @endif

                        <!-- Refresh Button (only for latest request with batch_request_id) -->
                        @if($index === 0 && $request->batch_request_id)
                            <div class="mt-4">
                                <button 
                                    onclick="refreshBatchStatus('{{ $request->batch_request_id }}', '{{ $request->id }}')"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition duration-200 flex items-center space-x-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <span>Tekrar Sorgula</span>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
function refreshBatchStatus(batchRequestId, requestId) {
    if (!batchRequestId) {
        alert('Batch Request ID bulunamadı.');
        return;
    }

    const button = event.target.closest('button');
    const originalText = button.querySelector('span').textContent;
    const originalIcon = button.querySelector('svg').outerHTML;

    button.disabled = true;
    button.querySelector('span').textContent = 'Sorgulanıyor...';
    button.querySelector('svg').classList.add('animate-spin');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    
    fetch(`{{ route("admin.ready-products.batch-status", ":batchRequestId") }}`.replace(':batchRequestId', batchRequestId), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Batch durumu güncellendi!');
            window.location.reload();
        } else {
            alert('Hata: ' + (data.message || 'Batch durumu kontrol edilemedi.'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Batch durumu kontrol edilirken bir hata oluştu: ' + error.message);
    })
    .finally(() => {
        button.disabled = false;
        button.querySelector('span').textContent = originalText;
        button.querySelector('svg').classList.remove('animate-spin');
        button.querySelector('svg').outerHTML = originalIcon;
    });
}
</script>
@endsection

