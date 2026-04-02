<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateDateRangeReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapStockOnHandReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RekapStockOnHandController extends Controller
{
    public function index(Request $request): View
    {
        $service = app(RekapStockOnHandReportService::class);

        return view('reports.management.rekap-stock-on-hand-form', [
            'startDate' => (string) $request->input('TglAwal', now()->startOfMonth()->toDateString()),
            'endDate' => (string) $request->input('TglAkhir', now()->toDateString()),
            'availableSections' => $service->availableSections(),
            'selectedSections' => (array) $request->input('sections', array_column($service->availableSections(), 'key')),
        ]);
    }

    public function download(
        GenerateDateRangeReportRequest $request,
        RekapStockOnHandReportService $reportService,
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

        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $selectedSections = (array) $request->input('sections', []);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate, $selectedSections);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.management.rekap-stock-on-hand-pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
            'pdf_default_font' => 'dejavusans',
        ]);

        $filename = sprintf('Laporan-Rekap-Stock-On-Hand-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateDateRangeReportRequest $request,
        RekapStockOnHandReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $selectedSections = (array) $request->input('sections', []);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate, $selectedSections);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'section_count' => (int) ($reportData['summary']['section_count'] ?? 0),
                'row_count' => (int) ($reportData['summary']['row_count'] ?? 0),
                'selected_sections' => $reportData['selected_sections'] ?? [],
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateDateRangeReportRequest $request,
        RekapStockOnHandReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $selectedSections = (array) $request->input('sections', []);

        try {
            $result = $reportService->healthCheck($startDate, $endDate, $selectedSections);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output Laporan Rekap Stock On Hand valid.'
                : 'Struktur output Laporan Rekap Stock On Hand berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'health' => $result,
        ]);
    }
}
