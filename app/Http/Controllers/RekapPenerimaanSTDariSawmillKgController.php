<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateRekapPenerimaanSTDariSawmillKgReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\RekapPenerimaanSTDariSawmillKgReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class RekapPenerimaanSTDariSawmillKgController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat-rambung.rekap-penerimaan-st-dari-sawmill-kg-form');
    }

    public function download(
        GenerateRekapPenerimaanSTDariSawmillKgReportRequest $request,
        RekapPenerimaanSTDariSawmillKgReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient, true);
    }

    public function previewPdf(
        GenerateRekapPenerimaanSTDariSawmillKgReportRequest $request,
        RekapPenerimaanSTDariSawmillKgReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient, true);
    }

    public function preview(
        GenerateRekapPenerimaanSTDariSawmillKgReportRequest $request,
        RekapPenerimaanSTDariSawmillKgReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($reportData['rows'] ?? []),
                'total_dates' => $reportData['summary']['total_dates'] ?? 0,
                'total_receipts' => $reportData['summary']['total_receipts'] ?? 0,
                'column_order' => array_keys($reportData['rows'][0] ?? []),
                'date_column' => $reportData['date_column'] ?? null,
                'kategori_column' => $reportData['kategori_column'] ?? null,
                'grade_column' => $reportData['grade_column'] ?? null,
                'kb_column' => $reportData['kb_column'] ?? null,
                'st_column' => $reportData['st_column'] ?? null,
                'percent_column' => $reportData['percent_column'] ?? null,
                'no_penerimaan_column' => $reportData['no_penerimaan_column'] ?? null,
                'no_kayu_bulat_column' => $reportData['no_kayu_bulat_column'] ?? null,
                'supplier_column' => $reportData['supplier_column'] ?? null,
                'no_truk_column' => $reportData['no_truk_column'] ?? null,
                'jenis_kayu_column' => $reportData['jenis_kayu_column'] ?? null,
                'meja_column' => $reportData['meja_column'] ?? null,
            ],
            'summary' => $reportData['summary'] ?? [],
            'grouped_data' => $reportData['date_groups'] ?? [],
            'grand_totals' => $reportData['grand_totals'] ?? null,
            'data' => $reportData['rows'] ?? [],
        ]);
    }

    public function health(
        GenerateRekapPenerimaanSTDariSawmillKgReportRequest $request,
        RekapPenerimaanSTDariSawmillKgReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapRekapPenerimaanSTDariSawmill valid.'
                : 'Struktur output SP_LapRekapPenerimaanSTDariSawmill berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateRekapPenerimaanSTDariSawmillKgReportRequest $request,
        RekapPenerimaanSTDariSawmillKgReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
        bool $attachment,
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

        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $viewData = [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_column_count' => 7,
        ];

        $html = $pdfGenerator->renderHtml('reports.kayu-bulat-rambung.rekap-penerimaan-st-dari-sawmill-kg-pdf', $viewData);

        $metrics = $pdfGenerator->paperMetrics($viewData);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        try {
            $pdfBytes = $gotenbergPdfClient->convertHtml($html, $metrics, $footerHtml);
        } catch (GotenbergPdfException $exception) {
            return $this->gotenbergFailureResponse($request, $exception->getMessage());
        }

        $filename = sprintf('Laporan-Rekap-Penerimaan-ST-Dari-Sawmill-Timbang-KG-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $attachment ? 'attachment' : 'inline';

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateRekapPenerimaanSTDariSawmillKgReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateRekapPenerimaanSTDariSawmillKgReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
