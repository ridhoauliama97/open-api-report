<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStockSTKeringReportRequest;
use App\Services\FilePdfJobStore;
use App\Services\PdfGenerator;
use App\Services\StockSTKeringReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;

class StockSTKeringController extends Controller
{
    private const REPORT_TYPE = 'sawn-timber/stock-st-kering';

    private const SHARED_REQUESTED_BY = 'system';

    public function index(): View
    {
        return view('reports.sawn-timber.stock-st-kering-form');
    }

    public function download(
        GenerateStockSTKeringReportRequest $request,
        StockSTKeringReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateStockSTKeringReportRequest $request,
        StockSTKeringReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function dispatchAsync(GenerateStockSTKeringReportRequest $request, FilePdfJobStore $jobStore): JsonResponse
    {
        return $this->dispatchFilePdfJob($request, $jobStore, 'reports.sawn-timber.stock-st-kering.async-status');
    }

    public function apiDispatchAsync(GenerateStockSTKeringReportRequest $request, FilePdfJobStore $jobStore): JsonResponse
    {
        return $this->dispatchFilePdfJob($request, $jobStore, 'api.reports.sawn-timber.stock-st-kering.async-status');
    }

    public function previewPdfWait(GenerateStockSTKeringReportRequest $request, FilePdfJobStore $jobStore): Response|JsonResponse
    {
        return $this->downloadWait($request, $jobStore, 'reports.sawn-timber.stock-st-kering.async-status');
    }

    public function apiDownloadWait(GenerateStockSTKeringReportRequest $request, FilePdfJobStore $jobStore): Response|JsonResponse
    {
        return $this->downloadWait($request, $jobStore, 'api.reports.sawn-timber.stock-st-kering.async-status');
    }

    public function asyncStatus(string $jobId, FilePdfJobStore $jobStore): JsonResponse
    {
        return $this->filePdfJobStatus($jobId, $jobStore, 'reports.sawn-timber.stock-st-kering.async-download');
    }

    public function apiAsyncStatus(string $jobId, FilePdfJobStore $jobStore): JsonResponse
    {
        return $this->filePdfJobStatus($jobId, $jobStore, 'api.reports.sawn-timber.stock-st-kering.async-download');
    }

    public function asyncDownload(string $jobId, FilePdfJobStore $jobStore): Response|JsonResponse
    {
        return $this->downloadFilePdfJob($jobId, $jobStore, false);
    }

    public function apiAsyncDownload(string $jobId, FilePdfJobStore $jobStore): Response|JsonResponse
    {
        return $this->downloadFilePdfJob($jobId, $jobStore, true);
    }

    public function preview(
        GenerateStockSTKeringReportRequest $request,
        StockSTKeringReportService $reportService,
    ): JsonResponse {
        $endDate = $request->endDate();

        try {
            $rows = $reportService->fetch($endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $previewLimit = $this->resolveWebPreviewLimit($request);
        $isTruncated = false;
        if ($previewLimit > 0 && count($rows) > $previewLimit) {
            $rows = array_slice($rows, 0, $previewLimit);
            $isTruncated = true;
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'end_date' => $endDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
                'is_truncated' => $isTruncated,
                'preview_limit' => $previewLimit,
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateStockSTKeringReportRequest $request,
        StockSTKeringReportService $reportService,
    ): JsonResponse {
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapStockSTKering valid.'
                : 'Struktur output SP_LapStockSTKering berubah.',
            'meta' => [
                'end_date' => $endDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function dispatchFilePdfJob(
        GenerateStockSTKeringReportRequest $request,
        FilePdfJobStore $jobStore,
        string $statusRouteName,
    ): JsonResponse {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            return response()->json(['message' => 'Silakan login terlebih dahulu untuk mencetak laporan.'], 401);
        }

        $requestedBy = (string) ($generatedBy->username ?? $generatedBy->Username ?? $generatedBy->name ?? 'unknown');
        $payload = ['end_date' => $request->endDate()];

        if ($payload['end_date'] === '') {
            return response()->json(['message' => 'Parameter end_date wajib diisi untuk membuat job PDF.'], 422);
        }

        if (! $request->boolean('force')) {
            $cachedJob = $this->findReusablePdfJob($jobStore, $payload, $requestedBy);
            if ($cachedJob !== null) {
                return response()->json([
                    'job_id' => $cachedJob['job_id'],
                    'status' => $cachedJob['status'],
                    'status_url' => route($statusRouteName, $cachedJob['job_id']),
                    'download_url' => route($this->downloadRouteNameForStatusRoute($statusRouteName), $cachedJob['job_id']),
                    'pdf_url' => route('api.reports.sawn-timber.stock-st-kering.pdf', ['job_id' => $cachedJob['job_id']]),
                    'message' => 'PDF sudah tersedia.',
                ]);
            }
        }

        $job = $jobStore->create(self::REPORT_TYPE, $payload, $requestedBy);
        $this->startBackgroundPdfProcess((string) $job['job_id'], $requestedBy);

        return response()->json([
            'job_id' => $job['job_id'],
            'status' => $job['status'],
            'status_url' => route($statusRouteName, $job['job_id']),
            'message' => 'PDF sedang diproses di background.',
        ], 202);
    }

    private function downloadWait(
        GenerateStockSTKeringReportRequest $request,
        FilePdfJobStore $jobStore,
        string $statusRouteName,
    ): Response|JsonResponse {
        $existingJobId = $request->query('job_id') ?? $request->input('job_id');
        if (is_string($existingJobId) && trim($existingJobId) !== '') {
            return $this->downloadFilePdfJob(trim($existingJobId), $jobStore, true);
        }

        $dispatchResponse = $this->dispatchFilePdfJob($request, $jobStore, $statusRouteName);
        $dispatchPayload = $dispatchResponse->getData(true);
        $jobId = (string) ($dispatchPayload['job_id'] ?? '');

        if (($dispatchPayload['status'] ?? null) === FilePdfJobStore::STATUS_DONE && $jobId !== '') {
            return $this->downloadFilePdfJob($jobId, $jobStore, true);
        }

        if ($jobId === '') {
            return $dispatchResponse;
        }

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
            $response['pdf_url'] = route('api.reports.sawn-timber.stock-st-kering.pdf', ['job_id' => $job['job_id']]);
            $response['expires_at'] = $job['expires_at'] ?? null;
        }

        if (($job['status'] ?? null) === FilePdfJobStore::STATUS_FAILED) {
            $response['error'] = $job['error_message'] ?? 'Job PDF gagal diproses.';
        }

        return response()->json($response);
    }

    private function downloadFilePdfJob(string $jobId, FilePdfJobStore $jobStore, bool $attachment): Response|JsonResponse
    {
        $job = $jobStore->find($jobId);

        if ($job === null) {
            return response()->json(['message' => 'Job tidak ditemukan.'], 404);
        }

        if (($job['status'] ?? null) !== FilePdfJobStore::STATUS_DONE) {
            return response()->json([
                'message' => 'PDF belum siap. Status saat ini: ' . ($job['status'] ?? 'unknown'),
                'status' => $job['status'] ?? 'unknown',
            ], 409);
        }

        $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));

        if (! is_string($job['file_path'] ?? null) || ! $disk->exists((string) $job['file_path'])) {
            return response()->json(['message' => 'File PDF tidak ditemukan. Mungkin sudah kadaluarsa.'], 410);
        }

        $filename = basename((string) $job['file_path']);
        $content = $disk->get((string) $job['file_path']);
        $disposition = $attachment ? 'attachment' : 'inline';

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
            'reports:generate-stock-st-kering-pdf',
            $jobId,
            '--requested-by=' . $requestedBy,
        ];

        if (PHP_OS_FAMILY === 'Windows') {
            $escaped = array_map('escapeshellarg', $command);
            pclose(popen('start /B "" ' . implode(' ', $escaped) . ' > NUL 2>&1', 'r'));

            return;
        }

        $escaped = implode(' ', array_map('escapeshellarg', $command));
        exec($escaped . ' > /dev/null 2>&1 &');
    }

    private function downloadRouteNameForStatusRoute(string $statusRouteName): string
    {
        return $statusRouteName === 'api.reports.sawn-timber.stock-st-kering.async-status'
            ? 'api.reports.sawn-timber.stock-st-kering.async-download'
            : 'reports.sawn-timber.stock-st-kering.async-download';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function findReusablePdfJob(FilePdfJobStore $jobStore, array $payload, string $requestedBy): ?array
    {
        return $jobStore->findLatestDoneMatchingPayload(self::REPORT_TYPE, $payload, self::SHARED_REQUESTED_BY)
            ?? $jobStore->findLatestDoneMatchingPayload(self::REPORT_TYPE, $payload, $requestedBy);
    }

    private function buildPdfResponse(
        GenerateStockSTKeringReportRequest $request,
        StockSTKeringReportService $reportService,
        PdfGenerator $pdfGenerator,
        bool $attachment,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return back()
                ->withInput()
                ->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        $endDate = $request->endDate();

        $existingJobId = $request->query('job_id') ?? $request->input('job_id');
        if (is_string($existingJobId) && trim($existingJobId) !== '') {
            return $this->downloadFilePdfJob(trim($existingJobId), app(FilePdfJobStore::class), $attachment || $request->is('api/*'));
        }

        try {
            $rows = $reportService->fetch($endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $summaryStats = $this->buildSummaryStats($rows);

        if ($attachment) {
            $previewPdfLimit = (int) config('reports.stock_st_kering.preview_pdf_max_rows', 0);
            if ($previewPdfLimit > 0 && count($rows) > $previewPdfLimit) {
                $rows = array_slice($rows, 0, $previewPdfLimit);
            }
        }

        @ini_set('memory_limit', (string) env('STOCK_ST_KERING_PDF_MEMORY_LIMIT', '1024M'));
        @set_time_limit(0);

        $payload = [
            'rows' => $rows,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'summaryStats' => $summaryStats,
            'pdf_simple_tables' => false,
        ];

        $filename = sprintf('Laporan-Stock-ST-Kering-%s.pdf', $endDate);
        $dispositionType = $attachment ? 'attachment' : 'inline';

        $dir = storage_path('app/pdf-temp');
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $tmpPath = $dir . DIRECTORY_SEPARATOR . uniqid('stock-st-kering-', true) . '.pdf';

        $pdfGenerator->renderToFile('reports.sawn-timber.stock-st-kering-pdf', $payload, $tmpPath);

        return response()
            ->file($tmpPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
            ])
            ->deleteFileAfterSend(true);
    }

    private function resolveWebPreviewLimit(GenerateStockSTKeringReportRequest $request): int
    {
        // Keep API preview untouched; limit only web preview JSON for faster UI response.
        if ($request->is('api/*')) {
            return 0;
        }

        return (int) config('reports.stock_st_kering.preview_json_max_rows', 100);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, int|float>
     */
    private function buildSummaryStats(array $rows): array
    {
        $normalize = static function (?string $name): string {
            return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name ?? '') ?? '');
        };

        $findColumn = static function (array $columns, array $candidates) use ($normalize): ?string {
            $candidateSet = [];
            foreach ($candidates as $candidate) {
                $candidateSet[$normalize((string) $candidate)] = true;
            }

            foreach ($columns as $column) {
                if (isset($candidateSet[$normalize((string) $column)])) {
                    return (string) $column;
                }
            }

            return null;
        };

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (! is_string($value)) {
                return null;
            }

            $normalized = trim(str_replace(' ', '', $value));
            if ($normalized === '') {
                return null;
            }

            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                    $normalized = str_replace('.', '', $normalized);
                    $normalized = str_replace(',', '.', $normalized);
                } else {
                    $normalized = str_replace(',', '', $normalized);
                }
            } elseif (str_contains($normalized, ',')) {
                $normalized = str_replace(',', '.', $normalized);
            }

            return is_numeric($normalized) ? (float) $normalized : null;
        };

