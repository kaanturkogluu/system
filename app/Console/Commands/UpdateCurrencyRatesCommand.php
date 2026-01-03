<?php

namespace App\Console\Commands;

use App\Jobs\UpdateCurrencyRatesJob;
use Illuminate\Console\Command;

class UpdateCurrencyRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:currency:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'TCMB döviz kurlarını güncelle';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('💰 TCMB döviz kurları güncelleniyor...');

        try {
            // Job'ı dispatch et
            UpdateCurrencyRatesJob::dispatch();

            $this->info('✅ Döviz kurları güncelleme job\'ı kuyruğa eklendi.');
            $this->info('💡 Job\'ın işlenmesi için queue worker çalışıyor olmalı.');
            $this->info('💡 Hemen çalıştırmak için: php artisan queue:work --stop-when-empty');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Hata oluştu: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

