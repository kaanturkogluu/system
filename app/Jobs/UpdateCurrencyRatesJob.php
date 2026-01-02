<?php

namespace App\Jobs;

use App\Models\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class UpdateCurrencyRatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 60;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('TCMB döviz kurları güncelleniyor...');

            // TCMB XML'ini çek
            $response = Http::timeout(30)->get('http://www.tcmb.gov.tr/kurlar/today.xml');

            if (!$response->successful()) {
                throw new Exception('TCMB API yanıt vermedi. HTTP Status: ' . $response->status());
            }

            $xmlContent = $response->body();
            
            if (empty($xmlContent)) {
                throw new Exception('TCMB XML içeriği boş.');
            }

            // XML'i parse et
            $xml = simplexml_load_string($xmlContent);
            
            if ($xml === false) {
                throw new Exception('TCMB XML parse edilemedi.');
            }

            // Currency kodlarını mapping (TCMB kodları -> ISO kodları)
            $currencyMapping = [
                'USD' => 'USD',
                'EUR' => 'EUR',
                'GBP' => 'GBP',
                'JPY' => 'JPY',
                'CHF' => 'CHF',
                'AUD' => 'AUD',
                'CAD' => 'CAD',
                'RUB' => 'RUB',
                'CNY' => 'CNY',
            ];

            $updated = 0;
            $notFound = 0;

            // TCMB XML'den currency'leri al
            foreach ($xml->Currency as $currencyNode) {
                $tcmbCode = (string) $currencyNode['CurrencyCode'];
                
                // Mapping'de var mı kontrol et
                if (!isset($currencyMapping[$tcmbCode])) {
                    continue;
                }

                $isoCode = $currencyMapping[$tcmbCode];
                
                // Currency'yi bul
                $currency = Currency::where('code', $isoCode)->first();
                
                if (!$currency) {
                    $notFound++;
                    Log::warning("Para birimi bulunamadı: {$isoCode}");
                    continue;
                }

                // TRY zaten 1.000000, güncelleme
                if ($currency->code === 'TRY') {
                    continue;
                }

                // ForexBuying değerini al (Alış kuru - 1 birim para birimi = X TRY)
                $forexBuying = (string) $currencyNode->ForexBuying;
                
                if (empty($forexBuying) || $forexBuying === '0' || $forexBuying === '') {
                    Log::warning("TCMB'den {$isoCode} için geçerli kur bulunamadı.");
                    continue;
                }

                // Rate'ı güncelle (1 USD = X TRY formatında)
                $rateToTry = (float) $forexBuying;
                
                if ($rateToTry <= 0) {
                    Log::warning("TCMB'den {$isoCode} için geçersiz kur: {$rateToTry}");
                    continue;
                }

                $currency->update([
                    'rate_to_try' => $rateToTry,
                ]);

                $updated++;
                Log::info("Para birimi güncellendi: {$isoCode} = {$rateToTry} TRY");
            }

            Log::info("Döviz kurları güncellendi. Güncellenen: {$updated}, Bulunamayan: {$notFound}");

        } catch (Exception $e) {
            Log::error('TCMB döviz kurları güncellenirken hata oluştu: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
