<?php

namespace App\Console\Commands;

use App\Services\StockSTKeringReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshStockStKeringPdfIfChangedCommand extends Command
{
    protected $signature = 'reports:refresh-stock-st-kering-pdf-if-changed
        {--end-date= : Tanggal akhir laporan Stock ST Kering}
        {--force : Tetap render ulang meskipun fingerprint data belum berubah}';

    protected $description = 'Refresh shared Stock ST Kering PDF only when source data changes.';

    public function handle(StockSTKeringReportService $reportService): int
    {
        $endDate = $this->resolveEndDate();
        $fingerprint = $reportService->buildFingerprint($endDate);
        $previous = $this->readPreviousFingerprint($endDate);

        if (! $this->option('force') && ($previous['hash'] ?? null) === $fingerprint['hash']) {
            $this->info("Data Stock ST Kering {$endDate} belum berubah. Render PDF shared dilewati.");

            return self::SUCCESS;
        }

        $this->info("Data Stock ST Kering {$endDate} berubah. Mulai render ulang PDF shared.");
        $exitCode = Artisan::call('reports:warm-stock-st-kering-pdf', [
            '--end-date' => $endDate,
            '--force' => true,
        ]);

        $this->output->write(Artisan::output());

        if ($exitCode !== self::SUCCESS) {
            $this->error('Render ulang PDF shared Stock ST Kering gagal. Fingerprint tidak disimpan.');

            return self::FAILURE;
        }

        $this->writeFingerprint($endDate, $fingerprint);
        $this->info("Fingerprint Stock ST Kering disimpan. Rows: {$fingerprint['row_count']}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function readPreviousFingerprint(string $endDate): array
    {
        $path = $this->fingerprintPath($endDate);
        if (! is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if (! is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $fingerprint
     */
    private function writeFingerprint(string $endDate, array $fingerprint): void
    {
        $path = $this->fingerprintPath($endDate);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        file_put_contents($path, json_encode($fingerprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
    }

    private function fingerprintPath(string $endDate): string
    {
        return storage_path('app/pdf-report-fingerprints/stock-st-kering-'.$endDate.'.json');
    }

    private function resolveEndDate(): string
    {
        $optionDate = trim((string) $this->option('end-date'));
        if ($optionDate !== '') {
            return $optionDate;
        }

        $configDate = trim((string) config('reports.stock_st_kering.warm_end_date', ''));
        if ($configDate !== '') {
            return $configDate;
        }

        return now()->toDateString();
    }
}
