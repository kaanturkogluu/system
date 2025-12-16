@extends('admin.layouts.app')

@section('title', 'Feed Run Detay')
@section('page-title', 'Feed Run Detay #' . $feedRun->id)

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a 
            href="{{ route('admin.feed-runs.index') }}"
            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 flex items-center"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Geri Dön
        </a>
    </div>

    <!-- Status Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Durum</h3>
            @if($feedRun->status === 'PENDING')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300">
                    Bekliyor
                </span>
            @elseif($feedRun->status === 'RUNNING')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300">
                    Çalışıyor
                </span>
            @elseif($feedRun->status === 'DONE')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300">
                    Tamamlandı
                </span>
            @elseif($feedRun->status === 'SKIPPED')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-indigo-100 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-300">
                    Atlanı
                </span>
            @elseif($feedRun->status === 'FAILED')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300">
                    Başarısız
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Feed Kaynağı</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">{{ $feedRun->feedSource->name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $feedRun->feedSource->url }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Oluşturulma</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">{{ $feedRun->created_at->format('d.m.Y H:i:s') }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $feedRun->created_at->diffForHumans() }}</p>
            </div>
        </div>
    </div>

    <!-- File Information -->
    @if($feedRun->file_path || $feedRun->file_hash)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Dosya Bilgileri</h3>
            <div class="space-y-4">
                @if($feedRun->file_path)
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Dosya Yolu</p>
                        <code class="text-sm bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded block break-all">{{ $feedRun->file_path }}</code>
                        @if($feedRun->file_exists ?? false)
                            <a 
                                href="{{ $feedRun->file_url }}" 
                                target="_blank"
                                class="mt-2 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Dosyayı İndir
                            </a>
                        @endif
                    </div>
                @endif

                @if($feedRun->file_size)
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Dosya Boyutu</p>
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ number_format($feedRun->file_size, 0, ',', '.') }} byte 
                            ({{ number_format($feedRun->file_size / 1024, 2, ',', '.') }} KB)
                            @if($feedRun->file_size > 1024 * 1024)
                                ({{ number_format($feedRun->file_size / (1024 * 1024), 2, ',', '.') }} MB)
                            @endif
                        </p>
                    </div>
                @endif

                @if($feedRun->file_hash)
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">SHA-256 Hash</p>
                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded block break-all font-mono">{{ $feedRun->file_hash }}</code>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Bu hash değeri ile önceki run'lar karşılaştırılır. Aynı hash varsa dosya atlanır.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Timing Information -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Zamanlama</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Başlangıç</p>
                <p class="text-sm text-gray-900 dark:text-white mt-1">
                    {{ $feedRun->started_at ? $feedRun->started_at->format('d.m.Y H:i:s') : '-' }}
                </p>
                @if($feedRun->started_at)
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $feedRun->started_at->diffForHumans() }}</p>
                @endif
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bitiş</p>
                <p class="text-sm text-gray-900 dark:text-white mt-1">
                    {{ $feedRun->ended_at ? $feedRun->ended_at->format('d.m.Y H:i:s') : '-' }}
                </p>
                @if($feedRun->ended_at)
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $feedRun->ended_at->diffForHumans() }}</p>
                @endif
            </div>
            @if($feedRun->started_at && $feedRun->ended_at)
                <div class="md:col-span-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Süre</p>
                    <p class="text-sm text-gray-900 dark:text-white mt-1">
                        {{ $feedRun->started_at->diffForHumans($feedRun->ended_at, true) }}
                        ({{ $feedRun->started_at->diffInSeconds($feedRun->ended_at) }} saniye)
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Skip Information -->
    @if($feedRun->status === 'SKIPPED')
        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-indigo-800 dark:text-indigo-200">
                        Bu run atlandı
                    </h3>
                    <p class="mt-2 text-sm text-indigo-700 dark:text-indigo-300">
                        İndirilen dosyanın hash değeri önceki başarılı run ile aynı olduğu için dosya parse edilmedi ve atlandı.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

