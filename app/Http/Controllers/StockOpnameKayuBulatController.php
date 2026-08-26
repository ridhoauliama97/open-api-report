<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateStockOpnameKayuBulatReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\StockOpnameKayuBulatReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

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
        GotenbergPdfClient $gotenbergPdfClient,
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

        $viewData = [
            'rows' => $reportData['rows'],
            'groupedRows' => $reportData['grouped_rows'],
            'summary' => $reportData['summary'],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ];

        $html = $pdfGenerator->renderHtml('reports.kayu-bulat.stock-opname-pdf', $viewData);

        $metrics = $pdfGenerator->paperMetrics($viewData);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        try {
            $pdfBytes = $gotenbergPdfClient->convertHtml($html, $metrics, $footerHtml);
        } catch (GotenbergPdfException $exception) {
            return $this->gotenbergFailureResponse($request, $exception->getMessage());
        }

        $filename = 'Laporan-Stock-Opname-Kayu-Bulat.pdf';

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateStockOpnameKayuBulatReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
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
