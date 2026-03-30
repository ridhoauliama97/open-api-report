<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateUmurBarangJadiDetailReportRequest;
use App\Services\PdfGenerator;
use App\Services\UmurBarangJadiDetailReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class UmurBarangJadiDetailController extends Controller
{
    private const DEFAULT_UMUR_1 = 15;
    private const DEFAULT_UMUR_2 = 30;
    private const DEFAULT_UMUR_3 = 60;
    private const DEFAULT_UMUR_4 = 90;

    public function index(): View
    {
        return view('reports.barang-jadi.umur-barang-jadi-detail-form');
    }

    public function download(
        GenerateUmurBarangJadiDetailReportRequest $request,
        UmurBarangJadiDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return back()->withInput()->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        $params = $request->umurParameters();

        try {
            $rows = $reportService->fetch($params);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['report' => $exception->getMessage()]);
        }

        $totals = $this->computeTotals($rows);

        $pdf = $pdfGenerator->render('reports.barang-jadi.umur-barang-jadi-detail-pdf', [
            'reportData' => ['rows' => $rows, 'totals' => $totals],
            'umur1' => $params['Umur1'] ?: self::DEFAULT_UMUR_1,
            'umur2' => $params['Umur2'] ?: self::DEFAULT_UMUR_2,
            'umur3' => $params['Umur3'] ?: self::DEFAULT_UMUR_3,
            'umur4' => $params['Umur4'] ?: self::DEFAULT_UMUR_4,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf('Laporan-Umur-Barang-Jadi-Detail-%s-%s-%s-%s.pdf', $params['Umur1'], $params['Umur2'], $params['Umur3'], $params['Umur4']);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateUmurBarangJadiDetailReportRequest $request,
        UmurBarangJadiDetailReportService $reportService,
    ): JsonResponse {
        $params = $request->umurParameters();

        try {
            $rows = $reportService->fetch($params);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'umur1' => $params['Umur1'],
                'umur2' => $params['Umur2'],
                'umur3' => $params['Umur3'],
                'umur4' => $params['Umur4'],
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function previewPdf(
        GenerateUmurBarangJadiDetailReportRequest $request,
        UmurBarangJadiDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $params = $request->umurParameters();

        try {
            $rows = $reportService->fetch($params);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $totals = $this->computeTotals($rows);

        $pdf = $pdfGenerator->render('reports.barang-jadi.umur-barang-jadi-detail-pdf', [
            'reportData' => ['rows' => $rows, 'totals' => $totals],
            'umur1' => $params['Umur1'] ?: self::DEFAULT_UMUR_1,
            'umur2' => $params['Umur2'] ?: self::DEFAULT_UMUR_2,
            'umur3' => $params['Umur3'] ?: self::DEFAULT_UMUR_3,
            'umur4' => $params['Umur4'] ?: self::DEFAULT_UMUR_4,
            'generatedBy' => $request->user() ?? auth('api')->user(),
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf('Laporan-Umur-Barang-Jadi-Detail-%s-%s-%s-%s.pdf', $params['Umur1'], $params['Umur2'], $params['Umur3'], $params['Umur4']);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateUmurBarangJadiDetailReportRequest $request,
        UmurBarangJadiDetailReportService $reportService,
    ): JsonResponse {
        $params = $request->umurParameters();

        try {
            $result = $reportService->healthCheck($params);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy'] ? 'Struktur output SP_LapUmurBarangJadi valid.' : 'Struktur output SP_LapUmurBarangJadi berubah.',
            'meta' => $params,
            'health' => $result,
        ]);
    }

    private function computeTotals(array $rows): array
    {
        $cols = ['Period1', 'Period2', 'Period3', 'Period4', 'Period5', 'Total'];
        $totals = array_fill_keys($cols, 0.0);

        foreach ($rows as $row) {
            foreach ($cols as $col) {
                $totals[$col] += (float) ($row[$col] ?? 0);
            }
        }

        return $totals;
    }
}
