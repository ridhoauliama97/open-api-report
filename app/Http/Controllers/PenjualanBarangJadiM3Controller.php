<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GeneratePenjualanBarangJadiM3ReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\PenjualanBarangJadiM3ReportService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PenjualanBarangJadiM3Controller extends Controller
{
    public function preview(
        GeneratePenjualanBarangJadiM3ReportRequest $request,
        PenjualanBarangJadiM3ReportService $reportService,
    ): JsonResponse {
        $noJual = $request->noJual();

        try {
            $reportData = $reportService->buildReportData($noJual);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'no_jual' => $noJual,
                'NoJual' => $noJual,
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_pcs' => (int) ($reportData['summary']['total_pcs'] ?? 0),
                'grand_total_m3' => (float) ($reportData['summary']['grand_total_m3'] ?? 0.0),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'data' => $reportData['rows'] ?? [],
            'report_data' => $reportData,
        ]);
    }

    public function download(
        GeneratePenjualanBarangJadiM3ReportRequest $request,
        PenjualanBarangJadiM3ReportService $reportService,
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

        $noJual = $request->noJual();

        try {
            $reportData = $reportService->buildReportData($noJual);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.penjualan.penjualan-barang-jadi-m3-pdf', [
            'reportData' => $reportData,
            'noJual' => $noJual,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $paperMetrics = $pdfGenerator->paperMetrics('a4', orientation: 'portrait');

        $generatedByName = $generatedBy->name ?? $generatedBy->Username ?? 'sistem';
        $generatedAtText = now()->locale('id')->translatedFormat('d-M-y H:i');

        try {
            $footerHtml = view('reports.partials.gotenberg-footer', [
                'generatedByName' => $generatedByName,
                'generatedAtText' => $generatedAtText,
            ])->render();

            $pdf = $gotenbergPdfClient->convertHtml($html, $paperMetrics, $footerHtml);

            $filename = sprintf('Laporan-Penjualan-Barang-Jadi-M3-%s.pdf', str_replace(['/', '\\'], '-', $noJual));

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]);
        } catch (GotenbergPdfException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()], 502);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()]);
        }
    }

    public function health(
        GeneratePenjualanBarangJadiM3ReportRequest $request,
        PenjualanBarangJadiM3ReportService $reportService,
    ): JsonResponse {
        $noJual = $request->noJual();

        try {
            $result = $reportService->healthCheck($noJual);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapPenjualanBarangJadi valid.'
                : 'Struktur output SP_LapPenjualanBarangJadi berubah.',
            'meta' => [
                'no_jual' => $noJual,
                'NoJual' => $noJual,
            ],
            'health' => $result,
        ]);
    }
}
