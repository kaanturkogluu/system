<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedRun;
use App\Models\FeedSource;
use App\Models\ImportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class FeedRunController extends Controller
{
    /**
     * Display a listing of feed runs
     */
    public function index(Request $request)
    {
        $query = FeedRun::with('feedSource');

        // Filter by feed source
        if ($request->has('feed_source_id') && $request->feed_source_id) {
            $query->where('feed_source_id', $request->feed_source_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $feedRuns = $query->latest('id')->paginate(30);
        $feedSources = FeedSource::orderBy('name')->get();

        // Add file_exists check for each feed run
        $feedRuns->getCollection()->transform(function ($feedRun) {
            $feedRun->file_exists = $feedRun->file_path ? Storage::exists($feedRun->file_path) : false;
            $feedRun->file_url = $feedRun->file_path && Storage::exists($feedRun->file_path) 
                ? Storage::url($feedRun->file_path) 
                : null;
            return $feedRun;
        });

        return view('admin.feed-runs.index', compact('feedRuns', 'feedSources'));
    }

    /**
     * Show the specified feed run
     */
    public function show(FeedRun $feedRun)
    {
        $feedRun->load('feedSource');
        $feedRun->file_exists = $feedRun->file_path ? Storage::exists($feedRun->file_path) : false;
        $feedRun->file_url = $feedRun->file_path && Storage::exists($feedRun->file_path) 
            ? Storage::url($feedRun->file_path) 
            : null;
        return view('admin.feed-runs.show', compact('feedRun'));
    }

    /**
     * Trigger download for a feed source
     */
    public function triggerDownload(Request $request)
    {
        $request->validate([
            'feed_source_id' => 'required|exists:feed_sources,id',
        ]);

        try {
            $feedSource = FeedSource::findOrFail($request->feed_source_id);

            if (!$feedSource->is_active) {
                return redirect()->route('admin.feed-runs.index')
                    ->with('error', 'Feed kaynağı aktif değil.');
            }

            // Aynı feed_source_id için PENDING durumunda feed_run var mı kontrol et
            $pendingRun = FeedRun::where('feed_source_id', $feedSource->id)
                ->where('status', 'PENDING')
                ->first();

            if ($pendingRun) {
                return redirect()->route('admin.feed-runs.index')
                    ->with('info', "Feed #{$feedSource->id} ({$feedSource->name}) zaten PENDING durumunda (Run #{$pendingRun->id})");
            }

            // Feed dosyasını indir
            $result = $this->downloadFeedFile($feedSource);

            if ($result['skipped']) {
                return redirect()->route('admin.feed-runs.index')
                    ->with('info', "Feed #{$feedSource->id} ({$feedSource->name}) atlandı - dosya değişmemiş (hash: {$result['file_hash']})");
            }

            if (!$result['success']) {
                return redirect()->route('admin.feed-runs.index')
                    ->with('error', "Feed indirme başarısız: {$result['error']}");
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

            Log::channel('imports')->info('Feed downloaded via admin panel', [
                'feed_id' => $feedSource->id,
                'feed_name' => $feedSource->name,
                'run_id' => $feedRun->id,
                'file_path' => $result['file_path'],
                'file_hash' => $result['file_hash'],
                'file_size' => $result['file_size'],
            ]);

            return redirect()->route('admin.feed-runs.index')
                ->with('success', "Feed başarıyla indirildi ve PENDING durumunda run oluşturuldu. Run ID: {$feedRun->id}");

        } catch (\Exception $e) {
            Log::channel('imports')->error('Feed download failed via admin panel', [
                'feed_source_id' => $request->feed_source_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.feed-runs.index')
                ->with('error', 'Feed indirme sırasında hata oluştu: ' . $e->getMessage());
        }
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

            // Geçici dosya oluştur
            $tempFile = tempnam(sys_get_temp_dir(), 'feed_download_');

            if (!$tempFile) {
                throw new Exception('Failed to create temporary file');
            }

            // Dosyayı indir
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; FeedBot/1.0)',
                'Accept' => 'application/xml,text/xml,*/*',
            ])
            ->timeout(180)
            ->sink($tempFile)
            ->get($feedSource->url);

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

            // Hash ve size hesapla
            $fileHash = hash_file('sha256', $tempFile);

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

        } catch (\Exception $e) {
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

    /**
     * Parse a specific feed run
     */
    public function parseFeedRun(FeedRun $feedRun)
    {
        try {
            // Kontroller
            if ($feedRun->status !== 'PENDING' && $feedRun->status !== 'DONE') {
                return redirect()->route('admin.feed-runs.index')
                    ->with('error', "Feed Run #{$feedRun->id} parse edilemez. Durum: {$feedRun->status}");
            }

            if (!$feedRun->file_path || !Storage::exists($feedRun->file_path)) {
                return redirect()->route('admin.feed-runs.index')
                    ->with('error', "Feed Run #{$feedRun->id} için dosya bulunamadı.");
            }

            // ParseFeedsCommand'daki parseFeedRun methodunu kullan
            // Önce command instance'ı oluştur
            $command = new \App\Console\Commands\ParseFeedsCommand();
            
            // Reflection kullanarak private method'a eriş
            $reflection = new \ReflectionClass($command);
            $method = $reflection->getMethod('parseFeedRun');
            $method->setAccessible(true);
            
            // Parse işlemini başlat
            $result = $method->invoke($command, $feedRun);
            
            if ($result['success']) {
                $feedRun->refresh();
                return redirect()->route('admin.feed-runs.index')
                    ->with('success', "Feed Run #{$feedRun->id} başarıyla parse edildi. {$result['items_count']} item eklendi.");
            } else {
                return redirect()->route('admin.feed-runs.index')
                    ->with('error', "Feed Run #{$feedRun->id} parse edilemedi: {$result['error']}");
            }

        } catch (\Exception $e) {
            Log::channel('imports')->error('Feed run parse failed via admin panel', [
                'feed_run_id' => $feedRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.feed-runs.index')
                ->with('error', 'Parse işlemi sırasında hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch imports for a specific feed run or all pending imports
     */
    public function dispatchImports(FeedRun $feedRun = null)
    {
        try {
            if ($feedRun) {
                // Belirli bir feed run için dispatch
                $importItemsCount = ImportItem::where('feed_run_id', $feedRun->id)
                    ->where('status', 'PENDING')
                    ->count();

                if ($importItemsCount === 0) {
                    return redirect()->route('admin.feed-runs.index')
                        ->with('info', "Feed Run #{$feedRun->id} için PENDING import item bulunamadı.");
                }

                // Sadece bu feed run'a ait PENDING item'ları dispatch et
                $dispatched = 0;
                ImportItem::where('feed_run_id', $feedRun->id)
                    ->where('status', 'PENDING')
                    ->chunk(1000, function ($items) use (&$dispatched) {
                        foreach ($items as $item) {
                            \App\Jobs\ImportItemJob::dispatch($item->id)->onQueue('imports');
                            $dispatched++;
                        }
                    });

                // Queue worker'ı başlat
                $this->startQueueWorker();

                return redirect()->route('admin.feed-runs.index')
                    ->with('success', "Feed Run #{$feedRun->id} için {$dispatched} import item dispatch edildi ve queue worker başlatıldı.");
            } else {
                // Tüm PENDING import item'lar için dispatch
                Artisan::call('app:imports:dispatch', [
                    '--auto-start' => true,
                ]);

                return redirect()->route('admin.feed-runs.index')
                    ->with('success', 'Tüm PENDING import item\'lar dispatch edildi ve queue worker başlatıldı.');
            }

        } catch (\Exception $e) {
            Log::channel('imports')->error('Dispatch imports failed via admin panel', [
                'feed_run_id' => $feedRun?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.feed-runs.index')
                ->with('error', 'Dispatch işlemi sırasında hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Re-import a feed run (reset import items to PENDING and re-parse)
     */
    public function reimport(FeedRun $feedRun)
    {
        try {
            // Kontroller
            if (!$feedRun->file_path || !Storage::exists($feedRun->file_path)) {
                return redirect()->route('admin.feed-runs.index')
                    ->with('error', "Feed Run #{$feedRun->id} için dosya bulunamadı.");
            }

            DB::beginTransaction();

            // Bu feed_run'a ait tüm import_item'ların status'ünü PENDING yap
            $updatedCount = ImportItem::where('feed_run_id', $feedRun->id)
                ->where('status', '!=', 'PENDING')
                ->update(['status' => 'PENDING']);

            Log::channel('imports')->info('Feed run re-import initiated', [
                'feed_run_id' => $feedRun->id,
                'updated_items_count' => $updatedCount,
            ]);

            // Feed run status'ünü PENDING yap
            $feedRun->update([
                'status' => 'PENDING',
                'started_at' => null,
                'ended_at' => null,
            ]);

            DB::commit();

            // Parse işlemini başlat
            $command = new \App\Console\Commands\ParseFeedsCommand();
            $reflection = new \ReflectionClass($command);
            $method = $reflection->getMethod('parseFeedRun');
            $method->setAccessible(true);
            
            $result = $method->invoke($command, $feedRun);
            
            if ($result['success']) {
                $feedRun->refresh();
                return redirect()->route('admin.feed-runs.index')
                    ->with('success', "Feed Run #{$feedRun->id} yeniden aktarıldı. {$updatedCount} item PENDING yapıldı, {$result['items_count']} item parse edildi.");
            } else {
                return redirect()->route('admin.feed-runs.index')
                    ->with('error', "Feed Run #{$feedRun->id} yeniden aktarılamadı: {$result['error']}");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::channel('imports')->error('Feed run re-import failed', [
                'feed_run_id' => $feedRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.feed-runs.index')
                ->with('error', 'Yeniden aktarma işlemi sırasında hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Start queue worker in background
     */
    private function startQueueWorker(): void
    {
        $phpPath = PHP_BINARY;
        $artisanPath = base_path('artisan');
        $command = "queue:work database --queue=imports --tries=3 --timeout=300 --stop-when-empty";
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $fullCommand = sprintf(
                'start /B "" "%s" "%s" %s',
                $phpPath,
                $artisanPath,
                $command
            );
            exec($fullCommand . ' 2>&1');
        } else {
            $fullCommand = sprintf(
                'nohup %s %s %s > /dev/null 2>&1 &',
                $phpPath,
                escapeshellarg($artisanPath),
                $command
            );
            exec($fullCommand);
        }
    }
}

