<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateBahanTerpakaiReportRequest;
use App\Services\BahanTerpakaiReportService;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class BahanTerpakaiController extends Controller
{
    /**
     * Display the default page for this resource.
     */
    public function index(): View
    {
        return view('reports.bahan-terpakai-form');
    }

    /**
     * Execute download logic.
     */
    public function download(
        GenerateBahanTerpakaiReportRequest $request,
        BahanTerpakaiReportService $reportService,
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

        $reportDate = $this->extractDate($request);

        try {
            $rows = $reportService->fetch($reportDate);
            $subRows = $reportService->fetchSubReport($reportDate);
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

        $html = $pdfGenerator->renderHtml('reports.bahan-terpakai-pdf', [
            'rows' => $rows,
            'subRows' => $subRows,
            'reportDate' => $reportDate,
            'tonToM3Factor' => (float) config('reports.bahan_terpakai.ton_to_m3_factor', 1.416),
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'rows' => $rows,
            'subRows' => $subRows,
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

        $filename = sprintf('Laporan-Bahan-Terpakai-%s.pdf', $reportDate);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateBahanTerpakaiReportRequest $request, string $message): JsonResponse|RedirectResponse
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

    /**
     * Execute preview logic.
     */
    public function preview(
        GenerateBahanTerpakaiReportRequest $request,
        BahanTerpakaiReportService $reportService,
    ): JsonResponse {
        $reportDate = $this->extractDate($request);

        try {
            $rows = $reportService->fetch($reportDate);
            $subRows = $reportService->fetchSubReport($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'date' => $reportDate,
                'TglAwal' => $reportDate,
                'total_rows' => count($rows),
                'total_sub_rows' => count($subRows),
                'column_order' => array_keys($rows[0] ?? []),
                'sub_column_order' => array_keys($subRows[0] ?? []),
            ],
            'data' => $rows,
            'sub_data' => $subRows,
        ]);
    }

    /**
     * Execute health logic.
     */
    public function health(
        GenerateBahanTerpakaiReportRequest $request,
        BahanTerpakaiReportService $reportService,
    ): JsonResponse {
        $reportDate = $this->extractDate($request);

        try {
            $result = $reportService->healthCheck($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapBahanTerpakai valid.'
                : 'Struktur output SPWps_LapBahanTerpakai berubah.',
            'meta' => [
                'date' => $reportDate,
                'TglAwal' => $reportDate,
            ],
            'health' => $result,
        ]);
    }

    private function extractDate(GenerateBahanTerpakaiReportRequest $request): string
    {
        return (string) $request->input('date', $request->input('start_date', $request->input('TglAwal')));
    }
}
