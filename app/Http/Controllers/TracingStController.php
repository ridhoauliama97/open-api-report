<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateTracingStReportRequest;
use App\Services\PdfGenerator;
use App\Services\TracingStReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TracingStController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.tracing-st-form');
    }

    public function download(
        GenerateTracingStReportRequest $request,
        TracingStReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateTracingStReportRequest $request,
        TracingStReportService $reportService,
        PdfGenerator $pdfGenerator,
        ?string $filename = null,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateTracingStReportRequest $request,
        TracingStReportService $reportService,
    ): JsonResponse {
        $noProduk = $request->noProduk();

        try {
            $reportData = $reportService->buildReportData($noProduk);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'no_produk' => $noProduk,
                'NoProduk' => $noProduk,
                'total_rows' => count($reportData['rows'] ?? []),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'data' => $reportData['rows'] ?? [],
            'report_data' => $reportData,
        ]);
    }

    public function health(
        GenerateTracingStReportRequest $request,
        TracingStReportService $reportService,
    ): JsonResponse {
        $noProduk = $request->noProduk();

        try {
            $result = $reportService->healthCheck($noProduk);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapTracingST valid.'
                : 'Struktur output SP_LapTracingST berubah.',
            'meta' => [
                'no_produk' => $noProduk,
                'NoProduk' => $noProduk,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateTracingStReportRequest $request,
        TracingStReportService $reportService,
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

        $noProduk = $request->noProduk();

        try {
            $reportData = $reportService->buildReportData($noProduk);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $safeNoProduk = preg_replace('/[^A-Za-z0-9-]+/', '-', $noProduk) ?: 'tanpa-nomor';
        $safeNoProduk = trim($safeNoProduk, '-');
        $filename = sprintf('Laporan-Tracing-ST-%s.pdf', $safeNoProduk !== '' ? $safeNoProduk : 'tanpa-nomor');

        $pdf = $pdfGenerator->render('reports.sawn-timber.tracing-st-pdf', [
            'reportData' => $reportData,
            'noProduk' => $noProduk,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_format' => 'A6',
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_title' => $filename,
        ]);

        $dispositionType = $attachment ? 'attachment' : 'attachment';
        $disposition = sprintf(
            '%s; filename="%s"; filename*=UTF-8\'\'%s',
            $dispositionType,
            addcslashes($filename, '\\"'),
            rawurlencode($filename),
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
