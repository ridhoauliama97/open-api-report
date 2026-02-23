<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowRekapPembelianKayuBulatRequest;
use App\Services\PdfGenerator;
use App\Services\RekapPembelianKayuBulatReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapPembelianKayuBulatController extends Controller
{
    public function index(
        ShowRekapPembelianKayuBulatRequest $request,
        RekapPembelianKayuBulatReportService $reportService,
    ): View {
        $currentYear = (int) now()->format('Y');
        $defaultStartYear = $currentYear - 9;
        $startYear = (int) $request->input('start_year', $defaultStartYear);
        $endYear = (int) $request->input('end_year', $currentYear);
        if ($endYear < $startYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }
        $startDate = sprintf('%d-01-01', $startYear);
        $endDate = sprintf('%d-12-31', $endYear);

        $errorMessage = null;
        $reportData = [
            'rows' => [],
            'columns' => ['date' => null, 'type' => null, 'amount' => null],
            'dates' => [],
            'types' => [],
            'series_by_type' => [],
            'totals_by_type' => [],
            'daily_totals' => [],
            'table_rows' => [],
            'grand_total' => 0.0,
        ];

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('reports.kayu-bulat.rekap-pembelian-form', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'startYear' => $startYear,
            'endYear' => $endYear,
            'reportData' => $reportData,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function preview(
        ShowRekapPembelianKayuBulatRequest $request,
        RekapPembelianKayuBulatReportService $reportService,
    ): JsonResponse {
        $currentYear = (int) now()->format('Y');
        $defaultStartYear = $currentYear - 9;
        $startYear = (int) $request->input('start_year', $defaultStartYear);
        $endYear = (int) $request->input('end_year', $currentYear);
        if ($endYear < $startYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }
        $startDate = sprintf('%d-01-01', $startYear);
        $endDate = sprintf('%d-12-31', $endYear);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Data rekap pembelian kayu bulat berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_year' => $startYear,
                'end_year' => $endYear,
                'total_rows' => count($reportData['rows'] ?? []),
                'total_types' => count($reportData['types'] ?? []),
                'total_days' => count($reportData['dates'] ?? []),
                'columns' => $reportData['columns'] ?? [],
                'grand_total' => $reportData['grand_total'] ?? 0,
            ],
            'data' => $reportData,
        ]);
    }

    public function download(
        ShowRekapPembelianKayuBulatRequest $request,
        RekapPembelianKayuBulatReportService $reportService,
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

        $currentYear = (int) now()->format('Y');
        $defaultStartYear = $currentYear - 9;
        $startYear = (int) $request->input('start_year', $defaultStartYear);
        $endYear = (int) $request->input('end_year', $currentYear);
        if ($endYear < $startYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }
        $startDate = sprintf('%d-01-01', $startYear);
        $endDate = sprintf('%d-12-31', $endYear);

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

        $pdf = $pdfGenerator->render('reports.kayu-bulat.rekap-pembelian-pdf', [
            'reportData' => $reportData,
            'startYear' => $startYear,
            'endYear' => $endYear,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Rekap-Pembelian-Kayu-Bulat-%d-sd-%d.pdf', $startYear, $endYear);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }
}
