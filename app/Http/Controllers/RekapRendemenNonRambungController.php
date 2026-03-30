<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapRendemenNonRambungReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapRendemenNonRambungReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapRendemenNonRambungController extends Controller
{
    public function index(): View
    {
        return view('reports.rendemen-kayu.rekap-rendemen-non-rambung-form');
    }

    public function download(
        GenerateRekapRendemenNonRambungReportRequest $request,
        RekapRendemenNonRambungReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateRekapRendemenNonRambungReportRequest $request,
        RekapRendemenNonRambungReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateRekapRendemenNonRambungReportRequest $request,
        RekapRendemenNonRambungReportService $reportService,
    ): JsonResponse {
        $year = $request->year();
        $month = $request->month();

        try {
            $reportData = $reportService->buildReportData($year, $month);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'year' => $year,
                'month' => $month,
                'Tahun' => $year,
                'Bulan' => $month,
                'total_rows' => count($rows),
                'column_order' => $reportData['column_order'] ?? [],
                'column_schema' => $reportData['column_schema'] ?? [],
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateRekapRendemenNonRambungReportRequest $request,
        RekapRendemenNonRambungReportService $reportService,
    ): JsonResponse {
        $year = $request->year();
        $month = $request->month();

        try {
            $result = $reportService->healthCheck($year, $month);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapRekapRendemenNonRambung valid.'
                : 'Struktur output SP_LapRekapRendemenNonRambung berubah.',
            'meta' => [
                'year' => $year,
                'month' => $month,
                'Tahun' => $year,
                'Bulan' => $month,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateRekapRendemenNonRambungReportRequest $request,
        RekapRendemenNonRambungReportService $reportService,
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

        $year = $request->year();
        $month = $request->month();

        try {
            $reportData = $reportService->buildReportData($year, $month);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.rendemen-kayu.rekap-rendemen-non-rambung-pdf', [
            'reportData' => $reportData,
            'year' => $year,
            'month' => $month,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'landscape',
            'pdf_pack_table_data' => false,
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Rekap-Rendemen-Non-Rambung-%s-%02d.pdf', $year, (int) $month);
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

}
