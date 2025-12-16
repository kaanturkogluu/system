@extends('admin.layouts.app')

@section('title', 'Feed Run\'lar')
@section('page-title', 'Feed Run\'lar')

@section('content')
<div class="space-y-6">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Feed Run'lar</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Feed indirme çalıştırma kayıtları</p>
        </div>
        <form method="POST" action="{{ route('admin.feed-runs.trigger') }}" class="flex items-center space-x-3">
            @csrf
            <select 
                name="feed_source_id" 
                required
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
                <option value="">Feed Seçin</option>
                @foreach(\App\Models\FeedSource::where('is_active', true)->orderBy('name')->get() as $source)
                    <option value="{{ $source->id }}">{{ $source->name }}</option>
                @endforeach
            </select>
            <button 
                type="submit"
                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                İndir
            </button>
        </form>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.feed-runs.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="feed_source_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Feed Kaynağı
                </label>
                <select 
                    id="feed_source_id" 
                    name="feed_source_id"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Tümü</option>
                    @foreach($feedSources as $source)
                        <option value="{{ $source->id }}" {{ request('feed_source_id') == $source->id ? 'selected' : '' }}>
                            {{ $source->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Durum
                </label>
                <select 
                    id="status" 
                    name="status"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Tümü</option>
                    <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>Bekliyor</option>
                    <option value="RUNNING" {{ request('status') == 'RUNNING' ? 'selected' : '' }}>Çalışıyor</option>
                    <option value="DONE" {{ request('status') == 'DONE' ? 'selected' : '' }}>Tamamlandı</option>
                    <option value="SKIPPED" {{ request('status') == 'SKIPPED' ? 'selected' : '' }}>Atlandı</option>
                    <option value="FAILED" {{ request('status') == 'FAILED' ? 'selected' : '' }}>Başarısız</option>
                </select>
            </div>

            <div class="flex items-end">
                <button 
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                >
                    Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Toplam</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ \App\Models\FeedRun::count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Bekliyor</p>
            <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ \App\Models\FeedRun::where('status', 'PENDING')->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Tamamlandı</p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">{{ \App\Models\FeedRun::where('status', 'DONE')->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Atlandı</p>
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ \App\Models\FeedRun::where('status', 'SKIPPED')->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Başarısız</p>
            <p class="text-xl font-bold text-red-600 dark:text-red-400 mt-1">{{ \App\Models\FeedRun::where('status', 'FAILED')->count() }}</p>
        </div>
    </div>

    <!-- Feed Runs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Feed Kaynağı
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Durum
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Dosya Yolu
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Dosya Boyutu
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Hash
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Başlangıç
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Bitiş
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            İşlemler
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($feedRuns as $feedRun)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $feedRun->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $feedRun->feedSource->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $feedRun->feedSource->type }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($feedRun->status === 'PENDING')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300">
                                        Bekliyor
                                    </span>
                                @elseif($feedRun->status === 'RUNNING')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300">
                                        Çalışıyor
                                    </span>
                                @elseif($feedRun->status === 'DONE')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300">
                                        Tamamlandı
                                    </span>
                                @elseif($feedRun->status === 'SKIPPED')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-300">
                                        Atlanı
                                    </span>
                                @elseif($feedRun->status === 'FAILED')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300">
                                        Başarısız
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($feedRun->file_path)
                                    <div class="text-sm text-gray-900 dark:text-white max-w-xs truncate" title="{{ $feedRun->file_path }}">
                                        {{ $feedRun->file_path }}
                                    </div>
                                    @if($feedRun->file_exists ?? false)
                                        <a 
                                            href="{{ $feedRun->file_url }}" 
                                            target="_blank"
                                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                                        >
                                            İndir
                                        </a>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($feedRun->file_size)
                                    {{ number_format($feedRun->file_size / 1024, 2) }} KB
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($feedRun->file_hash)
                                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded max-w-xs truncate block" title="{{ $feedRun->file_hash }}">
                                        {{ substr($feedRun->file_hash, 0, 16) }}...
                                    </code>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $feedRun->started_at ? $feedRun->started_at->format('d.m.Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $feedRun->ended_at ? $feedRun->ended_at->format('d.m.Y H:i') : '-' }}
                                @if($feedRun->started_at && $feedRun->ended_at)
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $feedRun->started_at->diffForHumans($feedRun->ended_at, true) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a 
                                    href="{{ route('admin.feed-runs.show', $feedRun) }}" 
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                >
                                    Detay
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Henüz feed run kaydı bulunmuyor.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($feedRuns->hasPages())
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                {{ $feedRuns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

