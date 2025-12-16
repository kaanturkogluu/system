<?php

namespace App\Jobs;

use App\Models\FeedRun;
use App\Services\FeedDownloaderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class DownloadFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Feed run ID
     *
     * @var int
     */
    public int $feedRunId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $feedRunId)
    {
        $this->feedRunId = $feedRunId;
        $this->onQueue('imports');
    }

    /**
     * Execute the job.
     */
    public function handle(FeedDownloaderService $downloader): void
    {
        $feedRun = FeedRun::findOrFail($this->feedRunId);

        $context = [
            'feed_id' => $feedRun->feed_source_id,
            'run_id' => $feedRun->id,
        ];

        try {
            Log::channel('imports')->info('DownloadFeedJob started', $context);

            $result = $downloader->download($feedRun);

            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Download failed');
            }

            if ($result['skipped']) {
                Log::channel('imports')->info('DownloadFeedJob completed - feed skipped', $context);
            } else {
                Log::channel('imports')->info('DownloadFeedJob completed successfully', array_merge($context, [
                    'file_path' => $result['file_path'],
                    'file_hash' => $result['file_hash'],
                    'file_size' => $result['file_size'],
                ]));
            }

        } catch (Exception $e) {
            $context['error'] = $e->getMessage();
            Log::channel('imports')->error('DownloadFeedJob failed', $context);

            // Update feed run status if not already updated
            $feedRun->refresh();
            if ($feedRun->status === 'RUNNING') {
                $feedRun->update([
                    'status' => 'FAILED',
                    'ended_at' => now(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        $feedRun = FeedRun::findOrFail($this->feedRunId);

        $context = [
            'feed_id' => $feedRun->feed_source_id,
            'run_id' => $feedRun->id,
            'error' => $exception?->getMessage(),
        ];

        Log::channel('imports')->error('DownloadFeedJob failed permanently', $context);

        $feedRun->refresh();
        if ($feedRun->status !== 'FAILED') {
            $feedRun->update([
                'status' => 'FAILED',
                'ended_at' => now(),
            ]);
        }
    }
}

