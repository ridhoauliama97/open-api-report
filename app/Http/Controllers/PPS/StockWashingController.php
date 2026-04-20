<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateStockWashingReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\StockWashingReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StockWashingController extends Controller
{
    public function index(): View
    {
        return view('pps.washing.stock_washing.form');
    }

    public function download(
        GenerateStockWashingReportRequest $request,
        StockWashingReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        [$startDate, $endDate] = $request->reportDates();
        $warehouseName = $request->warehouseName();
        $generatedBy = $this->resolveGeneratedBy($request);

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

        $pdf = $pdfGenerator->render('pps.washing.stock_washing.pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'warehouseName' => $warehouseName,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Stock-Washing-%s.pdf', $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateStockWashingReportRequest $request,
        StockWashingReportService $reportService,
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
        GenerateStockWashingReportRequest $request,
        StockWashingReportService $reportService,
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
                ? 'Struktur output SP_LaporanStockLabelWashing valid.'
                : 'Struktur output SP_LaporanStockLabelWashing berubah.',
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

    private function resolveGeneratedBy(GenerateStockWashingReportRequest $request): object
    {
        $webUser = $request->user() ?? auth('api')->user();
        if ($webUser !== null) {
            $name = (string) ($webUser->name ?? $webUser->Username ?? 'sistem');

            return (object) ['name' => $name];
        }

        $claims = $request->attributes->get('report_token_claims');
        if (is_array($claims)) {
            $name = (string) ($claims['name'] ?? $claims['username'] ?? 'api');

            return (object) ['name' => $name];
        }

        return (object) ['name' => 'sistem'];
    }
}
