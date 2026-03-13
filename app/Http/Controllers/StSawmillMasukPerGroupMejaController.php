<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStSawmillMasukPerGroupMejaReportRequest;
use App\Services\PdfGenerator;
use App\Services\StSawmillMasukPerGroupMejaReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StSawmillMasukPerGroupMejaController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.st-sawmill-masuk-per-group-meja-form');
    }

    public function download(
        GenerateStSawmillMasukPerGroupMejaReportRequest $request,
        StSawmillMasukPerGroupMejaReportService $reportService,
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

        $mejaCount = is_array($reportData['meja'] ?? null) ? count($reportData['meja']) : 0;

        $pdf = $pdfGenerator->render('reports.sawn-timber.st-sawmill-masuk-per-group-meja-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),

            // This report uses complex rowspans and a multi-row header.
            'pdf_simple_tables' => false,
            // Avoid mPDF table border packing issues on large pivot tables.
            'pdf_pack_table_data' => false,
            // Hint for orientation auto-selection in PdfGenerator.
            'pdf_column_count' => 4 + $mejaCount,
        ]);

        $filename = sprintf('Laporan-ST-Sawmill-Masuk-Per-Group-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateStSawmillMasukPerGroupMejaReportRequest $request,
        StSawmillMasukPerGroupMejaReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];
        $meja = is_array($reportData['meja'] ?? null) ? $reportData['meja'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_groups' => count($groups),
                'total_meja' => count($meja),
                'raw_row_count' => $reportData['summary']['total_rows'] ?? 0,
            ],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateStSawmillMasukPerGroupMejaReportRequest $request,
        StSawmillMasukPerGroupMejaReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapSTSawmillMasukPerGroup valid.'
                : 'Struktur output SP_LapSTSawmillMasukPerGroup berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateStSawmillMasukPerGroupMejaReportRequest $request): array
    {
        $startDate = $request->input('start_date', $request->input('TglAwal'));
        $endDate = $request->input('end_date', $request->input('TglAkhir'));

        return [(string) $startDate, (string) $endDate];
    }
}
