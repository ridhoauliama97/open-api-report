<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowDashboardS4SV2Request;
use App\Services\DashboardS4SV2ReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DashboardS4SV2Controller extends Controller
{
    public function index(
        ShowDashboardS4SV2Request $request,
        DashboardS4SV2ReportService $reportService,
    ): View {
        $defaultEndDate = now()->endOfMonth()->toDateString();
        $defaultStartDate = now()->startOfMonth()->toDateString();

        $startDate = (string) $request->input('start_date', $request->input('TglAwal', $defaultStartDate));
        $endDate = (string) $request->input('end_date', $request->input('TglAkhir', $defaultEndDate));

        $errorMessage = null;
        $reportData = $this->emptyReportData();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('dashboard.s4s-v2', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function preview(
        ShowDashboardS4SV2Request $request,
        DashboardS4SV2ReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->resolveDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Data dashboard s4s v2 berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($reportData['rows'] ?? []),
                'total_columns' => count($reportData['columns'] ?? []),
                'total_raw_rows' => count($reportData['raw_rows'] ?? []),
                'column_mapping' => $reportData['column_mapping'] ?? [],
            ],
            'data' => $reportData,
        ]);
    }

    public function health(
        ShowDashboardS4SV2Request $request,
        DashboardS4SV2ReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->resolveDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapDashboardS4S2 valid.'
                : 'Struktur output SPWps_LapDashboardS4S2 berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    public function download(
        ShowDashboardS4SV2Request $request,
        DashboardS4SV2ReportService $reportService,
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

        [$startDate, $endDate] = $this->resolveDates($request);

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

        $pdf = $pdfGenerator->render('dashboard.s4s-v2-pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'landscape',
        ]);

        $filename = sprintf(
            'Laporan-Dashboard-S4S-v2-%s-sd-%s-%s.pdf',
            $startDate,
            $endDate,
            now()->format('YmdHis')
        );
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function resolveDates(ShowDashboardS4SV2Request $request): array
    {
        $defaultEndDate = now()->endOfMonth()->toDateString();
        $defaultStartDate = now()->startOfMonth()->toDateString();

        $startDate = (string) $request->input('start_date', $request->input('TglAwal', $defaultStartDate));
        $endDate = (string) $request->input('end_date', $request->input('TglAkhir', $defaultEndDate));

        return [$startDate, $endDate];
    }

    private function emptyReportData(): array
    {
        return [
            'dates' => [],
            'columns' => [],
            'rows' => [],
            's_akhir_by_column' => [],
            'percent_by_column' => [],
            'ctr_by_column' => [],
            'totals' => ['s_akhir' => 0.0, 'ctr' => 0.0],
            'column_mapping' => [
                'date' => null,
                'jenis' => null,
                'barang_jadi' => null,
                'masuk' => null,
                'keluar' => null,
                's_akhir' => null,
                'ctr' => null,
            ],
            'raw_rows' => [],
        ];
    }
}
