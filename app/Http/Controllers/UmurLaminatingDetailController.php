<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateUmurLaminatingDetailReportRequest;
use App\Services\PdfGenerator;
use App\Services\UmurLaminatingDetailReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class UmurLaminatingDetailController extends Controller
{
    public function index(): View
    {
        return view('reports.laminating.umur-laminating-detail-form');
    }

    public function download(
        GenerateUmurLaminatingDetailReportRequest $request,
        UmurLaminatingDetailReportService $reportService,
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

        $params = $request->umurParameters();

        try {
            $rows = $reportService->fetch($params);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.laminating.umur-laminating-detail-pdf', [
            'reportData' => [
                'rows' => $rows,
                'totals' => $this->computeTotals($rows),
            ],
            'umur1' => $params['Umur1'],
            'umur2' => $params['Umur2'],
            'umur3' => $params['Umur3'],
            'umur4' => $params['Umur4'],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = $this->buildFilename($params);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateUmurLaminatingDetailReportRequest $request,
        UmurLaminatingDetailReportService $reportService,
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
        GenerateUmurLaminatingDetailReportRequest $request,
        UmurLaminatingDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $params = $request->umurParameters();

        try {
            $rows = $reportService->fetch($params);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = $pdfGenerator->render('reports.laminating.umur-laminating-detail-pdf', [
            'reportData' => [
                'rows' => $rows,
                'totals' => $this->computeTotals($rows),
            ],
            'umur1' => $params['Umur1'],
            'umur2' => $params['Umur2'],
            'umur3' => $params['Umur3'],
            'umur4' => $params['Umur4'],
            'generatedBy' => $request->user() ?? auth('api')->user(),
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = $this->buildFilename($params);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateUmurLaminatingDetailReportRequest $request,
        UmurLaminatingDetailReportService $reportService,
    ): JsonResponse {
        $params = $request->umurParameters();

        try {
            $result = $reportService->healthCheck($params);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapUmurLaminating valid.'
                : 'Struktur output SP_LapUmurLaminating berubah.',
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
            'Laporan-Umur-Laminating-Detail-%s-%s-%s-%s.pdf',
            $params['Umur1'],
            $params['Umur2'],
            $params['Umur3'],
            $params['Umur4'],
        );
    }
}
