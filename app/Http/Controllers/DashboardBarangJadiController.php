<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowDashboardBarangJadiRequest;
use App\Services\DashboardBarangJadiReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DashboardBarangJadiController extends Controller
{
    public function index(
        ShowDashboardBarangJadiRequest $request,
        DashboardBarangJadiReportService $reportService,
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

        return view('dashboard.barang-jadi', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function preview(
        ShowDashboardBarangJadiRequest $request,
        DashboardBarangJadiReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->resolveDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Data dashboard barang jadi berhasil diambil.',
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
        ShowDashboardBarangJadiRequest $request,
        DashboardBarangJadiReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->resolveDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapDashboardBJ valid.'
                : 'Struktur output SPWps_LapDashboardBJ berubah.',
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
        ShowDashboardBarangJadiRequest $request,
        DashboardBarangJadiReportService $reportService,
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

        $pdf = $pdfGenerator->render('dashboard.barang-jadi-pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Dashboard-Barang-Jadi-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveDates(ShowDashboardBarangJadiRequest $request): array
    {
        $defaultEndDate = now()->endOfMonth()->toDateString();
        $defaultStartDate = now()->startOfMonth()->toDateString();

        $startDate = (string) $request->input('start_date', $request->input('TglAwal', $defaultStartDate));
        $endDate = (string) $request->input('end_date', $request->input('TglAkhir', $defaultEndDate));

        return [$startDate, $endDate];
    }

    /**
     * @return array<string, mixed>
     */
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
