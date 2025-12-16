<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedRun;
use App\Models\FeedSource;
use App\Jobs\DownloadFeedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FeedRunController extends Controller
{
    /**
     * Display a listing of feed runs
     */
    public function index(Request $request)
    {
        $query = FeedRun::with('feedSource');

        // Filter by feed source
        if ($request->has('feed_source_id') && $request->feed_source_id) {
            $query->where('feed_source_id', $request->feed_source_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $feedRuns = $query->latest('id')->paginate(30);
        $feedSources = FeedSource::orderBy('name')->get();

        // Add file_exists check for each feed run
        $feedRuns->getCollection()->transform(function ($feedRun) {
            $feedRun->file_exists = $feedRun->file_path ? Storage::exists($feedRun->file_path) : false;
            $feedRun->file_url = $feedRun->file_path && Storage::exists($feedRun->file_path) 
                ? Storage::url($feedRun->file_path) 
                : null;
            return $feedRun;
        });

        return view('admin.feed-runs.index', compact('feedRuns', 'feedSources'));
    }

    /**
     * Show the specified feed run
     */
    public function show(FeedRun $feedRun)
    {
        $feedRun->load('feedSource');
        $feedRun->file_exists = $feedRun->file_path ? Storage::exists($feedRun->file_path) : false;
        $feedRun->file_url = $feedRun->file_path && Storage::exists($feedRun->file_path) 
            ? Storage::url($feedRun->file_path) 
            : null;
        return view('admin.feed-runs.show', compact('feedRun'));
    }

    /**
     * Trigger download for a feed source
     */
    public function triggerDownload(Request $request)
    {
        $request->validate([
            'feed_source_id' => 'required|exists:feed_sources,id',
        ]);

        $feedSource = FeedSource::findOrFail($request->feed_source_id);

        if (!$feedSource->is_active) {
            return redirect()->route('admin.feed-runs.index')
                ->with('error', 'Feed kaynağı aktif değil.');
        }

        // Create feed run
        $feedRun = FeedRun::create([
            'feed_source_id' => $feedSource->id,
            'status' => 'PENDING',
        ]);

        // Dispatch job
        DownloadFeedJob::dispatch($feedRun->id);

        return redirect()->route('admin.feed-runs.index')
            ->with('success', "Feed indirme işlemi başlatıldı. Run ID: {$feedRun->id}");
    }
}

