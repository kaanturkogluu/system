<?php

namespace App\Console\Commands;

use App\Jobs\DownloadFeedJob;
use App\Models\FeedRun;
use App\Models\FeedSource;
use Illuminate\Console\Command;

class DownloadFeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feed:download 
                            {feed_id? : The ID of the feed source to download}
                            {--all : Download all active feeds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download XML feed from feed source';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->downloadAllActiveFeeds();
        }

        $feedId = $this->argument('feed_id');

        if (!$feedId) {
            $this->error('Please provide feed_id or use --all option');
            return Command::FAILURE;
        }

        return $this->downloadFeed($feedId);
    }

    /**
     * Download specific feed
     */
    private function downloadFeed(int $feedId): int
    {
        $feedSource = FeedSource::find($feedId);

        if (!$feedSource) {
            $this->error("Feed source with ID {$feedId} not found");
            return Command::FAILURE;
        }

        if (!$feedSource->is_active) {
            $this->warn("Feed source {$feedId} is not active");
            return Command::FAILURE;
        }

        // Create feed run
        $feedRun = FeedRun::create([
            'feed_source_id' => $feedSource->id,
            'status' => 'PENDING',
        ]);

        $this->info("Created feed run #{$feedRun->id} for feed source: {$feedSource->name}");

        // Dispatch job
        DownloadFeedJob::dispatch($feedRun->id);

        $this->info("Download job dispatched to queue: imports");

        return Command::SUCCESS;
    }

    /**
     * Download all active feeds
     */
    private function downloadAllActiveFeeds(): int
    {
        $feedSources = FeedSource::where('is_active', true)->get();

        if ($feedSources->isEmpty()) {
            $this->warn('No active feed sources found');
            return Command::FAILURE;
        }

        $this->info("Found {$feedSources->count()} active feed source(s)");

        foreach ($feedSources as $feedSource) {
            $feedRun = FeedRun::create([
                'feed_source_id' => $feedSource->id,
                'status' => 'PENDING',
            ]);

            DownloadFeedJob::dispatch($feedRun->id);

            $this->info("Dispatched download job for feed: {$feedSource->name} (Run #{$feedRun->id})");
        }

        $this->info("All download jobs dispatched to queue: imports");

        return Command::SUCCESS;
    }
}

