<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateStockHidupPerNoSpkReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\StockHidupPerNoSpkReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StockHidupPerNoSpkController extends Controller
{
    public function index(GenerateStockHidupPerNoSpkReportRequest $request): View
    {
        return view('reports.management.stock-hidup-per-nospk-form', [
            'tanggalAkhir' => $request->tanggalAkhir(),
        ]);
    }

    public function download(
        GenerateStockHidupPerNoSpkReportRequest $request,
        StockHidupPerNoSpkReportService $reportService,
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

        $tanggalAkhir = $request->tanggalAkhir();

        try {
            $reportData = $reportService->buildReportData($tanggalAkhir);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.management.stock-hidup-per-nospk-pdf', [
            'tanggalAkhir' => $tanggalAkhir,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $paperMetrics = $pdfGenerator->paperMetrics('a4');

        $generatedByName = $generatedBy->name ?? $generatedBy->Username ?? 'sistem';
        $generatedAtText = now()->locale('id')->translatedFormat('d-M-y H:i');

        try {
            $pdf = $gotenbergPdfClient->convertHtml($html, $paperMetrics, 'reports.partials.gotenberg-footer', [
                'generatedByName' => $generatedByName,
                'generatedAtText' => $generatedAtText,
            ]);

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Laporan Stock Hidup Per Nospk"']);
        } catch (GotenbergPdfException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()], 502);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()]);
        }
    }

    public function preview(
        GenerateStockHidupPerNoSpkReportRequest $request,
        StockHidupPerNoSpkReportService $reportService,
    ): JsonResponse {
        $tanggalAkhir = $request->tanggalAkhir();

        try {
            $reportData = $reportService->buildReportData($tanggalAkhir);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'TglAkhir' => $tanggalAkhir,
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_categories' => (int) ($reportData['summary']['total_categories'] ?? 0),
                'total_spk' => (int) ($reportData['summary']['total_spk'] ?? 0),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateStockHidupPerNoSpkReportRequest $request,
        StockHidupPerNoSpkReportService $reportService,
    ): JsonResponse {
        $tanggalAkhir = $request->tanggalAkhir();

        try {
            $result = $reportService->healthCheck($tanggalAkhir);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapSemuaStockHidupPerSPK valid.'
                : 'Struktur output SP_LapSemuaStockHidupPerSPK berubah.',
            'meta' => ['TglAkhir' => $tanggalAkhir],
            'health' => $result,
        ]);
    }
}
