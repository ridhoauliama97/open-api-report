<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowTargetMasukBBRequest;
use App\Services\PdfGenerator;
use App\Services\TargetMasukBBBulananReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TargetMasukBBBulananController extends Controller
{
    public function index(
        ShowTargetMasukBBRequest $request,
        TargetMasukBBBulananReportService $reportService,
    ): View {
        [$startDate, $endDate] = $this->extractDates($request);

        $errorMessage = null;
        $reportData = [
            'rows' => [],
            'month_columns' => [],
            'table_rows' => [],
            'summary_rows' => [],
            'chart_labels' => [],
            'chart_series' => [],
            'period_text' => '',
        ];

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('reports.kayu-bulat.target-masuk-bb-bulanan-form', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function download(
        ShowTargetMasukBBRequest $request,
        TargetMasukBBBulananReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.kayu-bulat.target-masuk-bb-bulanan-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Target-Masuk-BB-Bulanan-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        ShowTargetMasukBBRequest $request,
        TargetMasukBBBulananReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Data target masuk bahan baku bulanan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($reportData['rows'] ?? []),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
                'month_columns' => array_map(
                    static fn (array $item): string => (string) ($item['label'] ?? ''),
                    $reportData['month_columns'] ?? []
                ),
            ],
            'data' => $reportData,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(ShowTargetMasukBBRequest $request): array
    {
        $defaultStartDate = now()->startOfYear()->format('Y-m-d');
        $defaultEndDate = now()->endOfYear()->format('Y-m-d');

        $startDate = (string) $request->input('start_date', $request->input('TglAwal', $defaultStartDate));
        $endDate = (string) $request->input('end_date', $request->input('TglAkhir', $defaultEndDate));

        if (strtotime($endDate) < strtotime($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }
}
