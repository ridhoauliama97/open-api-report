<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateStockGilinganReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\StockGilinganReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StockGilinganController extends Controller
{
    public function index(): View
    {
        return view('pps.gilingan.stock_gilingan.form');
    }

    public function download(
        GenerateStockGilinganReportRequest $request,
        StockGilinganReportService $reportService,
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

        $pdf = $pdfGenerator->render('pps.gilingan.stock_gilingan.pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'warehouseName' => $warehouseName,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Stock-Gilingan-%s.pdf', $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateStockGilinganReportRequest $request,
        StockGilinganReportService $reportService,
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
        GenerateStockGilinganReportRequest $request,
        StockGilinganReportService $reportService,
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
                ? 'Struktur output SP_LaporanStockLabelGilingan valid.'
                : 'Struktur output SP_LaporanStockLabelGilingan berubah.',
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

    private function resolveGeneratedBy(GenerateStockGilinganReportRequest $request): object
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
