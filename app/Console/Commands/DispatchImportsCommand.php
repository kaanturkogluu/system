<?php

namespace App\Console\Commands;

use App\Jobs\ImportItemJob;
use App\Models\ImportItem;
use Illuminate\Console\Command;

class DispatchImportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:imports:dispatch';

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

        $this->info("{$dispatchedCount} import item job'a gönderildi.");

        return Command::SUCCESS;
    }
}

