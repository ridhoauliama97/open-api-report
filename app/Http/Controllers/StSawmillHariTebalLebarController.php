<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStSawmillHariTebalLebarReportRequest;
use App\Services\PdfGenerator;
use App\Services\StSawmillHariTebalLebarReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StSawmillHariTebalLebarController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.st-sawmill-hari-tebal-lebar-form');
    }

    public function previewPdf(
        GenerateStSawmillHariTebalLebarReportRequest $request,
        StSawmillHariTebalLebarReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, true);
    }

    public function download(
        GenerateStSawmillHariTebalLebarReportRequest $request,
        StSawmillHariTebalLebarReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, false);
    }

    private function renderPdf(
        GenerateStSawmillHariTebalLebarReportRequest $request,
        StSawmillHariTebalLebarReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.sawn-timber.st-sawmill-hari-tebal-lebar-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'landscape',
            // Ensure mPDF respects the "vertical lines only" table styling.
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf('Laporan-ST-Sawmill-Hari-Tebal-Lebar-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $inline ? 'inline' : 'attachment', $filename),
        ]);
    }

    public function preview(
        GenerateStSawmillHariTebalLebarReportRequest $request,
        StSawmillHariTebalLebarReportService $reportService,
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
                'total_dates' => $reportData['summary']['total_dates'] ?? 0,
                'total_is_groups' => $reportData['summary']['total_is_groups'] ?? 0,
                'columns' => array_keys(($reportData['rows'][0] ?? [])),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateStSawmillHariTebalLebarReportRequest $request,
        StSawmillHariTebalLebarReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapSTSawmillPerHariPerTebalPerLebar valid.'
                : 'Struktur output SPWps_LapSTSawmillPerHariPerTebalPerLebar berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateStSawmillHariTebalLebarReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
