<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStockHidupPerNoSpkReportRequest;
use App\Services\PdfGenerator;
use App\Services\StockHidupPerNoSpkDiscrepancyReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StockHidupPerNoSpkDiscrepancyController extends Controller
{
    public function index(GenerateStockHidupPerNoSpkReportRequest $request): View
    {
        return view('reports.management.stock-hidup-per-nospk-discrepancy-form', [
            'tanggalAkhir' => $request->tanggalAkhir(),
        ]);
    }

    public function download(
        GenerateStockHidupPerNoSpkReportRequest $request,
        StockHidupPerNoSpkDiscrepancyReportService $reportService,
        PdfGenerator $pdfGenerator,
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

        $tanggalAkhir = $request->tanggalAkhir();

        try {
            $reportData = $reportService->buildReportData($tanggalAkhir);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.management.stock-hidup-per-nospk-discrepancy-pdf', [
            'tanggalAkhir' => $tanggalAkhir,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf('Laporan-Stock-Hidup-Per-NoSPK-Discrepancy-%s.pdf', $tanggalAkhir);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateStockHidupPerNoSpkReportRequest $request,
        StockHidupPerNoSpkDiscrepancyReportService $reportService,
    ): JsonResponse {
        $tanggalAkhir = $request->tanggalAkhir();

        try {
            $reportData = $reportService->buildReportData($tanggalAkhir);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'TglAkhir' => $tanggalAkhir,
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_categories' => (int) ($reportData['summary']['total_categories'] ?? 0),
                'total_spk' => (int) ($reportData['summary']['total_spk'] ?? 0),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateStockHidupPerNoSpkReportRequest $request,
        StockHidupPerNoSpkDiscrepancyReportService $reportService,
    ): JsonResponse {
        $tanggalAkhir = $request->tanggalAkhir();

        try {
            $result = $reportService->healthCheck($tanggalAkhir);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapSemuaStockHidupPerSPK valid.'
                : 'Struktur output SP_LapSemuaStockHidupPerSPK berubah.',
            'meta' => ['TglAkhir' => $tanggalAkhir],
            'health' => $result,
        ]);
    }
}
