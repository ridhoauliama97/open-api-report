<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapHasilSawmillPerMejaUpahBoronganV2ReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapHasilSawmillPerMejaUpahBoronganV2ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapHasilSawmillPerMejaUpahBoronganV2Controller extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan-v2-form');
    }

    public function download(
        GenerateRekapHasilSawmillPerMejaUpahBoronganV2ReportRequest $request,
        RekapHasilSawmillPerMejaUpahBoronganV2ReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateRekapHasilSawmillPerMejaUpahBoronganV2ReportRequest $request,
        RekapHasilSawmillPerMejaUpahBoronganV2ReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateRekapHasilSawmillPerMejaUpahBoronganV2ReportRequest $request,
        RekapHasilSawmillPerMejaUpahBoronganV2ReportService $reportService,
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
                'total_rows' => count($reportData['rows']),
                'total_sub_rows' => count($reportData['sub_rows']),
                'total_meja' => $reportData['summary']['main']['total_meja'] ?? 0,
                'column_order' => array_keys($reportData['rows'][0] ?? []),
                'sub_column_order' => array_keys($reportData['sub_rows'][0] ?? []),
            ],
            'summary' => $reportData['summary'],
            'data' => $reportData['rows'],
            'sub_data' => $reportData['sub_rows'],
            'grouped_data' => $reportData['grouped_rows'],
            'grouped_sub_data' => $reportData['grouped_sub_rows'],
        ]);
    }

    public function health(
        GenerateRekapHasilSawmillPerMejaUpahBoronganV2ReportRequest $request,
        RekapHasilSawmillPerMejaUpahBoronganV2ReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapRekapHasilSawmillPerMejaUpahBoronganV2 valid.'
                : 'Struktur output SPWps_LapRekapHasilSawmillPerMejaUpahBoronganV2 berubah.',
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
        GenerateRekapHasilSawmillPerMejaUpahBoronganV2ReportRequest $request,
        RekapHasilSawmillPerMejaUpahBoronganV2ReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan-v2-pdf', [
            'rows' => $reportData['rows'],
            'subRows' => $reportData['sub_rows'],
            'groupedRows' => $reportData['grouped_rows'],
            'groupedSubRows' => $reportData['grouped_sub_rows'],
            'summary' => $reportData['summary'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan Rekap Hasil Sawmill Per Meja (Semua Meja) - %s s-d %s.pdf', $startDate, $endDate);
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateRekapHasilSawmillPerMejaUpahBoronganV2ReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
