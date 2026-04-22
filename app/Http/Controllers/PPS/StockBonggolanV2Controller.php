<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateStockBonggolanV2ReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\StockBonggolanV2ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StockBonggolanV2Controller extends Controller
{
    public function index(): View
    {
        return view('pps.bonggolan.stock_bonggolan.form');
    }

    public function download(
        GenerateStockBonggolanV2ReportRequest $request,
        StockBonggolanV2ReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        [$startDate, $endDate] = $request->reportDates();
        $warehouseName = $request->warehouseName();
        $generatedBy = $this->resolveReportGeneratedBy($request);

        try {
            $rows = $reportService->fetch($startDate, $endDate, $warehouseName);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('pps.bonggolan.stock_bonggolan.pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'warehouseName' => $warehouseName,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Stock-Bonggolan-%s.pdf', $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateStockBonggolanV2ReportRequest $request,
        StockBonggolanV2ReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $request->reportDates();
        $warehouseName = $request->warehouseName();

        try {
            $rows = $reportService->fetch($startDate, $endDate, $warehouseName);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'warehouse_name' => $warehouseName,
                'WarehouseName' => $warehouseName,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateStockBonggolanV2ReportRequest $request,
        StockBonggolanV2ReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $request->reportDates();
        $warehouseName = $request->warehouseName();

        try {
            $result = $reportService->healthCheck($startDate, $endDate, $warehouseName);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LaporanStockLabelBonggolan valid.'
                : 'Struktur output SP_LaporanStockLabelBonggolan berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'warehouse_name' => $warehouseName,
                'WarehouseName' => $warehouseName,
            ],
            'health' => $result,
        ]);
    }
}
