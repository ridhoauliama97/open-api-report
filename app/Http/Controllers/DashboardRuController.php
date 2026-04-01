<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateDashboardRuReportRequest;
use App\Services\DashboardRuReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DashboardRuController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.management.dashboard-ru-form', [
            'reportDate' => (string) $request->input('Periode', $request->input('TglAkhir', now()->endOfMonth()->toDateString())),
        ]);
    }

    public function download(
        GenerateDashboardRuReportRequest $request,
        DashboardRuReportService $reportService,
        PdfGenerator $pdfGenerator,
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

        $reportDate = $request->reportDate();

        try {
            $reportData = $reportService->buildReportData($reportDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.management.dashboard-ru-pdf', [
            'reportDate' => $reportDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $periodLabel = $reportData['period_label'] ?? $reportDate;
        $filename = sprintf('Laporan-Dashboard-RU-%s.pdf', str_replace(' ', '-', (string) $periodLabel));
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateDashboardRuReportRequest $request,
        DashboardRuReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();

        try {
            $reportData = $reportService->buildReportData($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'report_date' => $reportDate,
                'Periode' => $reportDate,
                'row_count' => (int) ($reportData['summary']['row_count'] ?? 0),
                'daily_row_count' => (int) ($reportData['summary']['daily_row_count'] ?? 0),
                'group_count' => (int) ($reportData['summary']['group_count'] ?? 0),
                'sub_column_count' => (int) ($reportData['summary']['sub_column_count'] ?? 0),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateDashboardRuReportRequest $request,
        DashboardRuReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();

        try {
            $result = $reportService->healthCheck($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapProduktivitasDashboard valid.'
                : 'Struktur output SP_LapProduktivitasDashboard berubah.',
            'meta' => [
                'report_date' => $reportDate,
                'Periode' => $reportDate,
            ],
            'health' => $result,
        ]);
    }
}
