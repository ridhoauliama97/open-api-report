<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowDashboardSawnTimberRequest;
use App\Services\DashboardSawnTimberReportService;
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
}
