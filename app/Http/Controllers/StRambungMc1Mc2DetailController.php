<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateNoParameterReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\StRambungMc1Mc2DetailReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StRambungMc1Mc2DetailController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.st-rambung-mc1-mc2-detail-form');
    }

    public function previewPdf(
        GenerateNoParameterReportRequest $request,
        StRambungMc1Mc2DetailReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator,
            $gotenbergPdfClient, true);
    }

    public function download(
        GenerateNoParameterReportRequest $request,
        StRambungMc1Mc2DetailReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator,
            $gotenbergPdfClient, false);
    }

    private function renderPdf(
        GenerateNoParameterReportRequest $request,
        StRambungMc1Mc2DetailReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
        bool $attachment,
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

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.st-rambung-mc1-mc2-detail-pdf', [
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

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Laporan St Rambung Mc1 Mc2 Detail"']);
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
        GenerateNoParameterReportRequest $request,
        StRambungMc1Mc2DetailReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_groups' => count($groups),
                'total_rows' => (int) (($reportData['summary']['total_rows'] ?? 0)),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $groups,
        ]);
    }

    public function health(
        GenerateNoParameterReportRequest $request,
        StRambungMc1Mc2DetailReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapSTRambungMC1danMC2Detail valid.'
                : 'Struktur output SP_LapSTRambungMC1danMC2Detail berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
