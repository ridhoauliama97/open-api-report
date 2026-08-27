<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateSerahTerimaStKamarKdReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\SerahTerimaStKamarKdReportService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class SerahTerimaStKamarKdController extends Controller
{
    public function preview(
        GenerateSerahTerimaStKamarKdReportRequest $request,
        SerahTerimaStKamarKdReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData($request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'no_proc_kd' => (string) ($reportData['filters']['no_proc_kd'] ?? ''),
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_no_st' => (int) ($reportData['summary']['total_no_st'] ?? 0),
                'total_pcs' => (int) ($reportData['summary']['total_pcs'] ?? 0),
                'total_ton' => (float) ($reportData['summary']['total_ton'] ?? 0.0),
                'total_kubik' => (float) ($reportData['summary']['total_kubik'] ?? 0.0),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'data' => $reportData['rows'] ?? [],
            'report_data' => $reportData,
        ]);
    }

    public function download(
        GenerateSerahTerimaStKamarKdReportRequest $request,
        SerahTerimaStKamarKdReportService $reportService,
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
            $reportData = $reportService->buildReportData($request->validated());
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.serah-terima-st-kamar-kd-pdf', [
            'reportData' => $reportData,
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

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Laporan Serah Terima St Kamar Kd"']);
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
        GenerateSerahTerimaStKamarKdReportRequest $request,
        SerahTerimaStKamarKdReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck($request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapSerahTerimaSTKDKeluar valid.'
                : 'Struktur output SP_LapSerahTerimaSTKDKeluar berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
