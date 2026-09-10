<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGotenbergPdfResponses;
use App\Http\Requests\GeneratePenerimaanKayuBulatPerSupplierKgReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\PenerimaanKayuBulatPerSupplierKgReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PenerimaanKayuBulatPerSupplierKgController extends Controller
{
    use BuildsGotenbergPdfResponses;

    public function index(): View
    {
        return view('reports.kayu-bulat.penerimaan-per-supplier-kg-form');
    }

    public function download(
        GeneratePenerimaanKayuBulatPerSupplierKgReportRequest $request,
        PenerimaanKayuBulatPerSupplierKgReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient);
    }

    public function previewPdf(
        GeneratePenerimaanKayuBulatPerSupplierKgReportRequest $request,
        PenerimaanKayuBulatPerSupplierKgReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient);
    }

    public function preview(
        GeneratePenerimaanKayuBulatPerSupplierKgReportRequest $request,
        PenerimaanKayuBulatPerSupplierKgReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = $reportData['rows'];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'total_suppliers' => $reportData['summary']['total_suppliers'] ?? 0,
                'total_groups' => $reportData['summary']['total_groups'] ?? 0,
                'column_order' => array_keys($rows[0] ?? []),
                'group_names' => $reportData['group_names'] ?? [],
                'columns' => $reportData['columns'] ?? [],
            ],
            'summary' => $reportData['summary'],
            'data' => $rows,
            'grouped_data' => $reportData['suppliers'] ?? [],
        ]);
    }

    public function health(
        GeneratePenerimaanKayuBulatPerSupplierKgReportRequest $request,
        PenerimaanKayuBulatPerSupplierKgReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapPenerimaanKBPerSupplier valid.'
                : 'Struktur output SP_LapPenerimaanKBPerSupplier berubah.',
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
        GeneratePenerimaanKayuBulatPerSupplierKgReportRequest $request,
        PenerimaanKayuBulatPerSupplierKgReportService $reportService,
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

        $html = $pdfGenerator->renderHtml('reports.kayu-bulat.penerimaan-per-supplier-kg-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        $filename = sprintf('Laporan-Penerimaan-Kayu-Bulat-Per-Supplier-Timbang-KG-%s-sd-%s.pdf', $startDate, $endDate);

        return $this->buildGotenbergPdfResponse($request, $html, $filename, $metrics, $footerHtml, 'attachment');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GeneratePenerimaanKayuBulatPerSupplierKgReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
