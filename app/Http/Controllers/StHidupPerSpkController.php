<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateStHidupPerSpkReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\StHidupPerSpkReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StHidupPerSpkController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.st-hidup-per-spk-form');
    }

    public function download(
        GenerateStHidupPerSpkReportRequest $request,
        StHidupPerSpkReportService $reportService,
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

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.st-hidup-per-spk-pdf', [
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

            return $pdf;
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
        GenerateStHidupPerSpkReportRequest $request,
        StHidupPerSpkReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];
        $firstJenis = is_array($groups[0] ?? null) ? $groups[0] : [];
        $firstProduk = is_array(($firstJenis['products'][0] ?? null)) ? $firstJenis['products'][0] : [];
        $firstSpk = is_array(($firstProduk['spks'][0] ?? null)) ? $firstProduk['spks'][0] : [];
        $firstRow = is_array(($firstSpk['rows'][0] ?? null)) ? $firstSpk['rows'][0] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_jenis' => (int) ($reportData['summary']['total_jenis'] ?? 0),
                'total_produk' => (int) ($reportData['summary']['total_produk'] ?? 0),
                'total_spk' => (int) ($reportData['summary']['total_spk'] ?? 0),
                'column_order' => array_keys($firstRow),
            ],
            'summary' => $reportData['summary'],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateStHidupPerSpkReportRequest $request,
        StHidupPerSpkReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapSTHidupPerProdukV2 valid.'
                : 'Struktur output SPWps_LapSTHidupPerProdukV2 berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
