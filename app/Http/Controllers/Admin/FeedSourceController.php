<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeedSourceController extends Controller
{
    /**
     * Display a listing of feed sources
     */
    public function index()
    {
        $feedSources = FeedSource::withCount('feedRuns')
            ->orderBy('name')
            ->paginate(20);
        
        return view('admin.feed-sources.index', compact('feedSources'));
    }

    /**
     * Show the form for creating a new feed source
     */
    public function create()
    {
        return view('admin.feed-sources.create');
    }

    /**
     * Store a newly created feed source
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'type' => 'required|in:xml,json,api',
            'schedule' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        FeedSource::create($validated);

        return redirect()->route('admin.feed-sources.index')
            ->with('success', 'Feed kaynağı başarıyla oluşturuldu.');
    }

    /**
     * Show the form for editing a feed source
     */
    public function edit(FeedSource $feedSource)
    {
        return view('admin.feed-sources.edit', compact('feedSource'));
    }

    /**
     * Update the specified feed source
     */
    public function update(Request $request, FeedSource $feedSource)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'type' => 'required|in:xml,json,api',
            'schedule' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $feedSource->update($validated);

        return redirect()->route('admin.feed-sources.index')
            ->with('success', 'Feed kaynağı başarıyla güncellendi.');
    }

    /**
     * Remove the specified feed source
     */
    public function destroy(FeedSource $feedSource)
    {
        $runCount = $feedSource->feedRuns()->count();
        
        if ($runCount > 0) {
            return redirect()->route('admin.feed-sources.index')
                ->with('error', 'Bu feed kaynağına ait ' . $runCount . ' çalıştırma kaydı bulunmaktadır. Önce kayıtları siliniz.');
        }

        $feedSource->delete();

        return redirect()->route('admin.feed-sources.index')
            ->with('success', 'Feed kaynağı başarıyla silindi.');
    }
}

