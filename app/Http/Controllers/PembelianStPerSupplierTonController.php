<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateDateRangeReportRequest;
use App\Services\PdfGenerator;
use App\Services\PembelianStPerSupplierTonReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PembelianStPerSupplierTonController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.pembelian-st-per-supplier-ton-form');
    }

    public function previewPdf(
        GenerateDateRangeReportRequest $request,
        PembelianStPerSupplierTonReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, true);
    }

    public function download(
        GenerateDateRangeReportRequest $request,
        PembelianStPerSupplierTonReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, false);
    }

    private function renderPdf(
        GenerateDateRangeReportRequest $request,
        PembelianStPerSupplierTonReportService $reportService,
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

        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $jenisColumns = is_array($reportData['jenis_columns'] ?? null) ? $reportData['jenis_columns'] : [];
        $orientation = count($jenisColumns) > 5 ? 'landscape' : 'portrait';

        $pdf = $pdfGenerator->render('reports.sawn-timber.pembelian-st-per-supplier-ton-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'pdf_orientation' => $orientation,
            // Keep consistent with other "vertical-only borders" reports.
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = 'Laporan-Pembelian-ST-Per-Supplier-Ton.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename=\"%s\"', $inline ? 'inline' : 'attachment', $filename),
        ]);
    }

    public function preview(
        GenerateDateRangeReportRequest $request,
        PembelianStPerSupplierTonReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_suppliers' => (int) (($reportData['summary']['total_suppliers'] ?? 0)),
                'total_jenis' => (int) (($reportData['summary']['total_jenis'] ?? 0)),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateDateRangeReportRequest $request,
        PembelianStPerSupplierTonReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapPembelianSTPerSupplier valid.'
                : 'Struktur output SP_LapPembelianSTPerSupplier berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
