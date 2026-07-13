<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStockSTBasahReportRequest;
use App\Services\PdfGenerator;
use App\Services\StockSTBasahReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class StockSTBasahController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.stock-st-basah-form');
    }

    public function download(
        GenerateStockSTBasahReportRequest $request,
        StockSTBasahReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateStockSTBasahReportRequest $request,
        StockSTBasahReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateStockSTBasahReportRequest $request,
        StockSTBasahReportService $reportService,
    ): JsonResponse {
        $endDate = $request->endDate();

        try {
            $rows = $reportService->fetch($endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'end_date' => $endDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateStockSTBasahReportRequest $request,
        StockSTBasahReportService $reportService,
    ): JsonResponse {
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapStockSTBasah valid.'
                : 'Struktur output SP_LapStockSTBasah berubah.',
            'meta' => [
                'end_date' => $endDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateStockSTBasahReportRequest $request,
        StockSTBasahReportService $reportService,
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
        $filename = sprintf('Laporan-Stock-ST-Basah-%s.pdf', $endDate);
        $dispositionType = $attachment ? 'attachment' : 'attachment';
        $cacheKey = $this->pdfCacheKey($endDate, $generatedBy);

        if (!$request->boolean('pdf_disable_cache')) {
            $cachedPdf = $this->getCachedPdf($cacheKey);
            if (is_string($cachedPdf) && $cachedPdf !== '') {
                return response($cachedPdf, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
                    'X-Report-Cache' => 'HIT',
                ]);
            }
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

        $pdf = $pdfGenerator->render('reports.sawn-timber.stock-st-basah-pdf', [
            'rows' => $rows,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        if (!$request->boolean('pdf_disable_cache')) {
            $this->putCachedPdf($cacheKey, $pdf);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
            'X-Report-Cache' => 'MISS',
        ]);
    }

    private function pdfCacheKey(string $endDate, object $generatedBy): string
    {
        $userKey = (string) ($generatedBy->username
            ?? $generatedBy->Username
            ?? $generatedBy->email
            ?? $generatedBy->Email
            ?? $generatedBy->name
            ?? $generatedBy->Nama
            ?? 'unknown');

        return 'report-pdf:stock-st-basah:' . hash('sha256', $endDate . '|' . $userKey);
    }

    private function getCachedPdf(string $cacheKey): ?string
    {
        $cacheTtl = (int) config('app.pdf_render_cache_ttl_seconds', 0);
        if ($cacheTtl <= 0) {
            return null;
        }

        try {
            $cacheStore = trim((string) config('app.pdf_render_cache_store', 'file'));
            $store = $cacheStore !== '' ? Cache::store($cacheStore) : Cache::store();
            $cachedPdf = $store->get($cacheKey);

            return is_string($cachedPdf) ? $cachedPdf : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function putCachedPdf(string $cacheKey, string $pdf): void
    {
        $cacheTtl = (int) config('app.pdf_render_cache_ttl_seconds', 0);
        if ($cacheTtl <= 0 || $pdf === '') {
            return;
        }

        try {
            $cacheStore = trim((string) config('app.pdf_render_cache_store', 'file'));
            $store = $cacheStore !== '' ? Cache::store($cacheStore) : Cache::store();
            $store->put($cacheKey, $pdf, now()->addSeconds($cacheTtl));
        } catch (Throwable) {
            return;
        }
    }
}
