<?php

namespace App\Console\Commands;

use App\Jobs\DownloadFeedJob;
use App\Models\FeedRun;
use App\Models\FeedSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Aktif XML feed\'leri başlat';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Feed çalıştırma işlemi başlatılıyor...');

        try {
            DB::beginTransaction();

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
                DB::rollBack();
                return Command::FAILURE;
            }

            $this->info("{$feedSources->count()} aktif XML feed bulundu.");

            $startedCount = 0;
            $skippedCount = 0;

            foreach ($feedSources as $feedSource) {
                try {
                    // Aynı feed_source_id için RUNNING durumunda feed_run var mı kontrol et
                    $runningRun = FeedRun::where('feed_source_id', $feedSource->id)
                        ->where('status', 'RUNNING')
                        ->first();

                    if ($runningRun) {
                        $this->warn("Feed #{$feedSource->id} ({$feedSource->name}) atlandı - zaten çalışıyor (Run #{$runningRun->id})");
                        Log::channel('imports')->info('Feed skipped - already running', [
                            'feed_id' => $feedSource->id,
                            'feed_name' => $feedSource->name,
                            'running_run_id' => $runningRun->id,
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    // Yeni feed_run oluştur
                    $feedRun = FeedRun::create([
                        'feed_source_id' => $feedSource->id,
                        'status' => 'RUNNING',
                        'started_at' => now(),
                    ]);

                    // Job'ı dispatch et
                    DownloadFeedJob::dispatch($feedRun->id);

                    $this->info("Feed #{$feedSource->id} ({$feedSource->name}) başlatıldı - Run #{$feedRun->id}");
                    Log::channel('imports')->info('Feed run started', [
                        'feed_id' => $feedSource->id,
                        'feed_name' => $feedSource->name,
                        'run_id' => $feedRun->id,
                    ]);

                    $startedCount++;

                } catch (\Exception $e) {
                    $this->error("Feed #{$feedSource->id} ({$feedSource->name}) başlatılırken hata: " . $e->getMessage());
                    Log::channel('imports')->error('Feed run start failed', [
                        'feed_id' => $feedSource->id,
                        'feed_name' => $feedSource->name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            $this->newLine();
            $this->info("İşlem tamamlandı:");
            $this->info("  - Başlatılan: {$startedCount}");
            $this->info("  - Atlanan: {$skippedCount}");

            Log::channel('imports')->info('RunFeedsCommand completed', [
                'started' => $startedCount,
                'skipped' => $skippedCount,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('İşlem sırasında hata oluştu: ' . $e->getMessage());
            Log::channel('imports')->error('RunFeedsCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
