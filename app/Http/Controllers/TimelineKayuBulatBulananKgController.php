<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateTimelineKayuBulatBulananKgReportRequest;
use App\Services\PdfGenerator;
use App\Services\TimelineKayuBulatBulananKgReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TimelineKayuBulatBulananKgController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat.timeline-kayu-bulat-bulanan-kg-form');
    }

    public function download(
        GenerateTimelineKayuBulatBulananKgReportRequest $request,
        TimelineKayuBulatBulananKgReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateTimelineKayuBulatBulananKgReportRequest $request,
        TimelineKayuBulatBulananKgReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    private function buildPdfResponse(
        GenerateTimelineKayuBulatBulananKgReportRequest $request,
        TimelineKayuBulatBulananKgReportService $reportService,
        PdfGenerator $pdfGenerator,
        bool $inline,
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
            $rows = $reportService->fetch($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.kayu-bulat.timeline-kayu-bulat-bulanan-kg-pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
            // Table pivot uses: No + Supplier + 12 months + Total = 15 columns.
            // Force column count so PdfGenerator auto-selects landscape reliably.
            'pdf_column_count' => 15,
        ]);

        $filename = sprintf('Laporan-Time-Line-Kayu-Bulat-Bulanan-KG-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateTimelineKayuBulatBulananKgReportRequest $request,
        TimelineKayuBulatBulananKgReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $reportService->fetch($startDate, $endDate);
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
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateTimelineKayuBulatBulananKgReportRequest $request,
        TimelineKayuBulatBulananKgReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapTimelineKBBulananKg valid.'
                : 'Struktur output SP_LapTimelineKBBulananKg berubah.',
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
    private function extractDates(GenerateTimelineKayuBulatBulananKgReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