        $columns = array_keys($rows[0] ?? []);
        $jenisColumn = $findColumn($columns, ['Jenis', 'JenisKayu', 'Type', 'Tipe', 'Kategori']);
        $produkColumn = $findColumn($columns, ['Produk', 'Product', 'NamaProduk', 'NamaBarang', 'Item']);
        $pcsColumn = $findColumn($columns, ['Pcs', 'JmlhBatang', 'JumlahBatang']);
        $tonColumn = $findColumn($columns, ['Ton', 'JmlhTon', 'JumlahTon']);

        $jenisSet = [];
        $produkPairSet = [];
        $produkSet = [];
        $totalPcs = 0.0;
        $totalTon = 0.0;

        foreach ($rows as $row) {
            $jenis = trim((string) ($jenisColumn !== null ? ($row[$jenisColumn] ?? '') : ''));
            $produk = trim((string) ($produkColumn !== null ? ($row[$produkColumn] ?? '') : ''));
            $jenis = $jenis !== '' ? $jenis : 'Tanpa Jenis';
            $produk = $produk !== '' ? $produk : 'Tanpa Produk';

            $jenisSet[$jenis] = true;
            $produkPairSet[$jenis . '||' . $produk] = true;
            $produkSet[$produk] = true;

            $totalPcs += $pcsColumn !== null ? ($toFloat($row[$pcsColumn] ?? null) ?? 0.0) : 0.0;
            $totalTon += $tonColumn !== null ? ($toFloat($row[$tonColumn] ?? null) ?? 0.0) : 0.0;
        }

        return [
            'total_rows' => count($rows),
            'total_jenis' => count($jenisSet),
            'total_produk' => count($produkPairSet),
            'total_produk_unik' => count($produkSet),
            'total_pcs' => $totalPcs,
            'total_ton' => $totalTon,
        ];
    }
}
