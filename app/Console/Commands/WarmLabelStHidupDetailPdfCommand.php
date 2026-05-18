<?php

namespace App\Console\Commands;

use App\Services\FilePdfJobStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class WarmLabelStHidupDetailPdfCommand extends Command
{
    private const REPORT_TYPE = 'sawn-timber/label-st-hidup-detail';

    private const SHARED_REQUESTED_BY = 'system';

    protected $signature = 'reports:warm-label-st-hidup-detail-pdf
        {--force : Tetap render ulang meskipun PDF shared sudah tersedia}';

    protected $description = 'Pre-generate shared Label ST Hidup Detail PDF for all users.';

    public function handle(FilePdfJobStore $jobStore): int
    {
        if (! $this->option('force') && $jobStore->findLatestDone(self::REPORT_TYPE, self::SHARED_REQUESTED_BY) !== null) {
            $this->info('PDF shared masih tersedia. Render dilewati.');

            return self::SUCCESS;
        }

        $job = $jobStore->create(
            reportType: self::REPORT_TYPE,
            payload: ['warmup' => true],
            requestedBy: self::SHARED_REQUESTED_BY,
        );

        $this->info('Mulai render PDF shared. Job ID: '.$job['job_id']);

        $exitCode = Artisan::call('reports:generate-label-st-hidup-detail-pdf', [
            'jobId' => $job['job_id'],
            '--requested-by' => self::SHARED_REQUESTED_BY,
        ]);

        $this->output->write(Artisan::output());

        if ($exitCode !== self::SUCCESS) {
            $this->error('Render PDF shared gagal. Job ID: '.$job['job_id']);

            return self::FAILURE;
        }

        $this->info('Render PDF shared selesai. Job ID: '.$job['job_id']);

        return self::SUCCESS;
    }
}
