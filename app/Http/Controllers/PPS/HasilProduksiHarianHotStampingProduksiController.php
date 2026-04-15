<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateHasilProduksiHarianHotStampingProduksiReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\HasilProduksiHarianHotStampingProduksiReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class HasilProduksiHarianHotStampingProduksiController extends Controller
{
    public function index(HasilProduksiHarianHotStampingProduksiReportService $reportService): View
    {
        return view('pps.inject.hot_stamping.hot_stamping_produksi.form', [
            'recentNoProduksi' => $reportService->recentNoProduksi(),
        ]);
    }

    public function download(
        GenerateHasilProduksiHarianHotStampingProduksiReportRequest $request,
        HasilProduksiHarianHotStampingProduksiReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $noProduksi = $request->noProduksi();
        $generatedBy = $this->resolveGeneratedBy($request);

        try {
            $report = $reportService->fetch($noProduksi);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('pps.inject.hot_stamping.hot_stamping_produksi.pdf', [
            'report' => $report,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Harian-Hasil-Hot-Stamping-Produksi-%s.pdf', str_replace(['\\', '/', ' '], '-', $noProduksi));
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateHasilProduksiHarianHotStampingProduksiReportRequest $request,
        HasilProduksiHarianHotStampingProduksiReportService $reportService,
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
        GenerateHasilProduksiHarianHotStampingProduksiReportRequest $request,
        HasilProduksiHarianHotStampingProduksiReportService $reportService,
    ): JsonResponse {
        $noProduksi = $request->noProduksi();

        try {
            $result = $reportService->healthCheck($noProduksi);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapHasilProduksiHarianHotStamping valid.'
                : 'Struktur output SP_LapHasilProduksiHarianHotStamping berubah.',
            'meta' => [
                'no_produksi' => $noProduksi,
                'NoProduksi' => $noProduksi,
            ],
            'health' => $result,
        ]);
    }

    private function resolveGeneratedBy(GenerateHasilProduksiHarianHotStampingProduksiReportRequest $request): object
    {
        $webUser = $request->user() ?? auth('api')->user();
        if ($webUser !== null) {
            $name = (string) ($webUser->name ?? $webUser->Username ?? 'sistem');

            return (object) ['name' => $name];
        }

        $claims = $request->attributes->get('report_token_claims');
        if (is_array($claims)) {
            $name = (string) ($claims['name'] ?? $claims['username'] ?? 'api');

            return (object) ['name' => $name];
        }

        return (object) ['name' => 'sistem'];
    }
}
