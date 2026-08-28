<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateHasilProduksiHarianPackingProduksiReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\HasilProduksiHarianPackingProduksiReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class HasilProduksiHarianPackingProduksiController extends Controller
{
    public function index(HasilProduksiHarianPackingProduksiReportService $reportService): View
    {
        return view('pps.inject.packing.packing_produksi.form', [
            'recentNoPacking' => $reportService->recentNoPacking(),
        ]);
    }

    public function download(
        GenerateHasilProduksiHarianPackingProduksiReportRequest $request,
        HasilProduksiHarianPackingProduksiReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $noPacking = $request->noPacking();
        $generatedBy = $this->resolveReportGeneratedBy($request);

        try {
            $report = $reportService->fetch($noPacking);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('pps.inject.packing.packing_produksi.pdf', [
            'report' => $report,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Harian-Hasil-Packing-Produksi-%s.pdf', str_replace(['\\', '/', ' '], '-', $noPacking));
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateHasilProduksiHarianPackingProduksiReportRequest $request,
        HasilProduksiHarianPackingProduksiReportService $reportService,
    ): JsonResponse {
        $noPacking = $request->noPacking();

        try {
            $report = $reportService->fetch($noPacking);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'no_packing' => $noPacking,
                'NoPacking' => $noPacking,
                'detail_row_count' => count($report['detail_rows'] ?? []),
                'source' => $report['meta']['source'] ?? null,
                'meta' => $report['meta'] ?? [],
            ],
            'data' => $report,
        ]);
    }

    public function health(
        GenerateHasilProduksiHarianPackingProduksiReportRequest $request,
        HasilProduksiHarianPackingProduksiReportService $reportService,
    ): JsonResponse {
        $noPacking = $request->noPacking();

        try {
            $result = $reportService->healthCheck($noPacking);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapHasilProduksiHarianPacking valid.'
                : 'Struktur output SP_LapHasilProduksiHarianPacking berubah.',
            'meta' => [
                'no_packing' => $noPacking,
                'NoPacking' => $noPacking,
            ],
            'health' => $result,
        ]);
    }
}
