<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateUmurFingerJointDetailReportRequest;
use App\Services\PdfGenerator;
use App\Services\UmurFingerJointDetailReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class UmurFingerJointDetailController extends Controller
{
    private const DEFAULT_UMUR_1 = 15;
    private const DEFAULT_UMUR_2 = 30;
    private const DEFAULT_UMUR_3 = 60;
    private const DEFAULT_UMUR_4 = 90;

    public function index(): View
    {
        return view('reports.finger-joint.umur-finger-joint-detail-form');
    }

    public function download(
        GenerateUmurFingerJointDetailReportRequest $request,
        UmurFingerJointDetailReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.finger-joint.umur-finger-joint-detail-pdf', [
            'reportData' => [
                'rows' => $rows,
                'totals' => $totals,
            ],
            'umur1' => $params['Umur1'] ?: self::DEFAULT_UMUR_1,
            'umur2' => $params['Umur2'] ?: self::DEFAULT_UMUR_2,
            'umur3' => $params['Umur3'] ?: self::DEFAULT_UMUR_3,
            'umur4' => $params['Umur4'] ?: self::DEFAULT_UMUR_4,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            // Workaround for mPDF collapsed-border table bug (can crash when true).
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf(
            'Laporan-Umur-Finger-Joint-Detail-%s-%s-%s-%s.pdf',
            $params['Umur1'],
            $params['Umur2'],
            $params['Umur3'],
            $params['Umur4'],
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateUmurFingerJointDetailReportRequest $request,
        UmurFingerJointDetailReportService $reportService,
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
        GenerateUmurFingerJointDetailReportRequest $request,
        UmurFingerJointDetailReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.finger-joint.umur-finger-joint-detail-pdf', [
            'reportData' => [
                'rows' => $rows,
                'totals' => $totals,
            ],
            'umur1' => $params['Umur1'] ?: self::DEFAULT_UMUR_1,
            'umur2' => $params['Umur2'] ?: self::DEFAULT_UMUR_2,
            'umur3' => $params['Umur3'] ?: self::DEFAULT_UMUR_3,
            'umur4' => $params['Umur4'] ?: self::DEFAULT_UMUR_4,
            'generatedBy' => $request->user() ?? auth('api')->user(),
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            // Workaround for mPDF collapsed-border table bug (can crash when true).
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf(
            'Laporan-Umur-Finger-Joint-Detail-%s-%s-%s-%s.pdf',
            $params['Umur1'],
            $params['Umur2'],
            $params['Umur3'],
            $params['Umur4'],
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            // Ensure browser uses a friendly filename (instead of "preview-pdf").
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateUmurFingerJointDetailReportRequest $request,
        UmurFingerJointDetailReportService $reportService,
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
                ? 'Struktur output SP_LapUmurFingerJoint valid.'
                : 'Struktur output SP_LapUmurFingerJoint berubah.',
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
}
