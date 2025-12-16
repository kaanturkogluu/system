@extends('admin.layouts.app')

@section('title', 'Feed Kaynakları')
@section('page-title', 'Feed Kaynakları')

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

    <!-- Header with Create Button -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Feed Kaynakları</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">XML/JSON/API feed kaynaklarını yönetin</p>
        </div>
        <a 
            href="{{ route('admin.feed-sources.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Feed Kaynağı
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Feed</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $feedSources->total() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Feed</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ \App\Models\FeedSource::where('is_active', true)->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Run</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ \App\Models\FeedRun::count() }}</p>
        </div>
    </div>

    <!-- Feed Sources Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Ad
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            URL
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Tip
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Durum
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Run Sayısı
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            İşlemler
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($feedSources as $feedSource)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $feedSource->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $feedSource->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                    <a href="{{ $feedSource->url }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ $feedSource->url }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 dark:bg-purple-900/20 text-purple-800 dark:text-purple-300 uppercase">
                                    {{ $feedSource->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($feedSource->is_active)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300">
                                        Aktif
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                        Pasif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300">
                                    {{ $feedSource->feed_runs_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    <a 
                                        href="{{ route('admin.feed-runs.index', ['feed_source_id' => $feedSource->id]) }}" 
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                    >
                                        Run'lar
                                    </a>
                                    <a 
                                        href="{{ route('admin.feed-sources.edit', $feedSource) }}" 
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                    >
                                        Düzenle
                                    </a>
                                    <form 
                                        method="POST" 
                                        action="{{ route('admin.feed-sources.destroy', $feedSource) }}"
                                        onsubmit="return confirm('Bu feed kaynağını silmek istediğinizden emin misiniz?');"
                                        class="inline"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                        >
                                            Sil
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Henüz feed kaynağı eklenmemiş.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($feedSources->hasPages())
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                {{ $feedSources->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

