<?php

namespace App\Console\Commands;

use App\Services\LabelStHidupDetailReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshLabelStHidupDetailPdfIfChangedCommand extends Command
{
    protected $signature = 'reports:refresh-label-st-hidup-detail-pdf-if-changed
        {--force : Tetap render ulang meskipun fingerprint data belum berubah}';

    protected $description = 'Refresh shared Label ST Hidup Detail PDF only when source data changes.';

    public function handle(LabelStHidupDetailReportService $reportService): int
    {
        $fingerprint = $reportService->buildFingerprint();
        $previous = $this->readPreviousFingerprint();

        if (! $this->option('force') && ($previous['hash'] ?? null) === $fingerprint['hash']) {
            $this->info('Data belum berubah. Render PDF shared dilewati.');

            return self::SUCCESS;
        }

        $this->info('Data berubah. Mulai render ulang PDF shared.');
        $exitCode = Artisan::call('reports:warm-label-st-hidup-detail-pdf', [
            '--force' => true,
        ]);

        $this->output->write(Artisan::output());

        if ($exitCode !== self::SUCCESS) {
            $this->error('Render ulang PDF shared gagal. Fingerprint tidak disimpan.');

            return self::FAILURE;
        }

        $this->writeFingerprint($fingerprint);
        $this->info("Fingerprint disimpan. Rows: {$fingerprint['row_count']}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function readPreviousFingerprint(): array
    {
        $path = $this->fingerprintPath();
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
    private function writeFingerprint(array $fingerprint): void
    {
        $path = $this->fingerprintPath();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        file_put_contents($path, json_encode($fingerprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
    }

    private function fingerprintPath(): string
    {
        return storage_path('app/pdf-report-fingerprints/label-st-hidup-detail.json');
    }
}
