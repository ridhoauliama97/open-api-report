<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateKdKeluarMasukReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\KdKeluarMasukReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class KdKeluarMasukController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.kd-keluar-masuk-form');
    }

    public function previewPdf(
        GenerateKdKeluarMasukReportRequest $request,
        KdKeluarMasukReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, $gotenbergPdfClient);
    }

    public function download(
        GenerateKdKeluarMasukReportRequest $request,
        KdKeluarMasukReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, $gotenbergPdfClient);
    }

    private function renderPdf(
        GenerateKdKeluarMasukReportRequest $request,
        KdKeluarMasukReportService $reportService,
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

        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $noKd = $request->noKd();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate, $noKd);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdfData = [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'noKd' => $noKd,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),

            // Kept from the mPDF version: this report is always landscape.
            'pdf_orientation' => 'landscape',
        ];

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.kd-keluar-masuk-pdf', $pdfData);

        $metrics = $pdfGenerator->paperMetrics($pdfData);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        try {
            $pdfBytes = $gotenbergPdfClient->convertHtml($html, $metrics, $footerHtml);
        } catch (GotenbergPdfException $exception) {
            return $this->gotenbergFailureResponse($request, $exception->getMessage());
        }

        $suffix = $noKd ? "-KD-{$noKd}" : '';
        $filename = sprintf('Laporan-KD-Keluar-Masuk%s-%s-sd-%s.pdf', $suffix, $startDate, $endDate);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateKdKeluarMasukReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
    }

    public function preview(
        GenerateKdKeluarMasukReportRequest $request,
        KdKeluarMasukReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $noKd = $request->noKd();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate, $noKd);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rowsKeluar = is_array($reportData['rows_keluar'] ?? null) ? $reportData['rows_keluar'] : [];
        $rowsMasih = is_array($reportData['rows_masih'] ?? null) ? $reportData['rows_masih'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'no_kd' => $noKd,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows_keluar' => count($rowsKeluar),
                'total_rows_masih' => count($rowsMasih),
                'column_order' => array_keys($rowsKeluar[0] ?? ($rowsMasih[0] ?? [])),
            ],
            'summary' => $reportData['summary'] ?? [],
            'totals' => $reportData['totals'] ?? [],
            'data' => [
                'keluar' => $rowsKeluar,
                'masih' => $rowsMasih,
            ],
        ]);
    }

    public function health(
        GenerateKdKeluarMasukReportRequest $request,
        KdKeluarMasukReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapKDKeluarMasuk valid.'
                : 'Struktur output SP_LapKDKeluarMasuk berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }
}
