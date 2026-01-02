<?php

use App\Jobs\UpdateCurrencyRatesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// TCMB döviz kurlarını 5 dakikada bir güncelle
Schedule::job(new UpdateCurrencyRatesJob())
    ->everyFiveMinutes()
    ->withoutOverlapping();
