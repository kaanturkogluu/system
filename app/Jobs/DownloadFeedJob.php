<?php

namespace App\Jobs;

use App\Models\FeedRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class DownloadFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;
    public int $feedRunId;

    public function __construct(int $feedRunId)
    {
        $this->feedRunId = $feedRunId;
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $feedRun = FeedRun::findOrFail($this->feedRunId);
        $feedSource = $feedRun->feedSource;

        try {
            $feedRun->update([
                'status' => 'RUNNING',
                'started_at' => now(),
            ]);

            if (!$feedSource->is_active) {
                throw new Exception('Feed source is not active');
            }

            $url = $feedSource->url;
            $tempFile = tempnam(sys_get_temp_dir(), 'feed_download_');

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; FeedBot/1.0)',
                    'Accept' => 'application/xml,text/xml,*/*',
                ])
                ->timeout(180)
                ->sink($tempFile)
                ->get($url);

                if (!$response->successful()) {
                    throw new Exception('HTTP STATUS: ' . $response->status());
                }
            } catch (Exception $e) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
                throw $e;
            }

            if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
                throw new Exception('Downloaded file is empty');
            }

            $fileHash = hash_file('sha256', $tempFile);
            $fileSize = filesize($tempFile);

            $previousRun = FeedRun::where('feed_source_id', $feedSource->id)
                ->where('status', 'DONE')
                ->whereNotNull('file_hash')
                ->latest('id')
                ->first();

            if ($previousRun && $previousRun->file_hash === $fileHash) {
                @unlink($tempFile);
                $feedRun->update([
                    'status' => 'SKIPPED',
                    'ended_at' => now(),
                    'file_hash' => $fileHash,
                    'file_size' => $fileSize,
                ]);
                return;
            }

            $directory = "feeds/feed_{$feedSource->id}";
            Storage::makeDirectory($directory);
            $filename = now()->format('Y-m-d_His') . '.xml';
            $filePath = "{$directory}/{$filename}";

            Storage::putFileAs($directory, $tempFile, $filename);
            @unlink($tempFile);

            $feedRun->update([
                'status' => 'DONE',
                'ended_at' => now(),
                'file_path' => $filePath,
                'file_hash' => $fileHash,
                'file_size' => $fileSize,
            ]);

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            
            Log::channel('imports')->error('DownloadFeedJob failed', [
                'feed_id' => $feedSource->id,
                'run_id' => $feedRun->id,
                'error' => $errorMessage,
            ]);

            $feedRun->update([
                'status' => 'FAILED',
                'ended_at' => now(),
            ]);

            throw $e;
        }
    }
}
