<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateKdUpahPerCustomerReportRequest;
use App\Services\KdUpahPerCustomerReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class KdUpahPerCustomerController extends Controller
{
    public function preview(
        GenerateKdUpahPerCustomerReportRequest $request,
        KdUpahPerCustomerReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_customers' => (int) ($reportData['summary']['total_customers'] ?? 0),
                'grand_total_m3' => (float) ($reportData['summary']['grand_total_m3'] ?? 0.0),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'data' => $reportData['rows'] ?? [],
            'report_data' => $reportData,
        ]);
    }

    public function download(
        GenerateKdUpahPerCustomerReportRequest $request,
        KdUpahPerCustomerReportService $reportService,
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
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.sawn-timber.kd-upah-per-customer-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_title' => 'Laporan KD Upah Per-Cutomer',
        ]);

        $dispositionType = $request->routeIs('reports.sawn-timber.kd-upah-per-customer.preview-pdf')
            || $request->expectsJson()
            ? 'inline'
            : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, 'Laporan-KD-Upah-Per-Cutomer.pdf'),
        ]);
    }

    public function health(
        GenerateKdUpahPerCustomerReportRequest $request,
        KdUpahPerCustomerReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapKDUpahPerCutomer valid.'
                : 'Struktur output SP_LapKDUpahPerCutomer berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
