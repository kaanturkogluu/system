<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateBrandOrigins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brands:update-origins {--limit=10 : Limit number of brands to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and update brand origins by searching the web';

    /**
     * Known brand origins mapping (common brands)
     */
    private array $knownOrigins = [
        'ZEBEX' => 'China',
        'SUNLUX' => 'China',
        'ZEBRA' => 'USA',
        'PERKON' => 'Turkey',
        'PERFORMAX' => 'USA',
        'TSCAN' => 'China',
        'DATALOGIC' => 'Italy',
        'SPENTA' => 'Turkey',
        'HONEYWELL' => 'USA',
        'BARTRONİX' => 'Turkey',
        'M3' => 'Turkey',
        'NEWLAND' => 'China',
        'XPRINTER' => 'China',
        'RONGTA' => 'China',
        'POSCLASS' => 'Turkey',
        'ARGOX' => 'Taiwan',
        'TSC' => 'Taiwan',
        'PALMX' => 'China',
        'GODEX' => 'Taiwan',
        'OEM' => 'China',
        'M3 MOBILE' => 'Turkey',
        'POSTÜRK' => 'Turkey',
        'KodPos' => 'Turkey',
        'CAS' => 'China',
        'PAYSİS' => 'Turkey',
        'KODAK' => 'USA',
        'BROTHER' => 'Japan',
        'XEROX' => 'USA',
        'CANON' => 'Japan',
        'ASUS' => 'Taiwan',
        'MSI' => 'Taiwan',
        'ASROCK' => 'Taiwan',
        'HI-LEVEL' => 'Turkey',
        'PRIMECOM' => 'Turkey',
        'GSKILL' => 'Taiwan',
        'KINGSTON' => 'USA',
        'SAMSUNG' => 'South Korea',
        'TWINMOS' => 'Taiwan',
        'RAMAXEL' => 'China',
        'DAHUA' => 'China',
        'TRANSCEND' => 'Taiwan',
        'LEXAR' => 'USA',
        'CORSAIR' => 'USA',
        'NEOFORZA' => 'USA',
        'HYNIX' => 'South Korea',
        'MICRON' => 'USA',
        'AXLE' => 'USA',
        'KIOXIA' => 'Japan',
        'CODEGEN' => 'China',
        'INTENSO' => 'Germany',
        'SKHYNIX' => 'South Korea',
        'WESTERN DIGITAL' => 'USA',
        'TOSHIBA' => 'Japan',
        'SEAGATE' => 'USA',
        'DELL' => 'USA',
        'AMD' => 'USA',
        'INTEL' => 'USA',
        'EVERCOOL' => 'Taiwan',
        'REDROCK' => 'China',
        'DARK' => 'China',
        'DEEPCOOL' => 'China',
        'POWER BOOST' => 'China',
        'TX' => 'China',
        'FRISBY' => 'China',
        'INCA' => 'China',
        'VENTO' => 'China',
        'EVEREST' => 'China',
        'BITFENIX' => 'Taiwan',
        'AEROCOOL' => 'Taiwan',
        'FSP' => 'Taiwan',
        'HIGH POWER' => 'China',
        'LOGITECH' => 'Switzerland',
        'A4 TECH' => 'China',
        'LENOVO' => 'China',
        'PHILIPS' => 'Netherlands',
        'QUADRO' => 'USA',
        'GAMEBOOSTER' => 'China',
        'HIKVISION' => 'China',
        'SUNCOM' => 'China',
        'RAMPAGE' => 'China',
        'FAZEON' => 'China',
        'S-LINK' => 'China',
        'TP-LINK' => 'China',
        'LECOO' => 'China',
        'SANDISK' => 'USA',
        'CODEGEN CODMAX' => 'China',
        'HYTECH' => 'China',
        'CUDY' => 'China',
        'PROXSEN' => 'China',
        'WESTA' => 'China',
        'Onli' => 'China',
        'SSB' => 'China',
        'DOTVOLT' => 'China',
        'TUNÇMATİK' => 'Turkey',
        'MAKELSAN' => 'Turkey',
        'TESCOM' => 'Japan',
        'EZVIZ' => 'China',
        'UniWiz' => 'China',
        'Ttec' => 'China',
        'UniView' => 'China',
        'TEKNİM' => 'Turkey',
        'FONRI' => 'China',
        'AJAX' => 'USA',
        'CODE CODESEC' => 'China',
        'GST' => 'China',
        'FUJITRON' => 'China',
        'ZENON' => 'China',
        'HP' => 'USA',
        'GMKtec' => 'China',
        'I-LIFE' => 'China',
        'HCS' => 'Turkey',
        'ANZILIA PONIVA' => 'China',
        'YEALINK' => 'China',
        'LANDE' => 'China',
        'ODS' => 'China',
        'ERAT' => 'Turkey',
        'TP-LINK MERCUSYS' => 'China',
        'UBIQUITI' => 'USA',
        'TP-LINK OMADA' => 'China',
        'RUIJIE-REYEE' => 'China',
        'Wİ-TEK' => 'China',
        'RUIJIE' => 'China',
        'OPTOMA' => 'Taiwan',
        'VIEWSONIC' => 'Taiwan',
        'COMPAXE' => 'China',
        'DREXEL' => 'China',
        'PLM' => 'China',
        'KODPOS' => 'Turkey',
        'KodPos' => 'Turkey',
        'ONLI' => 'China',
        'Onli' => 'China',
        'UNIWIZ' => 'China',
        'UniWiz' => 'China',
        'TTEC' => 'China',
        'Ttec' => 'China',
        'UNIVIEW' => 'China',
        'UniView' => 'China',
        'GMKTEC' => 'China',
        'GMKtec' => 'China',
    ];

    /**
     * Country name to Country model mapping
     */
    private array $countryMap = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Marka menşeileri güncelleniyor...');
        $this->newLine();

        // Load all countries into memory for quick lookup
        $this->loadCountries();

        // Get brands without origin
        $limit = (int) $this->option('limit');
        $brands = Brand::whereNull('origin_country_id')
            ->where('status', 'active')
            ->limit($limit)
            ->get();

        if ($brands->isEmpty()) {
            $this->info('✅ Tüm markaların menşei bilgisi mevcut.');
            return Command::SUCCESS;
        }

        $this->info("📦 {$brands->count()} marka işlenecek...");
        $this->newLine();

        $updated = 0;
        $notFound = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($brands->count());
        $progressBar->start();

        foreach ($brands as $brand) {
            try {
                $countryName = $this->findBrandOrigin($brand->name);
                
                if ($countryName) {
                    $country = $this->getOrCreateCountry($countryName);
                    
                    if ($country) {
                        $brand->origin_country_id = $country->id;
                        $brand->save();
                        $updated++;
                    } else {
                        $notFound++;
                    }
                } else {
                    $notFound++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("❌ Hata ({$brand->name}): " . $e->getMessage());
            }

            $progressBar->advance();
            
            // Rate limiting - be nice to web services
            usleep(500000); // 0.5 second delay
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ {$updated} marka güncellendi.");
        if ($notFound > 0) {
            $this->warn("⚠️  {$notFound} marka için menşei bulunamadı.");
        }
        if ($errors > 0) {
            $this->error("❌ {$errors} hata oluştu.");
        }

        return Command::SUCCESS;
    }

    /**
     * Load all countries into memory
     */
    private function loadCountries(): void
    {
        $countries = Country::where('status', 'active')->get();
        
        foreach ($countries as $country) {
            $this->countryMap[strtolower($country->name)] = $country;
            $this->countryMap[strtolower($country->code)] = $country;
        }
    }

    /**
     * Find brand origin - first check known origins, then search web
     */
    private function findBrandOrigin(string $brandName): ?string
    {
        // First check known origins
        $normalizedBrandName = strtoupper(trim($brandName));
        if (isset($this->knownOrigins[$normalizedBrandName])) {
            return $this->knownOrigins[$normalizedBrandName];
        }

        // For now, return null - web search will be done manually or via API
        // Web search implementation would go here
        return null;
    }

    /**
     * Web search using web_search tool
     */
    private function webSearch(string $query): ?string
    {
        // Note: This method will be called from handle() method
        // Web search will be done inline in findBrandOrigin method
        return null;
    }

    /**
     * Extract country from search results
     */
    private function extractCountryFromResults(string $searchText, string $brandName): ?string
    {
        // Common country patterns
        $countryPatterns = [
            '/\b(United States|USA|US|America)\b/i',
            '/\b(South Korea|Korea|South Korean)\b/i',
            '/\b(Taiwan|Taiwanese)\b/i',
            '/\b(Turkey|Türkiye|Turkish)\b/i',
            '/\b(China|Chinese|PRC)\b/i',
            '/\b(Japan|Japanese)\b/i',
            '/\b(Germany|German)\b/i',
            '/\b(Netherlands|Dutch)\b/i',
            '/\b(Switzerland|Swiss)\b/i',
            '/\b(Italy|Italian)\b/i',
        ];

        $countryMap = [
            'United States' => 'United States',
            'USA' => 'United States',
            'US' => 'United States',
            'America' => 'United States',
            'South Korea' => 'South Korea',
            'Korea' => 'South Korea',
            'South Korean' => 'South Korea',
            'Taiwan' => 'Taiwan',
            'Taiwanese' => 'Taiwan',
            'Turkey' => 'Turkey',
            'Türkiye' => 'Turkey',
            'Turkish' => 'Turkey',
            'China' => 'China',
            'Chinese' => 'China',
            'PRC' => 'China',
            'Japan' => 'Japan',
            'Japanese' => 'Japan',
            'Germany' => 'Germany',
            'German' => 'Germany',
            'Netherlands' => 'Netherlands',
            'Dutch' => 'Netherlands',
            'Switzerland' => 'Switzerland',
            'Swiss' => 'Switzerland',
            'Italy' => 'Italy',
            'Italian' => 'Italy',
        ];

        foreach ($countryPatterns as $pattern) {
            if (preg_match($pattern, $searchText, $matches)) {
                $found = trim($matches[1]);
                return $countryMap[$found] ?? $found;
            }
        }

        return null;
    }

    /**
     * Get or create country by name
     */
    private function getOrCreateCountry(string $countryName): ?Country
    {
        $normalizedName = strtolower(trim($countryName));
        
        // Check if country exists in map
        if (isset($this->countryMap[$normalizedName])) {
            return $this->countryMap[$normalizedName];
        }

        // Try to find by name
        $country = Country::where('name', 'like', $countryName)
            ->orWhere('code', 'like', $countryName)
            ->first();

        if ($country) {
            $this->countryMap[$normalizedName] = $country;
            return $country;
        }

        // Country name mappings for common variations
        $countryMappings = [
            'usa' => 'United States',
            'us' => 'United States',
            'united states' => 'United States',
            'united states of america' => 'United States',
            'south korea' => 'South Korea',
            'korea' => 'South Korea',
            'taiwan' => 'Taiwan',
            'turkey' => 'Turkey',
            'türkiye' => 'Turkey',
            'china' => 'China',
            'japan' => 'Japan',
            'germany' => 'Germany',
            'netherlands' => 'Netherlands',
            'switzerland' => 'Switzerland',
        ];

        $mappedName = $countryMappings[$normalizedName] ?? $countryName;

        // Try again with mapped name
        $country = Country::where('name', 'like', "%{$mappedName}%")
            ->first();

        if ($country) {
            $this->countryMap[$normalizedName] = $country;
            return $country;
        }

        // If still not found, create it
        $country = Country::create([
            'code' => $this->generateCountryCode($countryName),
            'name' => $mappedName,
            'status' => 'active',
        ]);

        $this->countryMap[$normalizedName] = $country;
        return $country;
    }

    /**
     * Generate country code from name
     */
    private function generateCountryCode(string $countryName): string
    {
        $mappings = [
            'United States' => 'US',
            'USA' => 'US',
            'South Korea' => 'KR',
            'Taiwan' => 'TW',
            'Turkey' => 'TR',
            'China' => 'CN',
            'Japan' => 'JP',
            'Germany' => 'DE',
            'Netherlands' => 'NL',
            'Switzerland' => 'CH',
            'Italy' => 'IT',
        ];

        return $mappings[$countryName] ?? strtoupper(substr($countryName, 0, 2));
    }
}
