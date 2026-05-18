<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshSharedPdfReportsIfChangedCommand extends Command
{
    protected $signature = 'reports:refresh-shared-pdfs-if-changed
        {--stock-st-kering-end-date= : Tanggal akhir laporan Stock ST Kering}
        {--force : Tetap render ulang semua laporan meskipun fingerprint data belum berubah}';

    protected $description = 'Refresh all shared heavy PDFs only when their source data changes.';

    public function handle(): int
    {
        $hasFailure = false;

        $this->info('Mulai cek shared PDF: Label ST Hidup Detail.');
        $labelExitCode = Artisan::call('reports:refresh-label-st-hidup-detail-pdf-if-changed', [
            '--force' => (bool) $this->option('force'),
        ]);
        $this->output->write(Artisan::output());
        $hasFailure = $hasFailure || $labelExitCode !== self::SUCCESS;

        $this->info('Mulai cek shared PDF: Stock ST Kering.');
        $stockOptions = [
            '--force' => (bool) $this->option('force'),
        ];

        $stockEndDate = trim((string) $this->option('stock-st-kering-end-date'));
        if ($stockEndDate !== '') {
            $stockOptions['--end-date'] = $stockEndDate;
        }

        $stockExitCode = Artisan::call('reports:refresh-stock-st-kering-pdf-if-changed', $stockOptions);
        $this->output->write(Artisan::output());
        $hasFailure = $hasFailure || $stockExitCode !== self::SUCCESS;

        if ($hasFailure) {
            $this->error('Satu atau lebih shared PDF gagal direfresh.');

            return self::FAILURE;
        }

        $this->info('Semua shared PDF selesai dicek.');

        return self::SUCCESS;
    }
}
