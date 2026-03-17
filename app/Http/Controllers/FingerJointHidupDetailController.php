<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateNoParameterReportRequest;
use App\Services\FingerJointHidupDetailReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class FingerJointHidupDetailController extends Controller
{
    public function index(): View
    {
        return view('reports.finger-joint.finger-joint-hidup-detail-form');
    }

    public function download(
        GenerateNoParameterReportRequest $request,
        FingerJointHidupDetailReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.finger-joint.finger-joint-hidup-detail-pdf', [
            'reportData' => [
                'rows' => $rows,
                'totals' => $totals,
            ],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            // Workaround for mPDF collapsed-border table bug (can crash when true).
            'pdf_pack_table_data' => false,
        ]);

        $filename = 'Laporan-Finger-Joint-Hidup-Detail.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateNoParameterReportRequest $request,
        FingerJointHidupDetailReportService $reportService,
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
                'total_m3' => $totals['M3'] ?? 0.0,
            ],
            'data' => $rows,
        ]);
    }

    public function previewPdf(
        GenerateNoParameterReportRequest $request,
        FingerJointHidupDetailReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        try {
            $rows = $reportService->fetch();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $totals = $this->computeTotals($rows);

        $pdf = $pdfGenerator->render('reports.finger-joint.finger-joint-hidup-detail-pdf', [
            'reportData' => [
                'rows' => $rows,
                'totals' => $totals,
            ],
            'generatedBy' => $request->user() ?? auth('api')->user(),
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = 'Laporan-Finger-Joint-Hidup-Detail.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateNoParameterReportRequest $request,
        FingerJointHidupDetailReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapFingerJointHidupDetail valid.'
                : 'Struktur output SP_LapFingerJointHidupDetail berubah.',
            'meta' => [
                'parameter_count' => 0,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{JmlhBatang:int, M3:float}
     */
    private function computeTotals(array $rows): array
    {
        $batang = 0;
        $m3 = 0.0;

        foreach ($rows as $row) {
            $row = is_array($row) ? $row : (array) $row;
            $batang += (int) ($row['JmlhBatang'] ?? 0);
            $m3 += (float) ($row['M3'] ?? 0.0);
        }

        return [
            'JmlhBatang' => $batang,
            'M3' => $m3,
        ];
    }
}

