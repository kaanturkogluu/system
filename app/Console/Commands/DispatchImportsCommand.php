<?php

namespace App\Console\Commands;

use App\Jobs\ImportItemJob;
use App\Models\ImportItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchImportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:imports:dispatch {--auto-start : Queue worker\'ı otomatik başlat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PENDING durumundaki import_items kayıtlarını job\'a gönder';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Import items dispatch işlemi başlatılıyor...');

        // Queue'da zaten bekleyen job'ları kontrol et
        $queuedJobsCount = DB::table('jobs')
            ->where('queue', 'imports')
            ->count();

        if ($queuedJobsCount > 0) {
            $this->warn("⚠️  Queue'da zaten {$queuedJobsCount} job bekliyor!");
            $this->warn("💡 Queue worker'ı başlatmak için: php artisan queue:work database --queue=imports");
            $this->warn("💡 Veya tüm job'ları işlemek için: php artisan queue:work database --queue=imports --stop-when-empty");
            
            $continue = $this->confirm('Yine de yeni job\'lar eklemek istiyor musunuz?', false);
            if (!$continue) {
                return Command::SUCCESS;
            }
        }

        $totalCount = ImportItem::where('status', 'PENDING')->count();

        if ($totalCount === 0) {
            $this->warn('PENDING durumunda import item bulunamadı.');
            return Command::SUCCESS;
        }

        $this->info("{$totalCount} PENDING import item bulundu.");

        $dispatchedCount = 0;

        // Chunk ile işle (1000'lik parçalar halinde)
        ImportItem::where('status', 'PENDING')
            ->chunk(1000, function ($items) use (&$dispatchedCount) {
                foreach ($items as $item) {
                    ImportItemJob::dispatch($item->id)->onQueue('imports');
                    $dispatchedCount++;
                }
            });

        $this->info("✅ {$dispatchedCount} import item job'a gönderildi.");
        
        // Toplam queue job sayısını kontrol et
        $totalQueuedJobs = DB::table('jobs')
            ->where('queue', 'imports')
            ->count();
        
        if ($totalQueuedJobs > 0) {
            // Otomatik başlatma seçeneği
            $autoStart = $this->option('auto-start');
            
            if (!$autoStart) {
                $autoStart = $this->confirm('Queue worker\'ı otomatik başlatmak ister misiniz?', true);
            }
            
            if ($autoStart) {
                $this->info("🚀 Queue worker başlatılıyor...");
                $this->startQueueWorker();
            } else {
                $this->warn("⚠️  Queue worker çalışmıyor! Job'ları işlemek için:");
                $this->line("   php artisan queue:work database --queue=imports --stop-when-empty");
                $this->line("   veya");
                $this->line("   php artisan queue:work database --queue=imports");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Queue worker'ı arka planda başlat
     */
    private function startQueueWorker(): void
    {
        $phpPath = PHP_BINARY;
        $artisanPath = base_path('artisan');
        // --stop-when-empty: Tüm job'lar işlenince otomatik durur
        $command = "queue:work database --queue=imports --tries=3 --timeout=300 --stop-when-empty";
        
        // Windows için arka plan process başlatma
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows'ta start /B ile arka planda başlat
            $fullCommand = sprintf(
                'start /B "" "%s" "%s" %s',
                $phpPath,
                $artisanPath,
                $command
            );
            
            // Alternatif: PowerShell kullan (daha güvenilir)
            $powershellCommand = sprintf(
                'powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath \'%s\' -ArgumentList \'%s %s\' -WindowStyle Hidden"',
                $phpPath,
                $artisanPath,
                str_replace('"', '\"', $command)
            );
            
            // Önce start /B dene, başarısız olursa PowerShell dene
            exec($fullCommand . ' 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                // PowerShell ile dene
                exec($powershellCommand . ' 2>&1', $output, $returnCode);
            }
            
            if ($returnCode === 0) {
                $this->info("✅ Queue worker arka planda başlatıldı.");
                $this->line("💡 Worker tüm job'ları işleyip otomatik duracak.");
                $this->line("💡 Worker'ı manuel durdurmak için: taskkill /F /FI \"WINDOWTITLE eq php artisan queue:work*\"");
            } else {
                $this->warn("⚠️  Queue worker otomatik başlatılamadı.");
                $this->warn("⚠️  Lütfen manuel olarak başlatın:");
                $this->line("   php artisan queue:work database --queue=imports --stop-when-empty");
            }
        } else {
            // Linux/Unix için
            $fullCommand = sprintf(
                'nohup %s %s %s > /dev/null 2>&1 &',
                $phpPath,
                escapeshellarg($artisanPath),
                $command
            );
            
            exec($fullCommand, $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->info("✅ Queue worker arka planda başlatıldı.");
                $this->line("💡 Worker tüm job'ları işleyip otomatik duracak.");
            } else {
                $this->warn("⚠️  Queue worker başlatılamadı. Manuel olarak başlatın:");
                $this->line("   php artisan queue:work database --queue=imports --stop-when-empty");
            }
        }
    }
}

