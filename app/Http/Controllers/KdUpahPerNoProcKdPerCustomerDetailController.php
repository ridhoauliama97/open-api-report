<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateKdUpahPerNoProcKdPerCustomerDetailReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\KdUpahPerNoProcKdPerCustomerDetailReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        GotenbergPdfClient $gotenbergPdfClient,
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

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.kd-upah-per-no-proc-kd-per-customer-detail-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_title' => 'Laporan KD Upah Per-No.Proses KD Per-Cutomer Detail',
        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'reportData' => $reportData,
            'pdf_orientation' => 'portrait',
        ]);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        try {
            $pdfBytes = $gotenbergPdfClient->convertHtml($html, $metrics, $footerHtml);
        } catch (GotenbergPdfException $exception) {
            return $this->gotenbergFailureResponse($request, $exception->getMessage());
        }

        $dispositionType = 'attachment';

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, 'Laporan-KD-Upah-Per-No-Proses-KD-Per-Cutomer-Detail.pdf'),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateKdUpahPerNoProcKdPerCustomerDetailReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
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
