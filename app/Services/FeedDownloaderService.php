<?php

namespace App\Services;

use App\Models\FeedRun;
use App\Models\FeedSource;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class FeedDownloaderService
{
    /**
     * Download XML feed and save to storage
     *
     * @param FeedRun $feedRun
     * @return array ['success' => bool, 'file_path' => string|null, 'file_hash' => string|null, 'file_size' => int|null, 'skipped' => bool]
     */
    public function download(FeedRun $feedRun): array
    {
        $feedSource = $feedRun->feedSource;
        $context = [
            'feed_id' => $feedSource->id,
            'run_id' => $feedRun->id,
        ];

        try {
            Log::channel('imports')->info('Feed download started', $context);

            // Update status to RUNNING
            $feedRun->update([
                'status' => 'RUNNING',
                'started_at' => now(),
            ]);

            // Check if feed source is active
            if (!$feedSource->is_active) {
                throw new Exception('Feed source is not active');
            }

            // Download file with memory-safe approach
            $tempFile = $this->downloadToTempFile($feedSource->url, $context);

            // Calculate hash
            $fileHash = $this->calculateFileHash($tempFile);
            $fileSize = filesize($tempFile);

            $context['file_hash'] = $fileHash;
            $context['file_size'] = $fileSize;

            Log::channel('imports')->info('File downloaded and hash calculated', $context);

            // Check if hash matches previous successful run
            $previousRun = FeedRun::where('feed_source_id', $feedSource->id)
                ->where('status', 'DONE')
                ->whereNotNull('file_hash')
                ->latest('id')
                ->first();

            if ($previousRun && $previousRun->file_hash === $fileHash) {
                // Same file, skip
                unlink($tempFile);
                
                $feedRun->update([
                    'status' => 'SKIPPED',
                    'ended_at' => now(),
                    'file_hash' => $fileHash,
                    'file_size' => $fileSize,
                ]);

                Log::channel('imports')->info('Feed skipped - same hash as previous run', $context);

                return [
                    'success' => true,
                    'file_path' => null,
                    'file_hash' => $fileHash,
                    'file_size' => $fileSize,
                    'skipped' => true,
                ];
            }

            // Save file to permanent location
            $filePath = $this->saveFile($feedSource->id, $tempFile, $context);

            // Update feed run
            $feedRun->update([
                'status' => 'DONE',
                'ended_at' => now(),
                'file_path' => $filePath,
                'file_hash' => $fileHash,
                'file_size' => $fileSize,
            ]);

            $context['file_path'] = $filePath;
            Log::channel('imports')->info('Feed download completed successfully', $context);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_hash' => $fileHash,
                'file_size' => $fileSize,
                'skipped' => false,
            ];

        } catch (Exception $e) {
            $context['error'] = $e->getMessage();
            Log::channel('imports')->error('Feed download failed', $context);

            $feedRun->update([
                'status' => 'FAILED',
                'ended_at' => now(),
            ]);

            return [
                'success' => false,
                'file_path' => null,
                'file_hash' => null,
                'file_size' => null,
                'skipped' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Download file to temporary location (memory-safe)
     *
     * @param string $url
     * @param array $context
     * @return string Temporary file path
     * @throws Exception
     */
    private function downloadToTempFile(string $url, array $context): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'feed_download_');

        try {
            $context['url'] = $url;
            Log::channel('imports')->debug('Starting file download with Laravel Http Client', $context);

            // Use Laravel Http Client with sink to write directly to file (memory-safe)
            $maxSize = 1024 * 1024 * 1024; // 1GB limit
            
            try {
                $response = Http::timeout(300) // 5 minutes
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; FeedBot/1.0)',
                        'Accept' => 'application/xml,text/xml,*/*',
                    ])
                    ->withOptions([
                        'max_redirects' => 5,
                        'verify' => true, // SSL verification
                    ])
                    ->sink($tempFile) // Write directly to file, not RAM
                    ->throw() // Throw exception on HTTP errors
                    ->get($url);
            } catch (RequestException $e) {
                // Handle HTTP client exceptions
                $statusCode = $e->response ? $e->response->status() : 'unknown';
                throw new Exception("HTTP request failed with status {$statusCode}: {$url}. Error: " . $e->getMessage());
            } catch (\Exception $e) {
                // Handle other exceptions (timeout, SSL, etc.)
                throw new Exception("Download failed for {$url}: " . $e->getMessage());
            }

            // Check if request was successful (additional check)
            if (!$response->successful()) {
                $statusCode = $response->status();
                throw new Exception("HTTP request failed with status {$statusCode}: {$url}");
            }

            // Check file size
            if (!file_exists($tempFile)) {
                throw new Exception("Downloaded file does not exist");
            }

            $totalBytes = filesize($tempFile);

            if ($totalBytes === 0) {
                unlink($tempFile);
                throw new Exception("Downloaded file is empty");
            }

            if ($totalBytes > $maxSize) {
                unlink($tempFile);
                throw new Exception("File size exceeds maximum limit (1GB)");
            }

            $context['file_size'] = $totalBytes;
            Log::channel('imports')->debug('File download completed', $context);

            return $tempFile;

        } catch (Exception $e) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
            throw $e;
        }
    }

    /**
     * Calculate SHA-256 hash of file
     *
     * @param string $filePath
     * @return string
     */
    private function calculateFileHash(string $filePath): string
    {
        // Use hash_file for memory efficiency with large files
        return hash_file('sha256', $filePath);
    }

    /**
     * Save file to permanent storage location
     *
     * @param int $feedId
     * @param string $tempFile
     * @param array $context
     * @return string Storage path
     * @throws Exception
     */
    private function saveFile(int $feedId, string $tempFile, array $context): string
    {
        try {
            // Create directory structure: feeds/feed_{feed_id}/
            $directory = "feeds/feed_{$feedId}";
            Storage::makeDirectory($directory);

            // Generate unique filename with timestamp
            $filename = now()->format('Y-m-d_His') . '.xml';
            $filePath = "{$directory}/{$filename}";

            // Move file to storage (never overwrite - filename includes timestamp)
            Storage::putFileAs($directory, $tempFile, $filename);

            // Clean up temp file
            @unlink($tempFile);

            $context['file_path'] = $filePath;
            Log::channel('imports')->debug('File saved to storage', $context);

            return $filePath;

        } catch (Exception $e) {
            // Clean up temp file on error
            @unlink($tempFile);
            throw new Exception("Failed to save file to storage: " . $e->getMessage());
        }
    }
}

