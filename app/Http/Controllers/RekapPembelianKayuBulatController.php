<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateNoParameterReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\RekapPembelianKayuBulatReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class RekapPembelianKayuBulatController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat.rekap-pembelian-form');
    }

    public function preview(
        GenerateNoParameterReportRequest $request,
        RekapPembelianKayuBulatReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Data rekap pembelian kayu bulat berhasil diambil.',
            'meta' => [
                'total_rows' => count($reportData['rows'] ?? []),
                'total_years' => $reportData['summary']['total_years'] ?? 0,
                'column_order' => array_keys($reportData['rows'][0] ?? []),
                'grand_total' => $reportData['grand_total'] ?? 0,
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData['rows'] ?? [],
            'grouped_data' => $reportData['year_rows'] ?? [],
        ]);
    }

    public function download(
        GenerateNoParameterReportRequest $request,
        RekapPembelianKayuBulatReportService $reportService,
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

        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.kayu-bulat.rekap-pembelian-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),

            'pdf_column_count' => 14,
        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),

            'pdf_column_count' => 14,
        ]);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        try {
            $pdfBytes = $gotenbergPdfClient->convertHtml($html, $metrics, $footerHtml);
        } catch (GotenbergPdfException $exception) {
            return $this->gotenbergFailureResponse($request, $exception->getMessage());
        }

        $filename = 'Laporan-Rekap-Pembelian-Kayu-Bulat-Ton.pdf';

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateNoParameterReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
    }

    public function health(
        GenerateNoParameterReportRequest $request,
        RekapPembelianKayuBulatReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapRekapPembelianKayuBulat valid.'
                : 'Struktur output SPWps_LapRekapPembelianKayuBulat berubah.',
            'health' => $result,
        ]);
    }
}
