<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateUmurSawnTimberDetailTonReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\UmurSawnTimberDetailTonReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class UmurSawnTimberDetailTonController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.umur-sawn-timber-detail-ton-form');
    }

    public function download(
        GenerateUmurSawnTimberDetailTonReportRequest $request,
        UmurSawnTimberDetailTonReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
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

        $parameters = $request->umurParameters();

        try {
            $rows = $reportService->fetch($parameters);
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

        $data = [
            'rows' => $rows,
            'parameters' => $parameters,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
        ];

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.umur-sawn-timber-detail-ton-pdf', $data);

        $metrics = $pdfGenerator->paperMetrics($data);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        try {
            $pdfBytes = $gotenbergPdfClient->convertHtml($html, $metrics, $footerHtml);
        } catch (GotenbergPdfException $exception) {
            return $this->gotenbergFailureResponse($request, $exception->getMessage());
        }

        $filename = sprintf(
            'Laporan Umur Sawn Timber Detail (Ton) - U%s-U%s-U%s-U%s.pdf',
            $parameters['Umur1'],
            $parameters['Umur2'],
            $parameters['Umur3'],
            $parameters['Umur4'],
        );

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateUmurSawnTimberDetailTonReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
    }

    public function preview(
        GenerateUmurSawnTimberDetailTonReportRequest $request,
        UmurSawnTimberDetailTonReportService $reportService,
    ): JsonResponse {
        $parameters = $request->umurParameters();

        try {
            $rows = $reportService->fetch($parameters);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'parameters' => $parameters,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateUmurSawnTimberDetailTonReportRequest $request,
        UmurSawnTimberDetailTonReportService $reportService,
    ): JsonResponse {
        $parameters = $request->umurParameters();

        try {
            $result = $reportService->healthCheck($parameters);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapUmurST valid.'
                : 'Struktur output SPWps_LapUmurST berubah.',
            'meta' => [
                'parameters' => $parameters,
            ],
            'health' => $result,
        ]);
    }
}
