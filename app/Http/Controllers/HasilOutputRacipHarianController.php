<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGotenbergPdfResponses;
use App\Http\Requests\GenerateHasilOutputRacipHarianReportRequest;
use App\Services\HasilOutputRacipHarianReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class HasilOutputRacipHarianController extends Controller
{
    use BuildsGotenbergPdfResponses;

    private const DEFAULT_DATE_FORMAT = 'Y-m-d';

    public function index(): View
    {
        return view('reports.hasil-output-racip-harian-form');
    }

    public function download(
        GenerateHasilOutputRacipHarianReportRequest $request,
        HasilOutputRacipHarianReportService $reportService,
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

        $endDate = $request->endDate(now()->format(self::DEFAULT_DATE_FORMAT));

        try {
            $reportData = $reportService->buildReportData($endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.hasil-output-racip-harian-pdf', [
            'reportData' => $reportData,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'reportData' => $reportData,
        ]);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        $filename = sprintf('Laporan-Hasil-Output-Racip-Harian-%s.pdf', $endDate);

        return $this->buildGotenbergPdfResponse($request, $html, $filename, $metrics, $footerHtml, 'attachment');
    }

    public function preview(
        GenerateHasilOutputRacipHarianReportRequest $request,
        HasilOutputRacipHarianReportService $reportService,
    ): JsonResponse {
        $endDate = $request->endDate(now()->format(self::DEFAULT_DATE_FORMAT));

        try {
            $reportData = $reportService->buildReportData($endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'end_date' => $endDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($reportData['rows'] ?? []),
                'column_order' => $reportData['columns'] ?? [],
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData['rows'] ?? [],
        ]);
    }

    public function health(
        GenerateHasilOutputRacipHarianReportRequest $request,
        HasilOutputRacipHarianReportService $reportService,
    ): JsonResponse {
        $endDate = $request->endDate(now()->format(self::DEFAULT_DATE_FORMAT));

        try {
            $result = $reportService->healthCheck($endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapHasilOutputRacipHarian valid.'
                : 'Struktur output SP_LapHasilOutputRacipHarian berubah.',
            'meta' => [
                'end_date' => $endDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }
}
