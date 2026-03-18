<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateNoParameterReportRequest;
use App\Services\LaminatingHidupDetailReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class LaminatingHidupDetailController extends Controller
{
    public function index(): View
    {
        return view('reports.laminating.laminating-hidup-detail-form');
    }

    public function download(
        GenerateNoParameterReportRequest $request,
        LaminatingHidupDetailReportService $reportService,
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

        try {
            $rows = $reportService->fetch();
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $totals = $this->computeTotals($rows);

        $pdf = $pdfGenerator->render('reports.laminating.laminating-hidup-detail-pdf', [
            'reportData' => [
                'rows' => $rows,
                'totals' => $totals,
            ],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = 'Laporan-Laminating-Hidup-Detail.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateNoParameterReportRequest $request,
        LaminatingHidupDetailReportService $reportService,
    ): JsonResponse {
        try {
            $rows = $reportService->fetch();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $totals = $this->computeTotals($rows);

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'summary' => [
                'total_rows' => count($rows),
                'total_jmlh_batang' => $totals['JmlhBatang'] ?? 0,
                'total_kubik' => $totals['Kubik'] ?? 0.0,
            ],
            'data' => $rows,
        ]);
    }

    public function previewPdf(
        GenerateNoParameterReportRequest $request,
        LaminatingHidupDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $rows = $reportService->fetch();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $totals = $this->computeTotals($rows);

        $pdf = $pdfGenerator->render('reports.laminating.laminating-hidup-detail-pdf', [
            'reportData' => [
                'rows' => $rows,
                'totals' => $totals,
            ],
            'generatedBy' => $request->user() ?? auth('api')->user(),
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = 'Laporan-Laminating-Hidup-Detail.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateNoParameterReportRequest $request,
        LaminatingHidupDetailReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapLaminatingHidupDetail valid.'
                : 'Struktur output SP_LapLaminatingHidupDetail berubah.',
            'meta' => [
                'parameter_count' => 0,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{JmlhBatang:int, Kubik:float}
     */
    private function computeTotals(array $rows): array
    {
        $batang = 0;
        $kubik = 0.0;

        foreach ($rows as $row) {
            $row = is_array($row) ? $row : (array) $row;
            $batang += (int) ($row['JmlhBatang'] ?? 0);
            $kubik += (float) ($row['Kubik'] ?? 0.0);
        }

        return [
            'JmlhBatang' => $batang,
            'Kubik' => $kubik,
        ];
    }
}
