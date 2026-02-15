<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateMutasiCrossCutReportRequest;
use App\Services\MutasiCrossCutReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;

use RuntimeException;

class MutasiCrossCutController extends Controller
{

    public function index(): View
    {
        return view('reports.mutasi.cross-cut-form');
    }
    public function download(
        GenerateMutasiCrossCutReportRequest $request,
        MutasiCrossCutReportService $mutasiCrossCutReportService,
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
            $rows = $this->fetchRows($mutasiCrossCutReportService, $startDate, $endDate);
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

        $pdf = $pdfGenerator->render('reports.mutasi.cross-cut-pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'amountKey' => $amountKey,
        ]);

        $filename = sprintf('laporan-mutasi-cross-cut-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateMutasiCrossCutReportRequest $request,
        MutasiCrossCutReportService $mutasiCrossCutReportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $this->fetchRows($mutasiCrossCutReportService, $startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $amountKey = $this->resolveAmountKey($rows);

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_rows' => count($rows),
                'amount_field' => $amountKey,
            ],
            'data' => $rows,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateMutasiCrossCutReportRequest $request): array
    {
        return [
            $request->string('start_date')->toString(),
            $request->string('end_date')->toString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(MutasiCrossCutReportService $mutasiCrossCutReportService, string $startDate, string $endDate): array
    {
        return $mutasiCrossCutReportService->fetch($startDate, $endDate);
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
