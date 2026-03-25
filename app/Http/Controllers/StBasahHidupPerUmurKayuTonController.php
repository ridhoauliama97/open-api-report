<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateNoParameterReportRequest;
use App\Services\PdfGenerator;
use App\Services\StBasahHidupPerUmurKayuTonReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StBasahHidupPerUmurKayuTonController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.st-basah-hidup-per-umur-kayu-ton-form');
    }

    public function previewPdf(
        GenerateNoParameterReportRequest $request,
        StBasahHidupPerUmurKayuTonReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, true);
    }

    public function download(
        GenerateNoParameterReportRequest $request,
        StBasahHidupPerUmurKayuTonReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, false);
    }

    private function renderPdf(
        GenerateNoParameterReportRequest $request,
        StBasahHidupPerUmurKayuTonReportService $reportService,
        PdfGenerator $pdfGenerator,
        bool $inline,
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

        $pdf = $pdfGenerator->render('reports.sawn-timber.st-basah-hidup-per-umur-kayu-ton-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = 'Laporan-ST-Basah-Hidup-Per-Umur-Kayu-Ton.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $inline ? 'inline' : 'attachment', $filename),
        ]);
    }

    public function preview(
        GenerateNoParameterReportRequest $request,
        StBasahHidupPerUmurKayuTonReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'summary' => $reportData['summary'] ?? [],
            'totals' => $reportData['totals'] ?? null,
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateNoParameterReportRequest $request,
        StBasahHidupPerUmurKayuTonReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapSTBasahHidupPerUmurKayu valid.'
                : 'Struktur output SP_LapSTBasahHidupPerUmurKayu berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
