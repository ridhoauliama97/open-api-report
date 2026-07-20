<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateKdUpahPerNoProcKdPerCustomerDetailReportRequest;
use App\Services\KdUpahPerNoProcKdPerCustomerDetailReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class KdUpahPerNoProcKdPerCustomerDetailController extends Controller
{
    public function preview(
        GenerateKdUpahPerNoProcKdPerCustomerDetailReportRequest $request,
        KdUpahPerNoProcKdPerCustomerDetailReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData($request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'no_proc_kd' => (string) ($reportData['filters']['no_proc_kd'] ?? ''),
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_no_st' => (int) ($reportData['summary']['total_no_st'] ?? 0),
                'total_pcs' => (int) ($reportData['summary']['total_pcs'] ?? 0),
                'grand_total_m3' => (float) ($reportData['summary']['grand_total_m3'] ?? 0.0),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'data' => $reportData['rows'] ?? [],
            'report_data' => $reportData,
        ]);
    }

    public function download(
        GenerateKdUpahPerNoProcKdPerCustomerDetailReportRequest $request,
        KdUpahPerNoProcKdPerCustomerDetailReportService $reportService,
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
            $reportData = $reportService->buildReportData($request->validated());
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.sawn-timber.kd-upah-per-no-proc-kd-per-customer-detail-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_title' => 'Laporan KD Upah Per-No.Proses KD Per-Cutomer Detail',
        ]);

        $dispositionType = $request->routeIs('reports.sawn-timber.kd-upah-per-no-proc-kd-per-customer-detail.preview-pdf')
            || $request->expectsJson()
            ? 'attachment'
            : 'inline';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, 'Laporan-KD-Upah-Per-No-Proses-KD-Per-Cutomer-Detail.pdf'),
        ]);
    }

    public function health(
        GenerateKdUpahPerNoProcKdPerCustomerDetailReportRequest $request,
        KdUpahPerNoProcKdPerCustomerDetailReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck($request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapKDUpahPerNoProcKDPerCustomerDetail valid.'
                : 'Struktur output SP_LapKDUpahPerNoProcKDPerCustomerDetail berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
