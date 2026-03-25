<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateKdKeluarMasukReportRequest;
use App\Services\KdKeluarMasukReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class KdKeluarMasukController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.kd-keluar-masuk-form');
    }

    public function previewPdf(
        GenerateKdKeluarMasukReportRequest $request,
        KdKeluarMasukReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, true);
    }

    public function download(
        GenerateKdKeluarMasukReportRequest $request,
        KdKeluarMasukReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, false);
    }

    private function renderPdf(
        GenerateKdKeluarMasukReportRequest $request,
        KdKeluarMasukReportService $reportService,
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
        $noKd = $request->noKd();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate, $noKd);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.sawn-timber.kd-keluar-masuk-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'noKd' => $noKd,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'landscape',
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $suffix = $noKd ? "-KD-{$noKd}" : '';
        $filename = sprintf('Laporan-KD-Keluar-Masuk%s-%s-sd-%s.pdf', $suffix, $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $inline ? 'inline' : 'attachment', $filename),
        ]);
    }

    public function preview(
        GenerateKdKeluarMasukReportRequest $request,
        KdKeluarMasukReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $noKd = $request->noKd();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate, $noKd);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rowsKeluar = is_array($reportData['rows_keluar'] ?? null) ? $reportData['rows_keluar'] : [];
        $rowsMasih = is_array($reportData['rows_masih'] ?? null) ? $reportData['rows_masih'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'no_kd' => $noKd,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows_keluar' => count($rowsKeluar),
                'total_rows_masih' => count($rowsMasih),
                'column_order' => array_keys($rowsKeluar[0] ?? ($rowsMasih[0] ?? [])),
            ],
            'summary' => $reportData['summary'] ?? [],
            'totals' => $reportData['totals'] ?? [],
            'data' => [
                'keluar' => $rowsKeluar,
                'masih' => $rowsMasih,
            ],
        ]);
    }

    public function health(
        GenerateKdKeluarMasukReportRequest $request,
        KdKeluarMasukReportService $reportService,
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
                ? 'Struktur output SP_LapKDKeluarMasuk valid.'
                : 'Struktur output SP_LapKDKeluarMasuk berubah.',
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
