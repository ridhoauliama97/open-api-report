<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateStHidupKeringReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\StHidupKeringReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StHidupKeringController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.st-hidup-kering-form');
    }

    public function previewPdf(
        GenerateStHidupKeringReportRequest $request,
        StHidupKeringReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator,
            $gotenbergPdfClient, true);
    }

    public function download(
        GenerateStHidupKeringReportRequest $request,
        StHidupKeringReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator,
            $gotenbergPdfClient, false);
    }

    private function renderPdf(
        GenerateStHidupKeringReportRequest $request,
        StHidupKeringReportService $reportService,
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

        $hari = $request->hari();
        $modes = $request->selectedModes();
        $include = $request->include();
        $exclude = $request->exclude();

        try {
            $reportData = $reportService->buildReportData($hari, $modes);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.st-hidup-kering-pdf', [
            'reportData' => $reportData,
            'hari' => $hari,
            'include' => $include,
            'exclude' => $exclude,
            'modes' => $modes,
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

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Laporan St Hidup Kering"']);
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
        GenerateStHidupKeringReportRequest $request,
        StHidupKeringReportService $reportService,
    ): JsonResponse {
        $hari = $request->hari();
        $modes = $request->selectedModes();

        try {
            $reportData = $reportService->buildReportData($hari, $modes);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'hari' => $hari,
                'include' => $request->include(),
                'exclude' => $request->exclude(),
                'modes' => $modes,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateStHidupKeringReportRequest $request,
        StHidupKeringReportService $reportService,
    ): JsonResponse {
        $hari = $request->hari();
        $modes = $request->selectedModes();

        try {
            $result = $reportService->healthCheck($hari, $modes);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapSTHidupKering valid.'
                : 'Struktur output SP_LapSTHidupKering berubah.',
            'meta' => [
                'hari' => $hari,
                'include' => $request->include(),
                'exclude' => $request->exclude(),
                'modes' => $modes,
            ],
            'health' => $result,
        ]);
    }
}
