<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateRekapPcsTellyHasilSawmillReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\RekapPcsTellyHasilSawmillReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapPcsTellyHasilSawmillController extends Controller
{
    public function index(): View
    {
        return view('sawn-timber.rekap-pcs-telly-hasil-sawmill-form');
    }

    public function download(
        GenerateRekapPcsTellyHasilSawmillReportRequest $request,
        RekapPcsTellyHasilSawmillReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator,
            $gotenbergPdfClient, false);
    }

    public function previewPdf(
        GenerateRekapPcsTellyHasilSawmillReportRequest $request,
        RekapPcsTellyHasilSawmillReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator,
            $gotenbergPdfClient, true);
    }

    public function preview(
        GenerateRekapPcsTellyHasilSawmillReportRequest $request,
        RekapPcsTellyHasilSawmillReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

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
                'total_rows' => count($reportData['rows'] ?? []),
                'total_documents' => $reportData['summary']['total_documents'] ?? 0,
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'data' => $reportData['rows'] ?? [],
            'report_data' => $reportData,
        ]);
    }

    public function health(
        GenerateRekapPcsTellyHasilSawmillReportRequest $request,
        RekapPcsTellyHasilSawmillReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_RekapPcsTellyHasilSawmill valid.'
                : 'Struktur output SPWps_RekapPcsTellyHasilSawmill berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateRekapPcsTellyHasilSawmillReportRequest $request,
        RekapPcsTellyHasilSawmillReportService $reportService,
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

        [$startDate, $endDate] = $this->extractDates($request);

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

        $html = $pdfGenerator->renderHtml('sawn-timber.rekap-pcs-telly-hasil-sawmill-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
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

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Laporan Rekap Pcs Telly Hasil Sawmill"']);
        } catch (GotenbergPdfException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()], 502);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()]);
        }
    }

    /**
     * @return array{0:string,1:string}
     */
    private function extractDates(GenerateRekapPcsTellyHasilSawmillReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
