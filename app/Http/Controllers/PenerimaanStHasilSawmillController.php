<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GeneratePenerimaanStHasilSawmillReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\PenerimaanStHasilSawmillReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class PenerimaanStHasilSawmillController extends Controller
{
    public function preview(
        GeneratePenerimaanStHasilSawmillReportRequest $request,
        PenerimaanStHasilSawmillReportService $reportService,
    ): JsonResponse {
        $noPenSt = $request->noPenSt();

        try {
            $reportData = $reportService->buildReportData($noPenSt);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'no_pen_st' => $noPenSt,
                'NoPenST' => $noPenSt,
                'total_rows' => count($reportData['rows'] ?? []),
                'total_sub_rows' => count($reportData['sub_rows'] ?? []),
                'total_pcs' => $reportData['summary']['total_pcs'] ?? 0,
                'total_ton' => $reportData['summary']['total_ton'] ?? 0,
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'data' => $reportData['rows'] ?? [],
            'sub_data' => $reportData['sub_rows'] ?? [],
            'report_data' => $reportData,
        ]);
    }

    public function download(
        GeneratePenerimaanStHasilSawmillReportRequest $request,
        PenerimaanStHasilSawmillReportService $reportService,
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

        $noPenSt = $request->noPenSt();

        try {
            $reportData = $reportService->buildReportData($noPenSt);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.penerimaan-st-hasil-sawmill-pdf', [
            'reportData' => $reportData,
            'noPenSt' => $noPenSt,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_title' => 'Laporan Penerimaan ST Hasil Sawmill',
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

        $filename = sprintf('Laporan-Penerimaan-ST-Hasil-Sawmill-%s.pdf', str_replace(['/', '\\'], '-', $noPenSt));

        $dispositionType = 'attachment';

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GeneratePenerimaanStHasilSawmillReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
    }

    public function health(
        GeneratePenerimaanStHasilSawmillReportRequest $request,
        PenerimaanStHasilSawmillReportService $reportService,
    ): JsonResponse {
        $noPenSt = $request->noPenSt();

        try {
            $result = $reportService->healthCheck($noPenSt);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapPenerimaanSTSawmill valid.'
                : 'Struktur output SP_LapPenerimaanSTSawmill berubah.',
            'meta' => [
                'no_pen_st' => $noPenSt,
                'NoPenST' => $noPenSt,
            ],
            'health' => $result,
        ]);
    }
}
