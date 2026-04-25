<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateStockBahanBakuV2ReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\StockBahanBakuV2ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StockBahanBakuV2Controller extends Controller
{
    public function index(): View
    {
        return view('pps.bahan_baku.stock_bahan_baku_v2.form');
    }

    public function download(
        GenerateStockBahanBakuV2ReportRequest $request,
        StockBahanBakuV2ReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        [$startDate, $endDate] = $request->reportDates();
        $warehouse = $request->warehouse();
        $generatedBy = $this->resolveReportGeneratedBy($request);

        try {
            $rows = $reportService->fetch($startDate, $endDate, $warehouse);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('pps.bahan_baku.stock_bahan_baku_v2.pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'warehouse' => $warehouse,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_orientation' => 'landscape',
        ]);

        $filename = sprintf('Laporan-Stok-Bahan-Baku-V2-%s.pdf', $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateStockBahanBakuV2ReportRequest $request,
        StockBahanBakuV2ReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $request->reportDates();
        $warehouse = $request->warehouse();

        try {
            $rows = $reportService->fetch($startDate, $endDate, $warehouse);
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
                'warehouse' => $warehouse,
                'Warehouse' => $warehouse,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateStockBahanBakuV2ReportRequest $request,
        StockBahanBakuV2ReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $request->reportDates();
        $warehouse = $request->warehouse();

        try {
            $result = $reportService->healthCheck($startDate, $endDate, $warehouse);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapStokBahanBakuV2 valid.'
                : 'Struktur output SP_LapStokBahanBakuV2 berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'warehouse' => $warehouse,
                'Warehouse' => $warehouse,
            ],
            'health' => $result,
        ]);
    }
}
