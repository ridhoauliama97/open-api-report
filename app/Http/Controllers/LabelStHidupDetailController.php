<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateNoParameterReportRequest;
use App\Services\FilePdfJobStore;
use App\Services\LabelStHidupDetailReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;

class LabelStHidupDetailController extends Controller
{
    private const PREVIEW_ROW_LIMIT = 500;

    private const REPORT_TYPE = 'sawn-timber/label-st-hidup-detail';

    private const SHARED_REQUESTED_BY = 'system';

    public function index(): View
    {
        return view('reports.sawn-timber.label-st-hidup-detail-form');
    }

    public function previewPdf(
        GenerateNoParameterReportRequest $request,
        LabelStHidupDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
        FilePdfJobStore $jobStore,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, $jobStore, true);
    }

    public function download(
        GenerateNoParameterReportRequest $request,
        LabelStHidupDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
        FilePdfJobStore $jobStore,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, $jobStore, false);
    }

    public function dispatchAsync(GenerateNoParameterReportRequest $request, FilePdfJobStore $jobStore): JsonResponse
    {
        return $this->dispatchFilePdfJob(
            $request,
            $jobStore,
            'reports.sawn-timber.label-st-hidup-detail.async-status',
        );
    }

    public function apiDispatchAsync(GenerateNoParameterReportRequest $request, FilePdfJobStore $jobStore): JsonResponse
    {
        return $this->dispatchFilePdfJob(
            $request,
            $jobStore,
            'api.reports.sawn-timber.label-st-hidup-detail.async-status',
        );
    }

    private function dispatchFilePdfJob(
        GenerateNoParameterReportRequest $request,
        FilePdfJobStore $jobStore,
        string $statusRouteName,
    ): JsonResponse {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            return response()->json(['message' => 'Silakan login terlebih dahulu untuk mencetak laporan.'], 401);
        }

        $requestedBy = (string) ($generatedBy->username ?? $generatedBy->Username ?? $generatedBy->name ?? 'unknown');
        if (! $request->boolean('force')) {
            $cachedJob = $this->findReusablePdfJob($jobStore, $requestedBy);
            if ($cachedJob !== null) {
                return response()->json([
                    'job_id' => $cachedJob['job_id'],
                    'status' => $cachedJob['status'],
                    'status_url' => route($statusRouteName, $cachedJob['job_id']),
                    'download_url' => $this->resolveDownloadUrlForStatusRoute($statusRouteName, (string) $cachedJob['job_id']),
                    'pdf_url' => route('api.reports.sawn-timber.label-st-hidup-detail.pdf', ['job_id' => $cachedJob['job_id']]),
                    'message' => 'PDF sudah tersedia.',
                ]);
            }
        }

        $job = $jobStore->create(
            reportType: self::REPORT_TYPE,
            payload: $request->validated(),
            requestedBy: $requestedBy,
        );

        $this->startBackgroundPdfProcess((string) $job['job_id'], $requestedBy);

        return response()->json([
            'job_id' => $job['job_id'],
            'status' => $job['status'],
            'status_url' => route($statusRouteName, $job['job_id']),
            'message' => 'PDF sedang diproses di background.',
        ], 202);
    }

    private function resolveDownloadUrlForStatusRoute(string $statusRouteName, string $jobId): string
    {
        $downloadRouteName = $statusRouteName === 'api.reports.sawn-timber.label-st-hidup-detail.async-status'
            ? 'api.reports.sawn-timber.label-st-hidup-detail.async-download'
            : 'reports.sawn-timber.label-st-hidup-detail.async-download';

        return route($downloadRouteName, $jobId);
    }

    public function asyncStatus(string $jobId, FilePdfJobStore $jobStore): JsonResponse
    {
        return $this->filePdfJobStatus(
            $jobId,
            $jobStore,
            'reports.sawn-timber.label-st-hidup-detail.async-download',
        );
    }

    public function apiAsyncStatus(string $jobId, FilePdfJobStore $jobStore): JsonResponse
    {
        return $this->filePdfJobStatus(
            $jobId,
            $jobStore,
            'api.reports.sawn-timber.label-st-hidup-detail.async-download',
        );
    }

    private function filePdfJobStatus(string $jobId, FilePdfJobStore $jobStore, string $downloadRouteName): JsonResponse
    {
        $job = $jobStore->find($jobId);

        if ($job === null) {
            return response()->json(['message' => 'Job tidak ditemukan.'], 404);
        }

        $response = [
            'job_id' => $job['job_id'] ?? $jobId,
            'status' => $job['status'] ?? FilePdfJobStore::STATUS_FAILED,
            'created_at' => $job['created_at'] ?? null,
        ];

        if (($job['status'] ?? null) === FilePdfJobStore::STATUS_DONE) {
            $response['download_url'] = route($downloadRouteName, $job['job_id']);
            $response['pdf_url'] = route('api.reports.sawn-timber.label-st-hidup-detail.pdf', ['job_id' => $job['job_id']]);
            $response['expires_at'] = $job['expires_at'] ?? null;
        }

        if (($job['status'] ?? null) === FilePdfJobStore::STATUS_FAILED) {
            $response['error'] = $job['error_message'] ?? 'Job PDF gagal diproses.';
        }

        return response()->json($response);
    }

    public function asyncDownload(string $jobId, FilePdfJobStore $jobStore): Response|JsonResponse
    {
        return $this->downloadFilePdfJob($jobId, $jobStore, false);
    }

    public function apiAsyncDownload(string $jobId, FilePdfJobStore $jobStore): Response|JsonResponse
    {
        return $this->downloadFilePdfJob($jobId, $jobStore, true);
    }

    public function apiDownloadWait(GenerateNoParameterReportRequest $request, FilePdfJobStore $jobStore): Response|JsonResponse
    {
        return $this->downloadWait(
            $request,
            $jobStore,
            'api.reports.sawn-timber.label-st-hidup-detail.async-status',
            'Unauthenticated.',
        );
    }

    public function previewPdfWait(GenerateNoParameterReportRequest $request, FilePdfJobStore $jobStore): Response|JsonResponse
    {
        return $this->downloadWait(
            $request,
            $jobStore,
            'reports.sawn-timber.label-st-hidup-detail.async-status',
            'Silakan login terlebih dahulu untuk mencetak laporan.',
        );
    }

    private function downloadWait(
        GenerateNoParameterReportRequest $request,
        FilePdfJobStore $jobStore,
        string $statusRouteName,
        string $unauthenticatedMessage,
    ): Response|JsonResponse {
        $existingJobId = $request->query('job_id') ?? $request->input('job_id');
        if (is_string($existingJobId) && trim($existingJobId) !== '') {
            return $this->downloadFilePdfJob(trim($existingJobId), $jobStore, true);
        }

        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            return response()->json(['message' => $unauthenticatedMessage], 401);
        }

        $requestedBy = (string) ($generatedBy->username ?? $generatedBy->Username ?? $generatedBy->name ?? 'unknown');
        if (! $request->boolean('force')) {
            $cachedJob = $this->findReusablePdfJob($jobStore, $requestedBy);
            if ($cachedJob !== null) {
                return $this->downloadFilePdfJob((string) $cachedJob['job_id'], $jobStore, true);
            }
        }

        $job = $jobStore->create(
            reportType: self::REPORT_TYPE,
            payload: $request->validated(),
            requestedBy: $requestedBy,
        );

        $jobId = (string) $job['job_id'];
        $this->startBackgroundPdfProcess($jobId, $requestedBy);

        $timeoutSeconds = min(max((int) $request->integer('wait_timeout', 600), 30), 1800);
        @set_time_limit($timeoutSeconds + 30);

        $deadline = microtime(true) + $timeoutSeconds;

        do {
            $latestJob = $jobStore->find($jobId);

            if (($latestJob['status'] ?? null) === FilePdfJobStore::STATUS_DONE) {
                return $this->downloadFilePdfJob($jobId, $jobStore, true);
            }

            if (($latestJob['status'] ?? null) === FilePdfJobStore::STATUS_FAILED) {
                return response()->json([
                    'job_id' => $jobId,
                    'status' => FilePdfJobStore::STATUS_FAILED,
                    'message' => $latestJob['error_message'] ?? 'Job PDF gagal diproses.',
                ], 422);
            }

            usleep(2_000_000);
        } while (microtime(true) < $deadline);

        return response()->json([
            'job_id' => $jobId,
            'status' => $jobStore->find($jobId)['status'] ?? FilePdfJobStore::STATUS_PROCESSING,
            'status_url' => route($statusRouteName, $jobId),
            'message' => 'PDF masih diproses. Cek status_url atau buka kembali endpoint ini dengan job_id.',
        ], 202);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findReusablePdfJob(FilePdfJobStore $jobStore, string $requestedBy): ?array
    {
        return $jobStore->findLatestDone(self::REPORT_TYPE, self::SHARED_REQUESTED_BY)
            ?? $jobStore->findLatestDone(self::REPORT_TYPE, $requestedBy);
    }

    private function downloadFilePdfJob(string $jobId, FilePdfJobStore $jobStore, bool $attachment): Response|JsonResponse
    {
        $job = $jobStore->find($jobId);

        if ($job === null) {
            return response()->json(['message' => 'Job tidak ditemukan.'], 404);
        }

        if (($job['status'] ?? null) !== FilePdfJobStore::STATUS_DONE) {
            return response()->json([
                'message' => 'PDF belum siap. Status saat ini: '.($job['status'] ?? 'unknown'),
                'status' => $job['status'] ?? 'unknown',
            ], 409);
        }

        $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));

        if (! is_string($job['file_path'] ?? null) || ! $disk->exists((string) $job['file_path'])) {
            return response()->json(['message' => 'File PDF tidak ditemukan. Mungkin sudah kadaluarsa.'], 410);
        }

        $filename = basename((string) $job['file_path']);
        $content = $disk->get((string) $job['file_path']);
        $disposition = $attachment ? 'attachment' : 'attachment';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }

    private function startBackgroundPdfProcess(string $jobId, string $requestedBy): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $php = (new PhpExecutableFinder)->find(false) ?: PHP_BINARY;
        $artisan = base_path('artisan');
        $command = [
            $php,
            $artisan,
            'reports:generate-label-st-hidup-detail-pdf',
            $jobId,
            '--requested-by='.$requestedBy,
        ];

        if (PHP_OS_FAMILY === 'Windows') {
            $escaped = array_map('escapeshellarg', $command);
            pclose(popen('start /B "" '.implode(' ', $escaped).' > NUL 2>&1', 'r'));

            return;
        }

        $escaped = implode(' ', array_map('escapeshellarg', $command));
        exec($escaped.' > /dev/null 2>&1 &');
    }

    private function renderPdf(
        GenerateNoParameterReportRequest $request,
        LabelStHidupDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
        FilePdfJobStore $jobStore,
        bool $attachment,
    ) {
        $existingJobId = $request->query('job_id') ?? $request->input('job_id');
        if (is_string($existingJobId) && trim($existingJobId) !== '') {
            return $this->downloadFilePdfJob(trim($existingJobId), $jobStore, true);
        }

        // This report can be extremely large (10k+ rows). mPDF keeps page buffers in memory
        // until final output, so raise memory/time limits for both attachment preview and download.
        @ini_set('memory_limit', (string) env('LABEL_ST_HIDUP_DETAIL_PDF_MEMORY_LIMIT', '2048M'));
        @set_time_limit(0);

        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return back()
                ->withInput()
                ->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $payload = [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            // Large tables: keep mPDF in its simpler table rendering mode.
            'pdf_simple_tables' => false,
            // 'pdf_column_count' => 11,
        ];

        $filename = 'Laporan-Label-ST-Hidup-Detail.pdf';

        $dir = storage_path('app/pdf-temp');
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $tmpPath = $dir.DIRECTORY_SEPARATOR.uniqid('label-st-hidup-detail-', true).'.pdf';

        $pdfGenerator->renderToFile('reports.sawn-timber.label-st-hidup-detail-pdf', $payload, $tmpPath);

        $disposition = $attachment ? 'attachment' : 'attachment';

        return response()
            ->file($tmpPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
            ])
            ->deleteFileAfterSend(true);
    }

    public function preview(
        GenerateNoParameterReportRequest $request,
        LabelStHidupDetailReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $reportData = $this->limitPreviewRows($reportData);

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => (int) (($reportData['summary']['total_rows'] ?? 0)),
                'preview_row_limit' => self::PREVIEW_ROW_LIMIT,
                'preview_rows' => count($reportData['rows'] ?? []),
                'column_order' => array_keys(($reportData['rows'][0] ?? []) ?: []),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData['rows'] ?? [],
        ]);
    }

    public function health(
        GenerateNoParameterReportRequest $request,
        LabelStHidupDetailReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapLabelSTHidupDetail valid.'
                : 'Struktur output SP_LapLabelSTHidupDetail berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array<string, mixed>
     */
    private function limitPreviewRows(array $reportData): array
    {
        $rows = $reportData['rows'] ?? null;
        if (! is_array($rows) || $rows === []) {
            return $reportData;
        }

        $reportData['rows'] = array_slice($rows, 0, self::PREVIEW_ROW_LIMIT);

        return $reportData;
    }
}
