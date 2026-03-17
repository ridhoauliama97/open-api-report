<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateUmurS4SDetailReportRequest;
use App\Services\PdfGenerator;
use App\Services\UmurS4SDetailReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class UmurS4SDetailController extends Controller
{
    private const DEFAULT_UMUR_1 = 15;
    private const DEFAULT_UMUR_2 = 30;
    private const DEFAULT_UMUR_3 = 60;
    private const DEFAULT_UMUR_4 = 90;

    public function index(): View
    {
        return view('reports.s4s.umur-s4s-detail-form');
    }

    public function download(
        GenerateUmurS4SDetailReportRequest $request,
        UmurS4SDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return back()
                ->withInput()
                ->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        $params = $request->umurParameters();

        try {
            $rows = $reportService->fetch($params);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $totals = $this->computeTotals($rows);

        $pdf = $pdfGenerator->render('reports.s4s.umur-s4s-detail-pdf', [
            'reportData' => [
                'rows' => $rows,
                'totals' => $totals,
            ],
            'umur1' => $params['Umur1'],
            'umur2' => $params['Umur2'],
            'umur3' => $params['Umur3'],
            'umur4' => $params['Umur4'],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            // Workaround for mPDF collapsed-border table bug (can crash when true).
            'pdf_pack_table_data' => false,
        ]);

        $filename = $this->buildFilename($params);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateUmurS4SDetailReportRequest $request,
        UmurS4SDetailReportService $reportService,
    ): JsonResponse {
        $params = $request->umurParameters();

        try {
            $rows = $reportService->fetch($params);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
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
        GenerateUmurS4SDetailReportRequest $request,
        UmurS4SDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $params = $request->umurParameters();

        try {
            $rows = $reportService->fetch($params);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $totals = $this->computeTotals($rows);

        $pdf = $pdfGenerator->render('reports.s4s.umur-s4s-detail-pdf', [
            'reportData' => [
                'rows' => $rows,
                'totals' => $totals,
            ],
            'umur1' => $params['Umur1'],
            'umur2' => $params['Umur2'],
            'umur3' => $params['Umur3'],
            'umur4' => $params['Umur4'],
            'generatedBy' => $request->user() ?? auth('api')->user(),
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            // Workaround for mPDF collapsed-border table bug (can crash when true).
            'pdf_pack_table_data' => false,
        ]);

        $filename = $this->buildFilename($params);

        // Inline so it opens in a new tab, but keep filename so "Download" from PDF viewer uses a good name.
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateUmurS4SDetailReportRequest $request,
        UmurS4SDetailReportService $reportService,
    ): JsonResponse {
        $params = $request->umurParameters();

        try {
            $result = $reportService->healthCheck($params);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapUmurS4S valid.'
                : 'Struktur output SP_LapUmurS4S berubah.',
            'meta' => [
                'umur1' => $params['Umur1'],
                'umur2' => $params['Umur2'],
                'umur3' => $params['Umur3'],
                'umur4' => $params['Umur4'],
            ],
            'health' => $result,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, float>
     */
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

    /**
     * @param array{Umur1:int,Umur2:int,Umur3:int,Umur4:int} $params
     */
    private function buildFilename(array $params): string
    {
        return sprintf(
            'Laporan-Umur-S4S-Detail-%s-%s-%s-%s.pdf',
            $params['Umur1'],
            $params['Umur2'],
            $params['Umur3'],
            $params['Umur4'],
        );
    }
}
