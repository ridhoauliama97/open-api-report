<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStockOpnameKayuBulatReportRequest;
use App\Services\PdfGenerator;
use App\Services\StockOpnameKayuBulatReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class StockOpnameKayuBulatController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat.stock-opname-form');
    }

    public function download(
        GenerateStockOpnameKayuBulatReportRequest $request,
        StockOpnameKayuBulatReportService $reportService,
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

        $reportData = $reportService->buildReportData();

        $pdf = $pdfGenerator->render('reports.kayu-bulat.stock-opname-pdf', [
            'rows' => $reportData['rows'],
            'groupedRows' => $reportData['grouped_rows'],
            'summary' => $reportData['summary'],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = 'Laporan-Stock-Opname-Kayu-Bulat.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateStockOpnameKayuBulatReportRequest $request,
        StockOpnameKayuBulatReportService $reportService,
    ): JsonResponse {
        $reportData = $reportService->buildReportData();

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => count($reportData['rows'] ?? []),
                'column_order' => array_keys(($reportData['rows'][0] ?? [])),
            ],
            'summary' => $reportData['summary'],
            'data' => $reportData['rows'],
            'grouped_data' => $reportData['grouped_rows'],
        ]);
    }

    public function health(
        GenerateStockOpnameKayuBulatReportRequest $request,
        StockOpnameKayuBulatReportService $reportService,
    ): JsonResponse {
        $result = $reportService->healthCheck();

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output StockOpnameKayuBulat valid.'
                : 'Struktur output StockOpnameKayuBulat berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
