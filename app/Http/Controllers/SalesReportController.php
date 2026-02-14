<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateSalesReportRequest;
use App\Services\PdfGenerator;
use App\Services\SalesReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class SalesReportController extends Controller
{
    public function index(): View
    {
        return view('reports.sales-form');
    }

    public function download(
        GenerateSalesReportRequest $request,
        SalesReportService $salesReportService,
        PdfGenerator $pdfGenerator,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return back()
                ->withInput()
                ->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $this->fetchRows($salesReportService, $startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $amountKey = $this->resolveAmountKey($rows);
        $grandTotal = collect($rows)->sum(static fn(array $row): float => (float) ($amountKey ? ($row[$amountKey] ?? 0) : 0));

        $pdf = $pdfGenerator->render('reports.sales-pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'grandTotal' => $grandTotal,
            'amountKey' => $amountKey,
        ]);

        $filename = sprintf('laporan-penjualan-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateSalesReportRequest $request,
        SalesReportService $salesReportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $this->fetchRows($salesReportService, $startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $amountKey = $this->resolveAmountKey($rows);
        $grandTotal = collect($rows)->sum(static fn(array $row): float => (float) ($amountKey ? ($row[$amountKey] ?? 0) : 0));

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_rows' => count($rows),
                'amount_field' => $amountKey,
                'grand_total' => $grandTotal,
            ],
            'data' => $rows,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateSalesReportRequest $request): array
    {
        return [
            $request->string('start_date')->toString(),
            $request->string('end_date')->toString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(SalesReportService $salesReportService, string $startDate, string $endDate): array
    {
        return $salesReportService->fetch($startDate, $endDate);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolveAmountKey(array $rows): ?string
    {
        $sample = $rows[0] ?? [];

        foreach (['total', 'total_amount', 'grand_total', 'amount'] as $key) {
            if (array_key_exists($key, $sample)) {
                return $key;
            }
        }

        return null;
    }
}
