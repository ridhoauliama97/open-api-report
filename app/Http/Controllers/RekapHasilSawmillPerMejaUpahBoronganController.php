<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapHasilSawmillPerMejaUpahBoronganReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapHasilSawmillPerMejaUpahBoronganReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapHasilSawmillPerMejaUpahBoronganController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan-form');
    }

    public function download(
        GenerateRekapHasilSawmillPerMejaUpahBoronganReportRequest $request,
        RekapHasilSawmillPerMejaUpahBoronganReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateRekapHasilSawmillPerMejaUpahBoronganReportRequest $request,
        RekapHasilSawmillPerMejaUpahBoronganReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateRekapHasilSawmillPerMejaUpahBoronganReportRequest $request,
        RekapHasilSawmillPerMejaUpahBoronganReportService $reportService,
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
        GenerateRekapHasilSawmillPerMejaUpahBoronganReportRequest $request,
        RekapHasilSawmillPerMejaUpahBoronganReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapRekapHasilSawmillPerMejaUpahBorongan valid.'
                : 'Struktur output SPWps_LapRekapHasilSawmillPerMejaUpahBorongan berubah.',
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
        GenerateRekapHasilSawmillPerMejaUpahBoronganReportRequest $request,
        RekapHasilSawmillPerMejaUpahBoronganReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan-pdf', [
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
            // Needed so mPDF repeats <tfoot> (table end line) when the table breaks across pages.
            'pdf_simple_tables' => true,
        ]);

        $filename = sprintf('Laporan Rekap Hasil Sawmill Per Meja (Upah Borongan) - %s s-d %s.pdf', $startDate, $endDate);
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateRekapHasilSawmillPerMejaUpahBoronganReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
