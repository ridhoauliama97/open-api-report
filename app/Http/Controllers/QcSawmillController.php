<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGotenbergPdfResponses;
use App\Http\Requests\GenerateQcSawmillReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\QcSawmillReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class QcSawmillController extends Controller
{
    use BuildsGotenbergPdfResponses;

    public function index(): View
    {
        return view('reports.sawn-timber.qc-sawmill-form');
    }

    public function download(
        GenerateQcSawmillReportRequest $request,
        QcSawmillReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient, false);
    }

    public function previewPdf(
        GenerateQcSawmillReportRequest $request,
        QcSawmillReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient, true);
    }

    public function preview(
        GenerateQcSawmillReportRequest $request,
        QcSawmillReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateQcSawmillReportRequest $request,
        QcSawmillReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapQCSawmill valid.'
                : 'Struktur output SP_LapQCSawmill berubah.',
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
        GenerateQcSawmillReportRequest $request,
        QcSawmillReportService $reportService,
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

        $viewData = [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
        ];

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.qc-sawmill-pdf', $viewData);

        $metrics = $pdfGenerator->paperMetrics($viewData);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        $filename = sprintf('Laporan-QC-Sawmill-%s-sd-%s.pdf', $startDate, $endDate);

        return $this->buildGotenbergPdfResponse($request, $html, $filename, $metrics, $footerHtml, 'attachment');
    }

    /**
     * @return array{0:string,1:string}
     */
    private function extractDates(GenerateQcSawmillReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
