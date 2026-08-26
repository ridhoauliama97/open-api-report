<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateStockRacipKayuLatReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\StockRacipKayuLatReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

        $viewData = [
            'reportData' => $reportData,
            'rows' => $reportData['rows'] ?? [],
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ];

        $html = $pdfGenerator->renderHtml('reports.stock-racip-kayu-lat-pdf', $viewData);

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

        $filename = sprintf('Laporan-Stok-Racip-Kayu-Lat-%s.pdf', $endDate);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateStockRacipKayuLatReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
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

    public function health(
        GenerateStockRacipKayuLatReportRequest $request,
        StockRacipKayuLatReportService $reportService,
    ): JsonResponse {
        $defaultEndDate = now()->format('Y-m-d');
        $endDate = $request->endDate($defaultEndDate);

        try {
            $result = $reportService->healthCheck($endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output sp_LapStockRacipKayuLat valid.'
                : 'Struktur output sp_LapStockRacipKayuLat berubah.',
            'meta' => [
                'end_date' => $endDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }
}
