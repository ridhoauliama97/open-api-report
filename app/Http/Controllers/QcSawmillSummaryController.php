<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateQcSawmillSummaryReportRequest;
use App\Services\PdfGenerator;
use App\Services\QcSawmillSummaryReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class QcSawmillSummaryController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.qc-sawmill-summary-form');
    }

    public function download(
        GenerateQcSawmillSummaryReportRequest $request,
        QcSawmillSummaryReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateQcSawmillSummaryReportRequest $request,
        QcSawmillSummaryReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateQcSawmillSummaryReportRequest $request,
        QcSawmillSummaryReportService $reportService,
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
            'pivot' => [
                'date_keys' => $reportData['date_keys'] ?? [],
                'meja_rows' => $reportData['meja_rows'] ?? [],
            ],
        ]);
    }

    public function health(
        GenerateQcSawmillSummaryReportRequest $request,
        QcSawmillSummaryReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapQCSawmillSummary valid.'
                : 'Struktur output SP_LapQCSawmillSummary berubah.',
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
        GenerateQcSawmillSummaryReportRequest $request,
        QcSawmillSummaryReportService $reportService,
        PdfGenerator $pdfGenerator,
        bool $inline,
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

        $dateKeys = is_array($reportData['date_keys'] ?? null) ? $reportData['date_keys'] : [];

        $pdf = $pdfGenerator->render('reports.sawn-timber.qc-sawmill-summary-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'landscape',
            'pdf_format' => count($dateKeys) > 20 ? 'A3' : 'A4',
            'pdf_simple_tables' => false,
            'pdf_shrink_tables_to_fit' => 1,
        ]);

        $filename = sprintf('Laporan-QC-Sawmill-Summary-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $inline ? 'inline' : 'attachment', $filename),
        ]);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function extractDates(GenerateQcSawmillSummaryReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
