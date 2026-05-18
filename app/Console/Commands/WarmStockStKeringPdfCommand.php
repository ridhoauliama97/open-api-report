<?php

namespace App\Console\Commands;

use App\Services\FilePdfJobStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class WarmStockStKeringPdfCommand extends Command
{
    private const REPORT_TYPE = 'sawn-timber/stock-st-kering';

    private const SHARED_REQUESTED_BY = 'system';

    protected $signature = 'reports:warm-stock-st-kering-pdf
        {--end-date= : Tanggal akhir laporan Stock ST Kering}
        {--force : Tetap render ulang meskipun PDF shared sudah tersedia}';

    protected $description = 'Pre-generate shared Stock ST Kering PDF for all users.';

    public function handle(FilePdfJobStore $jobStore): int
    {
        $endDate = $this->resolveEndDate();
        $payload = ['end_date' => $endDate];

        if (! $this->option('force') && $jobStore->findLatestDoneMatchingPayload(self::REPORT_TYPE, $payload, self::SHARED_REQUESTED_BY) !== null) {
            $this->info("PDF shared Stock ST Kering {$endDate} masih tersedia. Render dilewati.");

            return self::SUCCESS;
        }

        $job = $jobStore->create(
            reportType: self::REPORT_TYPE,
            payload: $payload,
            requestedBy: self::SHARED_REQUESTED_BY,
        );

        $this->info("Mulai render PDF shared Stock ST Kering {$endDate}. Job ID: {$job['job_id']}");

        $exitCode = Artisan::call('reports:generate-stock-st-kering-pdf', [
            'jobId' => $job['job_id'],
            '--requested-by' => self::SHARED_REQUESTED_BY,
        ]);

        $this->output->write(Artisan::output());

        if ($exitCode !== self::SUCCESS) {
            $this->error('Render PDF shared Stock ST Kering gagal. Job ID: '.$job['job_id']);

            return self::FAILURE;
        }

        $this->info('Render PDF shared Stock ST Kering selesai. Job ID: '.$job['job_id']);

        return self::SUCCESS;
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
