<?php

namespace App\Console\Commands;

use App\Models\FeedRun;
use App\Models\FeedSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

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
    protected $description = 'Download XML feed from feed source and create PENDING feed_run';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            if ($this->option('all')) {
                return $this->downloadAllActiveFeeds();
            }

            $feedId = $this->argument('feed_id');

            if (!$feedId) {
                $this->error('Please provide feed_id or use --all option');
                return Command::FAILURE;
            }

            return $this->downloadFeed($feedId);
        } catch (\Exception $e) {
            $this->error('Command failed: ' . $e->getMessage());
            Log::channel('imports')->error('DownloadFeedCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Download specific feed
     */
    private function downloadFeed(int $feedId): int
    {
        $feedSource = FeedSource::find($feedId);

        if (!$feedSource) {
            $this->error("Feed source with ID {$feedId} not found");
            Log::channel('imports')->error('Feed source not found', ['feed_id' => $feedId]);
            return Command::FAILURE;
        }

        if (!$feedSource->is_active) {
            $this->warn("Feed source {$feedId} is not active");
            Log::channel('imports')->warning('Feed source not active', ['feed_id' => $feedId]);
            return Command::FAILURE;
        }

        // Aynı feed_source_id için PENDING durumunda feed_run var mı kontrol et
        $pendingRun = FeedRun::where('feed_source_id', $feedSource->id)
            ->where('status', 'PENDING')
            ->first();

        if ($pendingRun) {
            $this->warn("Feed #{$feedSource->id} ({$feedSource->name}) already has PENDING run #{$pendingRun->id}");
            Log::channel('imports')->info('Feed skipped - already pending', [
                'feed_id' => $feedSource->id,
                'feed_name' => $feedSource->name,
                'pending_run_id' => $pendingRun->id,
            ]);
            return Command::SUCCESS;
        }

        // Feed dosyasını indir
        $result = $this->downloadFeedFile($feedSource);

        if ($result['skipped']) {
            $this->info("Feed #{$feedSource->id} ({$feedSource->name}) skipped - file unchanged (hash: {$result['file_hash']})");
            return Command::SUCCESS;
        }

        if (!$result['success']) {
            $this->error("Feed #{$feedSource->id} ({$feedSource->name}) download failed: {$result['error']}");
            Log::channel('imports')->error('Feed download failed', [
                'feed_id' => $feedSource->id,
                'feed_name' => $feedSource->name,
                'error' => $result['error'],
            ]);
            return Command::FAILURE;
        }

        // Yeni feed_run oluştur - status PENDING, started_at ve ended_at NULL
        $feedRun = FeedRun::create([
            'feed_source_id' => $feedSource->id,
            'status' => 'PENDING',
            'started_at' => null,
            'ended_at' => null,
            'file_path' => $result['file_path'],
            'file_hash' => $result['file_hash'],
            'file_size' => $result['file_size'],
        ]);

        $this->info("Feed #{$feedSource->id} ({$feedSource->name}) downloaded - Run #{$feedRun->id} (PENDING)");
        Log::channel('imports')->info('Feed downloaded and run created', [
            'feed_id' => $feedSource->id,
            'feed_name' => $feedSource->name,
            'run_id' => $feedRun->id,
            'file_path' => $result['file_path'],
            'file_hash' => $result['file_hash'],
            'file_size' => $result['file_size'],
        ]);

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

        $createdCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($feedSources as $feedSource) {
            try {
                // Aynı feed_source_id için PENDING durumunda feed_run var mı kontrol et
                $pendingRun = FeedRun::where('feed_source_id', $feedSource->id)
                    ->where('status', 'PENDING')
                    ->first();

                if ($pendingRun) {
                    $this->warn("Feed #{$feedSource->id} ({$feedSource->name}) skipped - already PENDING (Run #{$pendingRun->id})");
                    $skippedCount++;
                    continue;
                }

                // Feed dosyasını indir
                $result = $this->downloadFeedFile($feedSource);

                if ($result['skipped']) {
                    $this->info("Feed #{$feedSource->id} ({$feedSource->name}) skipped - file unchanged");
                    $skippedCount++;
                    continue;
                }

                if (!$result['success']) {
                    $this->error("Feed #{$feedSource->id} ({$feedSource->name}) failed: {$result['error']}");
                    Log::channel('imports')->error('Feed download failed', [
                        'feed_id' => $feedSource->id,
                        'feed_name' => $feedSource->name,
                        'error' => $result['error'],
                    ]);
                    $failedCount++;
                    continue;
                }

                // Yeni feed_run oluştur
                $feedRun = FeedRun::create([
                    'feed_source_id' => $feedSource->id,
                    'status' => 'PENDING',
                    'started_at' => null,
                    'ended_at' => null,
                    'file_path' => $result['file_path'],
                    'file_hash' => $result['file_hash'],
                    'file_size' => $result['file_size'],
                ]);

                $this->info("Feed #{$feedSource->id} ({$feedSource->name}) downloaded - Run #{$feedRun->id} (PENDING)");
                Log::channel('imports')->info('Feed downloaded and run created', [
                    'feed_id' => $feedSource->id,
                    'feed_name' => $feedSource->name,
                    'run_id' => $feedRun->id,
                    'file_path' => $result['file_path'],
                    'file_hash' => $result['file_hash'],
                    'file_size' => $result['file_size'],
                ]);

                $createdCount++;

            } catch (\Exception $e) {
                $this->error("Feed #{$feedSource->id} ({$feedSource->name}) error: " . $e->getMessage());
                Log::channel('imports')->error('Feed download exception', [
                    'feed_id' => $feedSource->id,
                    'feed_name' => $feedSource->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failedCount++;
            }
        }

        $this->newLine();
        $this->info("İşlem tamamlandı:");
        $this->info("  - Oluşturulan: {$createdCount}");
        $this->info("  - Atlanan: {$skippedCount}");
        $this->info("  - Başarısız: {$failedCount}");

        Log::channel('imports')->info('DownloadFeedCommand completed', [
            'created' => $createdCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Download feed file from URL
     *
     * @param FeedSource $feedSource
     * @return array ['success' => bool, 'skipped' => bool, 'file_path' => string|null, 'file_hash' => string|null, 'file_size' => int|null, 'error' => string|null]
     */
    private function downloadFeedFile(FeedSource $feedSource): array
    {
        $tempFile = null;

        try {
            if (!$feedSource->url) {
                throw new Exception('Feed source URL is empty');
            }

            Log::channel('imports')->info('Starting feed download', [
                'feed_id' => $feedSource->id,
                'feed_name' => $feedSource->name,
                'url' => $feedSource->url,
            ]);

            // Geçici dosya oluştur
            $tempFile = tempnam(sys_get_temp_dir(), 'feed_download_');

            if (!$tempFile) {
                throw new Exception('Failed to create temporary file');
            }

            Log::channel('imports')->debug('Temporary file created', [
                'feed_id' => $feedSource->id,
                'temp_file' => $tempFile,
            ]);

            // Dosyayı indir
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; FeedBot/1.0)',
                'Accept' => 'application/xml,text/xml,*/*',
            ])
            ->timeout(180)
            ->sink($tempFile)
            ->get($feedSource->url);

            Log::channel('imports')->debug('HTTP request completed', [
                'feed_id' => $feedSource->id,
                'status_code' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if (!$response->successful()) {
                throw new Exception('HTTP STATUS: ' . $response->status() . ' - ' . $response->body());
            }

            // Dosya kontrolü
            if (!file_exists($tempFile)) {
                throw new Exception('Downloaded file does not exist');
            }

            $fileSize = filesize($tempFile);

            if ($fileSize === 0) {
                throw new Exception('Downloaded file is empty');
            }

            Log::channel('imports')->debug('File downloaded successfully', [
                'feed_id' => $feedSource->id,
                'file_size' => $fileSize,
            ]);

            // Hash ve size hesapla
            $fileHash = hash_file('sha256', $tempFile);

            Log::channel('imports')->debug('File hash calculated', [
                'feed_id' => $feedSource->id,
                'file_hash' => $fileHash,
            ]);

            // Önceki başarılı run ile hash karşılaştır
            $previousRun = FeedRun::where('feed_source_id', $feedSource->id)
                ->whereIn('status', ['PARSED', 'DONE'])
                ->whereNotNull('file_hash')
                ->latest('id')
                ->first();

            if ($previousRun && $previousRun->file_hash === $fileHash) {
                // Dosya değişmemiş, SKIPPED durumunda feed_run oluştur
                @unlink($tempFile);
                
                FeedRun::create([
                    'feed_source_id' => $feedSource->id,
                    'status' => 'SKIPPED',
                    'started_at' => null,
                    'ended_at' => now(),
                    'file_path' => null,
                    'file_hash' => $fileHash,
                    'file_size' => $fileSize,
                ]);

                Log::channel('imports')->info('Feed skipped - file unchanged', [
                    'feed_id' => $feedSource->id,
                    'file_hash' => $fileHash,
                    'previous_run_id' => $previousRun->id,
                ]);

                return [
                    'success' => true,
                    'skipped' => true,
                    'file_path' => null,
                    'file_hash' => $fileHash,
                    'file_size' => $fileSize,
                    'error' => null,
                ];
            }

            // Dosyayı storage'a kaydet
            $directory = "feeds/feed_{$feedSource->id}";
            Storage::makeDirectory($directory);
            $filename = now()->format('Y-m-d_His') . '.xml';
            $filePath = "{$directory}/{$filename}";

            Log::channel('imports')->debug('Saving file to storage', [
                'feed_id' => $feedSource->id,
                'file_path' => $filePath,
            ]);

            Storage::putFileAs($directory, $tempFile, $filename);
            @unlink($tempFile);
            $tempFile = null;

            Log::channel('imports')->info('Feed file saved to storage', [
                'feed_id' => $feedSource->id,
                'file_path' => $filePath,
                'file_hash' => $fileHash,
                'file_size' => $fileSize,
            ]);

            return [
                'success' => true,
                'skipped' => false,
                'file_path' => $filePath,
                'file_hash' => $fileHash,
                'file_size' => $fileSize,
                'error' => null,
            ];

        } catch (\Exception $e) {
            // Detaylı hata loglama
            Log::channel('imports')->error('Feed download error', [
                'feed_id' => $feedSource->id ?? null,
                'feed_name' => $feedSource->name ?? null,
                'url' => $feedSource->url ?? null,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Temizlik
            if ($tempFile && file_exists($tempFile)) {
                @unlink($tempFile);
            }

            return [
                'success' => false,
                'skipped' => false,
                'file_path' => null,
                'file_hash' => null,
                'file_size' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}

