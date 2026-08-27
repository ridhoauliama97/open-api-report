<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\ShowDashboardFingerJointRequest;
use App\Services\DashboardFingerJointReportService;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DashboardFingerJointController extends Controller
{
    public function index(
        ShowDashboardFingerJointRequest $request,
        DashboardFingerJointReportService $reportService,
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

        return view('dashboard.finger-joint', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function preview(
        ShowDashboardFingerJointRequest $request,
        DashboardFingerJointReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->resolveDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Data dashboard finger joint berhasil diambil.',
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
        ShowDashboardFingerJointRequest $request,
        DashboardFingerJointReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->resolveDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapDashboardFJ valid.'
                : 'Struktur output SPWps_LapDashboardFJ berubah.',
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
        ShowDashboardFingerJointRequest $request,
        DashboardFingerJointReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
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

        $html = $pdfGenerator->renderHtml('dashboard.finger-joint-pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $paperMetrics = $pdfGenerator->paperMetrics('a4');

        $generatedByName = $generatedBy->name ?? $generatedBy->Username ?? 'sistem';
        $generatedAtText = now()->locale('id')->translatedFormat('d-M-y H:i');

        try {
            $footerHtml = view('reports.partials.gotenberg-footer', [
                'generatedByName' => $generatedByName,
                'generatedAtText' => $generatedAtText,
            ])->render();

            $pdf = $gotenbergPdfClient->convertHtml($html, $paperMetrics, $footerHtml);

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Dashboard Finger Joint"']);
        } catch (GotenbergPdfException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()], 502);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()]);
        }
    }

    private function resolveDates(ShowDashboardFingerJointRequest $request): array
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
