<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateDateRangeReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\PembelianStTimelineTonReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PembelianStTimelineTonController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.pembelian-st-timeline-ton-form');
    }

    public function previewPdf(
        GenerateDateRangeReportRequest $request,
        PembelianStTimelineTonReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, true);
    }

    public function download(
        GenerateDateRangeReportRequest $request,
        PembelianStTimelineTonReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, false);
    }

    private function renderPdf(
        GenerateDateRangeReportRequest $request,
        PembelianStTimelineTonReportService $reportService,
        PdfGenerator $pdfGenerator,
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

        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $monthColumns = is_array($reportData['month_columns'] ?? null) ? $reportData['month_columns'] : [];
        $orientation = count($monthColumns) > 6 ? 'landscape' : 'portrait';

        $pdfColumnCount = max(3, 3 + count($monthColumns));

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.pembelian-st-timeline-ton-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'startDate' => $startDate,
            'endDate' => $endDate,
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
        GenerateDateRangeReportRequest $request,
        PembelianStTimelineTonReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_suppliers' => (int) (($reportData['summary']['total_suppliers'] ?? 0)),
                'total_months' => (int) (($reportData['summary']['total_months'] ?? 0)),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateDateRangeReportRequest $request,
        PembelianStTimelineTonReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapPembelianSTTimeline valid.'
                : 'Struktur output SP_LapPembelianSTTimeline berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'health' => $result,
        ]);
    }
}
