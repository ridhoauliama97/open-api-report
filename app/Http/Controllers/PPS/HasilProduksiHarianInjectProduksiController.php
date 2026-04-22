<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateHasilProduksiHarianInjectProduksiReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\HasilProduksiHarianInjectProduksiReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class HasilProduksiHarianInjectProduksiController extends Controller
{
    public function index(HasilProduksiHarianInjectProduksiReportService $reportService): View
    {
        return view('pps.inject.inject_produksi.form', [
            'recentNoProduksi' => $reportService->recentNoProduksi(),
        ]);
    }

    public function download(
        GenerateHasilProduksiHarianInjectProduksiReportRequest $request,
        HasilProduksiHarianInjectProduksiReportService $reportService,
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

        $pdf = $pdfGenerator->render('pps.inject.inject_produksi.pdf', [
            'report' => $report,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Harian-Hasil-Inject-Produksi-%s.pdf', str_replace(['\\', '/', ' '], '-', $noProduksi));
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateHasilProduksiHarianInjectProduksiReportRequest $request,
        HasilProduksiHarianInjectProduksiReportService $reportService,
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
                'detail_row_count' => count($report['detail_rows'] ?? []),
                'source' => $report['meta']['source'] ?? null,
                'meta' => $report['meta'] ?? [],
            ],
            'data' => $report,
        ]);
    }

    public function health(
        GenerateHasilProduksiHarianInjectProduksiReportRequest $request,
        HasilProduksiHarianInjectProduksiReportService $reportService,
    ): JsonResponse {
        $noProduksi = $request->noProduksi();

        try {
            $result = $reportService->healthCheck($noProduksi);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapHasilProduksiHarianInject valid.'
                : 'Struktur output SP_LapHasilProduksiHarianInject berubah.',
            'meta' => [
                'no_produksi' => $noProduksi,
                'NoProduksi' => $noProduksi,
            ],
            'health' => $result,
        ]);
    }
}
