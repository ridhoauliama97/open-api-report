<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateDateRangeReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapStPenjualanReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapStPenjualanController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.rekap-st-penjualan-form');
    }

    public function previewPdf(
        GenerateDateRangeReportRequest $request,
        RekapStPenjualanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, true);
    }

    public function download(
        GenerateDateRangeReportRequest $request,
        RekapStPenjualanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, false);
    }

    private function renderPdf(
        GenerateDateRangeReportRequest $request,
        RekapStPenjualanReportService $reportService,
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

        $startDate = $request->startDate();
        $endDate = $request->endDate();

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

        $pdf = $pdfGenerator->render('reports.sawn-timber.rekap-st-penjualan-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'pdf_orientation' => 'portrait',
            // Match Mutasi KD styling: keep vertical borders, remove horizontal row separators.
            // With mPDF, this needs simpleTables=false + packTableData=false to render correctly.
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = 'Laporan-Rekap-ST-Penjualan.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename=\"%s\"', $inline ? 'inline' : 'attachment', $filename),
        ]);
    }

    public function preview(
        GenerateDateRangeReportRequest $request,
        RekapStPenjualanReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_groups' => count($groups),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $groups,
        ]);
    }

    public function health(
        GenerateDateRangeReportRequest $request,
        RekapStPenjualanReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapRekapSTPenjualan valid.'
                : 'Struktur output SP_LapRekapSTPenjualan berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
