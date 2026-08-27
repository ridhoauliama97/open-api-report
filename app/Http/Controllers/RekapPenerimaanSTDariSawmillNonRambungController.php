<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateRekapPenerimaanSTDariSawmillNonRambungReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\RekapPenerimaanSTDariSawmillNonRambungReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class RekapPenerimaanSTDariSawmillNonRambungController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.rekap-penerimaan-st-dari-sawmill-non-rambung-form');
    }

    public function download(
        GenerateRekapPenerimaanSTDariSawmillNonRambungReportRequest $request,
        RekapPenerimaanSTDariSawmillNonRambungReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient, true);
    }

    public function previewPdf(
        GenerateRekapPenerimaanSTDariSawmillNonRambungReportRequest $request,
        RekapPenerimaanSTDariSawmillNonRambungReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient, true);
    }

    public function preview(
        GenerateRekapPenerimaanSTDariSawmillNonRambungReportRequest $request,
        RekapPenerimaanSTDariSawmillNonRambungReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'total_suppliers' => $reportData['summary']['total_suppliers'] ?? 0,
                'column_order' => array_keys($rows[0] ?? []),
                'supplier_column' => $reportData['supplier_column'] ?? null,
                'date_column' => $reportData['date_column'] ?? null,
                'source_columns' => $reportData['source_columns'] ?? null,
                'detected_columns' => $reportData['detected_columns'] ?? null,
            ],
            'summary' => $reportData['summary'] ?? [],
            'grouped_data' => $reportData['supplier_groups'] ?? [],
            'supplier_summary' => $reportData['supplier_summaries'] ?? null,
            'grand_totals' => $reportData['grand_totals'] ?? null,
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateRekapPenerimaanSTDariSawmillNonRambungReportRequest $request,
        RekapPenerimaanSTDariSawmillNonRambungReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapRekapPenSTDariSawmill valid.'
                : 'Struktur output SPWps_LapRekapPenSTDariSawmill berubah.',
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
        GenerateRekapPenerimaanSTDariSawmillNonRambungReportRequest $request,
        RekapPenerimaanSTDariSawmillNonRambungReportService $reportService,
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
            'pdf_orientation' => 'landscape',
        ];

        $html = $pdfGenerator->renderHtml('reports.sawn-timber.rekap-penerimaan-st-dari-sawmill-non-rambung-pdf', $viewData);

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

        $filename = sprintf('Laporan-Rekap-Penerimaan-ST-Dari-Sawmill-Non-Rambung-%s-sd-%s.pdf', $startDate, $endDate);
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
    private function gotenbergFailureResponse(GenerateRekapPenerimaanSTDariSawmillNonRambungReportRequest $request, string $message): JsonResponse|RedirectResponse
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
    private function extractDates(GenerateRekapPenerimaanSTDariSawmillNonRambungReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
