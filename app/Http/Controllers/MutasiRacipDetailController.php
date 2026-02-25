<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateMutasiRacipDetailReportRequest;
use App\Services\MutasiRacipDetailReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MutasiRacipDetailController extends Controller
{
    public function index(Request $request): View
    {
        $startDate = (string) $request->input('start_date', $request->input('TglAwal', now()->startOfMonth()->format('Y-m-d')));
        $endDate = (string) $request->input('end_date', $request->input('TglAkhir', now()->format('Y-m-d')));

        return view('reports.mutasi-racip-detail-form', [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function download(
        GenerateMutasiRacipDetailReportRequest $request,
        MutasiRacipDetailReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.mutasi-racip-detail-pdf', [
            'reportData' => $reportData,
            'pdf_orientation' => 'landscape',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Mutasi-Racip-Detail-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateMutasiRacipDetailReportRequest $request,
        MutasiRacipDetailReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($reportData['rows'] ?? []),
                'column_order' => $reportData['columns'] ?? [],
            ],
            'totals' => $reportData['totals'] ?? [],
            'data' => $reportData['rows'] ?? [],
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateMutasiRacipDetailReportRequest $request): array
    {
        $startDate = $request->input('start_date', $request->input('TglAwal'));
        $endDate = $request->input('end_date', $request->input('TglAkhir'));

        return [(string) $startDate, (string) $endDate];
    }
}
