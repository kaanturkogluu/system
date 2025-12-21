<?php

namespace App\Console\Commands;

use App\Models\FeedRun;
use App\Models\FeedSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class RunFeedsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:feeds:run {--feed_id= : Belirli bir feed ID için çalıştır}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aktif XML feed\'leri indir ve PENDING durumunda feed_run oluştur';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Feed indirme işlemi başlatılıyor...');

        try {
            // Feed kaynaklarını al
            $query = FeedSource::where('type', 'xml')
                ->where('is_active', true);

            // Eğer feed_id belirtilmişse sadece onu al
            if ($this->option('feed_id')) {
                $query->where('id', $this->option('feed_id'));
            }

            $feedSources = $query->get();

            if ($feedSources->isEmpty()) {
                $this->warn('Aktif XML feed bulunamadı.');
                return Command::FAILURE;
            }

            $this->info("{$feedSources->count()} aktif XML feed bulundu.");

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
                        $this->warn("Feed #{$feedSource->id} ({$feedSource->name}) atlandı - zaten PENDING durumunda (Run #{$pendingRun->id})");
                        Log::channel('imports')->info('Feed skipped - already pending', [
                            'feed_id' => $feedSource->id,
                            'feed_name' => $feedSource->name,
                            'pending_run_id' => $pendingRun->id,
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    // Feed dosyasını indir
                    $result = $this->downloadFeed($feedSource);

                    if ($result['skipped']) {
                        $this->info("Feed #{$feedSource->id} ({$feedSource->name}) atlandı - dosya değişmemiş (hash: {$result['file_hash']})");
                        $skippedCount++;
                        continue;
                    }

                    if (!$result['success']) {
                        $this->error("Feed #{$feedSource->id} ({$feedSource->name}) indirilemedi: {$result['error']}");
                        $failedCount++;
                        continue;
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

                    $this->info("Feed #{$feedSource->id} ({$feedSource->name}) indirildi - Run #{$feedRun->id} (PENDING)");
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
                    $this->error("Feed #{$feedSource->id} ({$feedSource->name}) işlenirken hata: " . $e->getMessage());
                    Log::channel('imports')->error('Feed download failed', [
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

            Log::channel('imports')->info('RunFeedsCommand completed', [
                'created' => $createdCount,
                'skipped' => $skippedCount,
                'failed' => $failedCount,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('İşlem sırasında hata oluştu: ' . $e->getMessage());
            Log::channel('imports')->error('RunFeedsCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Download feed file from URL
     *
     * @param FeedSource $feedSource
     * @return array ['success' => bool, 'skipped' => bool, 'file_path' => string|null, 'file_hash' => string|null, 'file_size' => int|null, 'error' => string|null]
     */
    private function downloadFeed(FeedSource $feedSource): array
    {
        $tempFile = null;

        try {
            if (!$feedSource->url) {
                throw new Exception('Feed source URL is empty');
            }

            // Geçici dosya oluştur
            $tempFile = tempnam(sys_get_temp_dir(), 'feed_download_');

            // Dosyayı indir
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; FeedBot/1.0)',
                'Accept' => 'application/xml,text/xml,*/*',
            ])
            ->timeout(180)
            ->sink($tempFile)
            ->get($feedSource->url);

            if (!$response->successful()) {
                throw new Exception('HTTP STATUS: ' . $response->status());
            }

            // Dosya kontrolü
            if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                throw new Exception('Downloaded file is empty');
            }

            // Hash ve size hesapla
            $fileHash = hash_file('sha256', $tempFile);
            $fileSize = filesize($tempFile);

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

            Storage::putFileAs($directory, $tempFile, $filename);
            @unlink($tempFile);
            $tempFile = null;

            return [
                'success' => true,
                'skipped' => false,
                'file_path' => $filePath,
                'file_hash' => $fileHash,
                'file_size' => $fileSize,
                'error' => null,
            ];

        } catch (Exception $e) {
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
