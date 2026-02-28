<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowDashboardS4SRequest;
use App\Services\DashboardS4SReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DashboardS4SController extends Controller
{
    public function index(
        ShowDashboardS4SRequest $request,
        DashboardS4SReportService $reportService,
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

        return view('dashboard.s4s', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function preview(
        ShowDashboardS4SRequest $request,
        DashboardS4SReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->resolveDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Data dashboard s4s berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($reportData['rows'] ?? []),
                'total_groups' => count($reportData['groups'] ?? []),
                'total_raw_rows' => count($reportData['raw_rows'] ?? []),
                'column_mapping' => $reportData['column_mapping'] ?? [],
            ],
            'data' => $reportData,
        ]);
    }

    public function health(
        ShowDashboardS4SRequest $request,
        DashboardS4SReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->resolveDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapDashboardS4S valid.'
                : 'Struktur output SPWps_LapDashboardS4S berubah.',
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
        ShowDashboardS4SRequest $request,
        DashboardS4SReportService $reportService,
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

        $pdf = $pdfGenerator->render('dashboard.s4s-pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'landscape',
        ]);

        $filename = sprintf('Laporan-Dashboard-S4S-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    private function resolveDates(ShowDashboardS4SRequest $request): array
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
            'groups' => [],
            'rows' => [],
            'dates' => [],
            'column_mapping' => [
                'date' => null,
                'jenis' => null,
                'barang_jadi' => null,
                'awal' => null,
                'masuk' => null,
                'jual' => null,
                'keluar' => null,
            ],
            'raw_rows' => [],
        ];
    }
}
