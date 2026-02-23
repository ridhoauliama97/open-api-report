<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStockRacipKayuLatReportRequest;
use App\Services\PdfGenerator;
use App\Services\StockRacipKayuLatReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StockRacipKayuLatController extends Controller
{
    public function index(
        GenerateStockRacipKayuLatReportRequest $request,
        StockRacipKayuLatReportService $reportService,
    ): View {
        $defaultEndDate = now()->format('Y-m-d');
        $endDate = $request->endDate($defaultEndDate);

        $errorMessage = null;
        $reportData = [
            'rows' => [],
            'grouped_rows' => [],
            'summary' => [
                'total_rows' => 0,
                'total_batang' => 0.0,
                'total_hasil' => 0.0,
            ],
            'end_date_text' => $endDate,
            'column_order' => [],
        ];

        try {
            $reportData = $reportService->buildReportData($endDate);
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('reports.stock-racip-kayu-lat-form', [
            'endDate' => $endDate,
            'reportData' => $reportData,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function download(
        GenerateStockRacipKayuLatReportRequest $request,
        StockRacipKayuLatReportService $reportService,
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

        $defaultEndDate = now()->format('Y-m-d');
        $endDate = $request->endDate($defaultEndDate);

        try {
            $reportData = $reportService->buildReportData($endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.stock-racip-kayu-lat-pdf', [
            'reportData' => $reportData,
            'rows' => $reportData['rows'] ?? [],
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Stok-Racip-Kayu-Lat-%s.pdf', $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateStockRacipKayuLatReportRequest $request,
        StockRacipKayuLatReportService $reportService,
    ): JsonResponse {
        $defaultEndDate = now()->format('Y-m-d');
        $endDate = $request->endDate($defaultEndDate);

        try {
            $reportData = $reportService->buildReportData($endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'end_date' => $endDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($reportData['rows'] ?? []),
                'column_order' => $reportData['column_order'] ?? [],
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData['rows'] ?? [],
        ]);
    }
}
