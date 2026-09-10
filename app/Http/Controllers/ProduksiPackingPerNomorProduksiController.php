<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGotenbergPdfResponses;
use App\Http\Requests\GenerateProduksiPerNomorProduksiReportRequest;
use App\Services\PdfGenerator;
use App\Services\ProduksiPackingPerNomorProduksiReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ProduksiPackingPerNomorProduksiController extends Controller
{
    use BuildsGotenbergPdfResponses;

    public function index(): View
    {
        return view('reports.proses-produksi.produksi-packing-per-nomor-produksi-form');
    }

    public function download(
        GenerateProduksiPerNomorProduksiReportRequest $request,
        ProduksiPackingPerNomorProduksiReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $noProduksi = $request->noProduksi();
        $generatedBy = $this->resolveReportGeneratedBy($request);

        try {
            $report = $reportService->fetch($noProduksi);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.proses-produksi.produksi-packing-per-nomor-produksi-pdf', [
            'report' => $report,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'report' => $report,
        ]);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        $filename = sprintf('Laporan-Produksi-Packing-Per-Nomor-Produksi-%s.pdf', str_replace(['\\', '/', ' '], '-', $noProduksi));
        $dispositionType = $request->boolean('preview_pdf') ? 'attachment' : 'inline';

        return $this->buildGotenbergPdfResponse($request, $html, $filename, $metrics, $footerHtml, $dispositionType);
    }

    public function preview(
        GenerateProduksiPerNomorProduksiReportRequest $request,
        ProduksiPackingPerNomorProduksiReportService $reportService,
    ): JsonResponse {
        $noProduksi = $request->noProduksi();

        try {
            $report = $reportService->fetch($noProduksi);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'no_produksi' => $noProduksi,
                'NoProduksi' => $noProduksi,
                'input_row_count' => count($report['input_rows'] ?? []),
                'output_row_count' => count($report['output_rows'] ?? []),
                'raw_column_count' => count($report['raw_columns'] ?? []),
                'source' => $report['meta']['source'] ?? null,
            ],
            'data' => $report,
        ]);
    }

    public function health(
        GenerateProduksiPerNomorProduksiReportRequest $request,
        ProduksiPackingPerNomorProduksiReportService $reportService,
    ): JsonResponse {
        $noProduksi = $request->noProduksi();

        try {
            $result = $reportService->healthCheck($noProduksi);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapProduksiPacking valid.'
                : 'Struktur output SPWps_LapProduksiPacking berubah.',
            'meta' => [
                'no_produksi' => $noProduksi,
                'NoProduksi' => $noProduksi,
            ],
            'health' => $result,
        ]);
    }
}
