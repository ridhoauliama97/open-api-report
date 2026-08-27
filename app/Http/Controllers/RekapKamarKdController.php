<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateRekapKamarKdReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\RekapKamarKdReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapKamarKdController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.rekap-kamar-kd-form');
    }

    public function previewPdf(
        GenerateRekapKamarKdReportRequest $request,
        RekapKamarKdReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator,
            $gotenbergPdfClient, true);
    }

    public function download(
        GenerateRekapKamarKdReportRequest $request,
        RekapKamarKdReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator,
            $gotenbergPdfClient, false);
    }

    private function renderPdf(
        GenerateRekapKamarKdReportRequest $request,
        RekapKamarKdReportService $reportService,
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

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.rekap-kamar-kd-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'startDate' => $startDate,
            'endDate' => $endDate,
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

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Laporan Rekap Kamar Kd"']);
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
        GenerateRekapKamarKdReportRequest $request,
        RekapKamarKdReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rooms = is_array($reportData['rooms'] ?? null) ? $reportData['rooms'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_rooms' => count($rooms),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $rooms,
        ]);
    }

    public function health(
        GenerateRekapKamarKdReportRequest $request,
        RekapKamarKdReportService $reportService,
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
                ? 'Struktur output SP_LapRekapKamarKD valid.'
                : 'Struktur output SP_LapRekapKamarKD berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
