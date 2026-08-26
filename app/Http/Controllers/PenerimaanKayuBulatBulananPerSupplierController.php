<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\PenerimaanKayuBulatBulananPerSupplierReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class PenerimaanKayuBulatBulananPerSupplierController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat.penerimaan-bulanan-per-supplier-form');
    }

    public function download(
        GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest $request,
        PenerimaanKayuBulatBulananPerSupplierReportService $reportService,
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

        $pdfData = [
            'rows' => $reportData['data'],
            'subRows' => $reportData['sub_data'],
            'detailRows' => $reportData['detail_data'] ?? [],
            'groupedRows' => $reportData['grouped_data'],
            'groupedSubRows' => $reportData['grouped_sub_data'],
            'groupedDetailRows' => $reportData['grouped_detail_data'] ?? [],
            'summary' => $reportData['summary'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ];

        $html = $pdfGenerator->renderHtml('reports.kayu-bulat.penerimaan-bulanan-per-supplier-pdf', $pdfData);

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

        $filename = sprintf('Laporan-Penerimaan-Kayu-Bulat-Bulanan-Per-Supplier-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
    }

    public function preview(
        GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest $request,
        PenerimaanKayuBulatBulananPerSupplierReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = $reportData['data'];
        $subRows = $reportData['sub_data'];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'total_sub_rows' => count($subRows),
                'total_detail_rows' => count($reportData['detail_data'] ?? []),
                'total_suppliers' => $reportData['summary']['detail']['total_suppliers']
                    ?? ($reportData['summary']['main']['total_suppliers'] ?? 0),
                'column_order' => array_keys($rows[0] ?? []),
                'sub_column_order' => array_keys($subRows[0] ?? []),
                'detail_column_order' => array_keys(($reportData['detail_data'][0] ?? [])),
                'supplier_column' => $reportData['supplier_column'] ?? null,
                'sub_supplier_column' => $reportData['sub_supplier_column'] ?? null,
                'detail_supplier_column' => $reportData['detail_supplier_column'] ?? null,
            ],
            'summary' => $reportData['summary'],
            'data' => $rows,
            'sub_data' => $subRows,
            'detail_data' => $reportData['detail_data'] ?? [],
            'grouped_data' => $reportData['grouped_data'],
            'grouped_sub_data' => $reportData['grouped_sub_data'],
            'grouped_detail_data' => $reportData['grouped_detail_data'] ?? [],
        ]);
    }

    public function health(
        GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest $request,
        PenerimaanKayuBulatBulananPerSupplierReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LaPenerimaanKayuBulatBulananPerSupplier valid.'
                : 'Struktur output SP_LaPenerimaanKayuBulatBulananPerSupplier berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest $request): array
    {
        $startDate = $request->input('start_date', $request->input('TglAwal'));
        $endDate = $request->input('end_date', $request->input('TglAkhir'));

        return [(string) $startDate, (string) $endDate];
    }
}
