<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowDashboardSawnTimberRequest;
use App\Services\DashboardSawnTimberReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DashboardSawnTimberController extends Controller
{
    public function index(
        ShowDashboardSawnTimberRequest $request,
        DashboardSawnTimberReportService $reportService,
    ): View {
        $defaultEndDate = now()->toDateString();
        $defaultStartDate = now()->subDays(6)->toDateString();

        $startDate = (string) $request->input('start_date', $defaultStartDate);
        $endDate = (string) $request->input('end_date', $defaultEndDate);

        $errorMessage = null;
        $chartData = [
            'dates' => [],
            'types' => [],
            'series_by_type' => [],
            'totals_by_type' => [],
            'column_mapping' => ['date' => null, 'type' => null, 'in' => null, 'out' => null],
            'raw_rows' => [],
        ];

        try {
            $chartData = $reportService->buildChartData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('dashboard.sawn-timber', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'chartData' => $chartData,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function preview(
        ShowDashboardSawnTimberRequest $request,
        DashboardSawnTimberReportService $reportService,
    ): JsonResponse {
        $defaultEndDate = now()->toDateString();
        $defaultStartDate = now()->subDays(6)->toDateString();

        $startDate = (string) $request->input('start_date', $defaultStartDate);
        $endDate = (string) $request->input('end_date', $defaultEndDate);

        try {
            $chartData = $reportService->buildChartData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Data dashboard sawn timber berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_rows' => count($chartData['raw_rows'] ?? []),
                'total_types' => count($chartData['types'] ?? []),
                'total_days' => count($chartData['dates'] ?? []),
                'column_mapping' => $chartData['column_mapping'] ?? [],
            ],
            'data' => $chartData,
        ]);
    }

    public function download(
        ShowDashboardSawnTimberRequest $request,
        DashboardSawnTimberReportService $reportService,
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

        $defaultEndDate = now()->toDateString();
        $defaultStartDate = now()->subDays(6)->toDateString();
        $startDate = (string) $request->input('start_date', $defaultStartDate);
        $endDate = (string) $request->input('end_date', $defaultEndDate);

        try {
            $chartData = $reportService->buildChartData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdfChartData = $this->preparePdfChartData($chartData);

        $pdf = $pdfGenerator->render('reports.sawn-timber.dashboard-sawn-timber-pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'chartData' => $pdfChartData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Dashboard-Sawn-Timber-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * Reduce chart payload for PDF rendering so large date ranges do not exhaust memory.
     *
     * @param array<string, mixed> $chartData
     * @return array<string, mixed>
     */
    private function preparePdfChartData(array $chartData): array
    {
        $prepared = $chartData;
        $prepared['raw_row_count'] = count($chartData['raw_rows'] ?? []);
        unset($prepared['raw_rows']);
        unset($prepared['column_mapping']);

        $dates = array_values(is_array($chartData['dates'] ?? null) ? $chartData['dates'] : []);
        $dailyIn = array_values(is_array($chartData['daily_in_totals'] ?? null) ? $chartData['daily_in_totals'] : []);
        $dailyOut = array_values(is_array($chartData['daily_out_totals'] ?? null) ? $chartData['daily_out_totals'] : []);
        $types = array_values(is_array($chartData['types'] ?? null) ? $chartData['types'] : []);
        $totalsByType = is_array($chartData['totals_by_type'] ?? null) ? $chartData['totals_by_type'] : [];
        $stockByType = is_array($chartData['stock_by_type'] ?? null) ? $chartData['stock_by_type'] : [];
        $seriesByType = is_array($chartData['series_by_type'] ?? null) ? $chartData['series_by_type'] : [];

        $maxTypes = 25;
        if (count($types) > $maxTypes) {
            usort($types, static function (string $a, string $b) use ($stockByType, $totalsByType): int {
                $aScore = (float) ($stockByType[$a]['s_akhir'] ?? 0)
                    + (float) ($totalsByType[$a]['in'] ?? 0)
                    + (float) ($totalsByType[$a]['out'] ?? 0);
                $bScore = (float) ($stockByType[$b]['s_akhir'] ?? 0)
                    + (float) ($totalsByType[$b]['in'] ?? 0)
                    + (float) ($totalsByType[$b]['out'] ?? 0);

                return $bScore <=> $aScore;
            });

            $selectedTypes = array_slice($types, 0, $maxTypes);
            $selectedMap = array_fill_keys($selectedTypes, true);

            $prepared['types'] = $selectedTypes;
            $prepared['totals_by_type'] = array_filter(
                $totalsByType,
                static fn(string $type): bool => isset($selectedMap[$type]),
                ARRAY_FILTER_USE_KEY
            );
            $prepared['series_by_type'] = array_filter(
                $seriesByType,
                static fn(string $type): bool => isset($selectedMap[$type]),
                ARRAY_FILTER_USE_KEY
            );
            $prepared['stock_by_type'] = array_filter(
                $stockByType,
                static fn(string $type): bool => isset($selectedMap[$type]),
                ARRAY_FILTER_USE_KEY
            );
            $prepared['pdf_truncated_types'] = true;
            $prepared['pdf_original_type_count'] = count($types);
        } else {
            $prepared['types'] = $types;
            $prepared['totals_by_type'] = $totalsByType;
            $prepared['series_by_type'] = $seriesByType;
            $prepared['stock_by_type'] = $stockByType;
            $prepared['pdf_truncated_types'] = false;
            $prepared['pdf_original_type_count'] = count($types);
        }

        $maxPoints = 31;
        if (count($dates) <= $maxPoints) {
            return $prepared;
        }

        $bucketCount = min($maxPoints, count($dates));
        $chunkSize = (int) ceil(count($dates) / $bucketCount);

        $sampledDates = [];
        $sampledIn = [];
        $sampledOut = [];

        for ($offset = 0; $offset < count($dates); $offset += $chunkSize) {
            $dateChunk = array_slice($dates, $offset, $chunkSize);
            $inChunk = array_slice($dailyIn, $offset, $chunkSize);
            $outChunk = array_slice($dailyOut, $offset, $chunkSize);

            if ($dateChunk === []) {
                continue;
            }

            $sampledDates[] = (string) end($dateChunk);
            $sampledIn[] = array_sum(array_map('floatval', $inChunk));
            $sampledOut[] = array_sum(array_map('floatval', $outChunk));
        }

        $prepared['dates'] = $sampledDates;
        $prepared['daily_in_totals'] = $sampledIn;
        $prepared['daily_out_totals'] = $sampledOut;
        $prepared['series_by_type'] = $this->sampleSeriesByType(
            is_array($prepared['series_by_type'] ?? null) ? $prepared['series_by_type'] : [],
            $chunkSize,
        );

        return $prepared;
    }

    /**
     * @param array<string, array{in?: array<int, float|int|string|null>, out?: array<int, float|int|string|null>}> $seriesByType
     * @return array<string, array{in: array<int, float>, out: array<int, float>}>
     */
    private function sampleSeriesByType(array $seriesByType, int $chunkSize): array
    {
        $sampled = [];

        foreach ($seriesByType as $type => $series) {
            $sampled[$type] = [
                'in' => $this->sumSeriesChunks($series['in'] ?? [], $chunkSize),
                'out' => $this->sumSeriesChunks($series['out'] ?? [], $chunkSize),
            ];
        }

        return $sampled;
    }

    /**
     * @param array<int, float|int|string|null> $values
     * @return array<int, float>
     */
    private function sumSeriesChunks(array $values, int $chunkSize): array
    {
        if ($chunkSize <= 1) {
            return array_map(static fn($value): float => (float) ($value ?? 0), $values);
        }

        $chunks = [];
        for ($offset = 0; $offset < count($values); $offset += $chunkSize) {
            $chunks[] = array_sum(array_map('floatval', array_slice($values, $offset, $chunkSize)));
        }

        return $chunks;
    }
}
