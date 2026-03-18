<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateDateRangeReportRequest;
use App\Services\KetahananBarangDagangLaminatingReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class KetahananBarangDagangLaminatingController extends Controller
{
    public function index(): View
    {
        return view('reports.laminating.ketahanan-barang-laminating-form');
    }

    public function previewPdf(
        GenerateDateRangeReportRequest $request,
        KetahananBarangDagangLaminatingReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, true);
    }

    public function download(
        GenerateDateRangeReportRequest $request,
        KetahananBarangDagangLaminatingReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, false);
    }

    private function renderPdf(
        GenerateDateRangeReportRequest $request,
        KetahananBarangDagangLaminatingReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.laminating.ketahanan-barang-laminating-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = 'Laporan-Ketahanan-Barang-Dagang-Laminating.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $inline ? 'inline' : 'attachment', $filename),
        ]);
    }

    public function preview(
        GenerateDateRangeReportRequest $request,
        KetahananBarangDagangLaminatingReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];

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
            'summary' => $reportData['summary'] ?? [],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateDateRangeReportRequest $request,
        KetahananBarangDagangLaminatingReportService $reportService,
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
                ? 'Struktur output SP_LapKetahananBarangLaminating valid.'
                : 'Struktur output SP_LapKetahananBarangLaminating berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }
}
