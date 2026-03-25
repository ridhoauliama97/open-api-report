<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateKayuBulatHidupReportRequest;
use App\Services\KayuBulatHidupReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class KayuBulatHidupController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat.hidup-form');
    }

    public function download(
        GenerateKayuBulatHidupReportRequest $request,
        KayuBulatHidupReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateKayuBulatHidupReportRequest $request,
        KayuBulatHidupReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function previewPdfLink(
        GenerateKayuBulatHidupReportRequest $request,
        KayuBulatHidupReportService $reportService,
        PdfGenerator $pdfGenerator,
        string $downloadName,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    private function buildPdfResponse(
        GenerateKayuBulatHidupReportRequest $request,
        KayuBulatHidupReportService $reportService,
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
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.kayu-bulat.hidup-pdf', [
            'rows' => $reportData['rows'],
            'summary' => $reportData['summary'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_column_count' => 8,
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $startLabel = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $endLabel = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $filename = sprintf('Laporan Kayu Bulat Hidup - Periode %s s/d %s.pdf', $startLabel, $endLabel);

        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                '%s; filename="%s"; filename*=UTF-8\'\'%s',
                $dispositionType,
                addcslashes($filename, "\"\\"),
                rawurlencode($filename)
            ),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function preview(
        GenerateKayuBulatHidupReportRequest $request,
        KayuBulatHidupReportService $reportService,
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
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'summary' => $reportData['summary'],
            'data' => $reportData['rows'],
        ]);
    }

    public function health(
        GenerateKayuBulatHidupReportRequest $request,
        KayuBulatHidupReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapkayuBulatHidup valid.'
                : 'Struktur output SPWps_LapkayuBulatHidup berubah.',
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
    private function extractDates(GenerateKayuBulatHidupReportRequest $request): array
    {
        $startDate = $request->input('start_date', $request->input('TglAwal'));
        $endDate = $request->input('end_date', $request->input('TglAkhir'));

        return [(string) $startDate, (string) $endDate];
    }
}
