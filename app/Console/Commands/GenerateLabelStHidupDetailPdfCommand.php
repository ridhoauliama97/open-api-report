<?php

namespace App\Console\Commands;

use App\Services\FilePdfJobStore;
use App\Services\LabelStHidupDetailReportService;
use App\Services\PdfGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateLabelStHidupDetailPdfCommand extends Command
{
    protected $signature = 'reports:generate-label-st-hidup-detail-pdf
        {jobId : File-based PDF job id}
        {--requested-by=async-job : User label printed in the PDF footer}';

    protected $description = 'Generate Label ST Hidup Detail PDF in a database-free background process.';

    public function handle(
        FilePdfJobStore $jobStore,
        LabelStHidupDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
    ): int {
        $jobId = (string) $this->argument('jobId');
        $requestedBy = (string) $this->option('requested-by');

        @ini_set('memory_limit', (string) env('LABEL_ST_HIDUP_DETAIL_PDF_MEMORY_LIMIT', '2048M'));
        @set_time_limit(3600);

        $jobStore->markProcessing($jobId);

        try {
            $reportData = $reportService->buildReportData();
            $storagePath = trim((string) config('app.pdf_storage_path', 'pdf_reports'), '/')
                .'/label-st-hidup-detail-'.now()->format('Ymd_His').'-'.substr($jobId, 0, 8).'.pdf';

            $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));
            $outputPath = $disk->path($storagePath);
            $outputDir = dirname($outputPath);
            if (! is_dir($outputDir)) {
                @mkdir($outputDir, 0777, true);
            }

            $pdfGenerator->renderToFile('reports.sawn-timber.label-st-hidup-detail-pdf', [
                'reportData' => $reportData,
                'generatedBy' => (object) [
                    'name' => $requestedBy,
                    'Username' => $requestedBy,
                ],
                'generatedAt' => now(),
                'pdf_orientation' => 'portrait',
                'pdf_simple_tables' => false,
            ], $outputPath);

            $jobStore->markDone($jobId, $storagePath);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $jobStore->markFailed($jobId, $exception->getMessage());

            report($exception);

            return self::FAILURE;
        }
    }
}
