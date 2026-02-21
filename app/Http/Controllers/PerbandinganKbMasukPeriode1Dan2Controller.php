<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePerbandinganKbMasukPeriode1Dan2ReportRequest;
use App\Services\PdfGenerator;
use App\Services\PerbandinganKbMasukPeriode1Dan2ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PerbandinganKbMasukPeriode1Dan2Controller extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2-form');
    }

    public function download(
        GeneratePerbandinganKbMasukPeriode1Dan2ReportRequest $request,
        PerbandinganKbMasukPeriode1Dan2ReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GeneratePerbandinganKbMasukPeriode1Dan2ReportRequest $request,
        PerbandinganKbMasukPeriode1Dan2ReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    private function buildPdfResponse(
        GeneratePerbandinganKbMasukPeriode1Dan2ReportRequest $request,
        PerbandinganKbMasukPeriode1Dan2ReportService $reportService,
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

        [$period1StartDate, $period1EndDate, $period2StartDate, $period2EndDate] = $this->extractPeriods($request);

        try {
            $reportData = $reportService->buildReportData(
                $period1StartDate,
                $period1EndDate,
                $period2StartDate,
                $period2EndDate,
            );
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2-pdf', [
            'rows' => $reportData['rows'],
            'summary' => $reportData['summary'],
            'period1StartDate' => $period1StartDate,
            'period1EndDate' => $period1EndDate,
            'period2StartDate' => $period2StartDate,
            'period2EndDate' => $period2EndDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf(
            'Laporan-Perbandingan-KB-Masuk-Periode1dan2-%s-sd-%s-vs-%s-sd-%s.pdf',
            $period1StartDate,
            $period1EndDate,
            $period2StartDate,
            $period2EndDate,
        );
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GeneratePerbandinganKbMasukPeriode1Dan2ReportRequest $request,
        PerbandinganKbMasukPeriode1Dan2ReportService $reportService,
    ): JsonResponse {
        [$period1StartDate, $period1EndDate, $period2StartDate, $period2EndDate] = $this->extractPeriods($request);

        try {
            $reportData = $reportService->buildReportData(
                $period1StartDate,
                $period1EndDate,
                $period2StartDate,
                $period2EndDate,
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'period_1_start_date' => $period1StartDate,
                'period_1_end_date' => $period1EndDate,
                'period_2_start_date' => $period2StartDate,
                'period_2_end_date' => $period2EndDate,
                'TglAwalPeriode1' => $period1StartDate,
                'TglAkhirPeriode1' => $period1EndDate,
                'TglAwalPeriode2' => $period2StartDate,
                'TglAkhirPeriode2' => $period2EndDate,
                'total_rows' => count($reportData['rows'] ?? []),
                'column_order' => array_keys(($reportData['rows'][0] ?? [])),
            ],
            'summary' => $reportData['summary'],
            'data' => $reportData['rows'],
        ]);
    }

    public function health(
        GeneratePerbandinganKbMasukPeriode1Dan2ReportRequest $request,
        PerbandinganKbMasukPeriode1Dan2ReportService $reportService,
    ): JsonResponse {
        [$period1StartDate, $period1EndDate, $period2StartDate, $period2EndDate] = $this->extractPeriods($request);

        try {
            $result = $reportService->healthCheck(
                $period1StartDate,
                $period1EndDate,
                $period2StartDate,
                $period2EndDate,
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapPerbandinganKbMasukPeriode1dan2 valid.'
                : 'Struktur output SP_LapPerbandinganKbMasukPeriode1dan2 berubah.',
            'meta' => [
                'period_1_start_date' => $period1StartDate,
                'period_1_end_date' => $period1EndDate,
                'period_2_start_date' => $period2StartDate,
                'period_2_end_date' => $period2EndDate,
                'TglAwalPeriode1' => $period1StartDate,
                'TglAkhirPeriode1' => $period1EndDate,
                'TglAwalPeriode2' => $period2StartDate,
                'TglAkhirPeriode2' => $period2EndDate,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function extractPeriods(GeneratePerbandinganKbMasukPeriode1Dan2ReportRequest $request): array
    {
        return [
            $request->period1StartDate(),
            $request->period1EndDate(),
            $request->period2StartDate(),
            $request->period2EndDate(),
        ];
    }
}
