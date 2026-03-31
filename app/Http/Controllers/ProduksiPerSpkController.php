<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateProduksiPerSpkReportRequest;
use App\Services\PdfGenerator;
use App\Services\ProduksiPerSpkReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ProduksiPerSpkController extends Controller
{
    public function index(GenerateProduksiPerSpkReportRequest $request): View
    {
        return view('reports.rendemen-kayu.produksi-per-spk-form', [
            'noSpk' => $request->noSpk(),
        ]);
    }

    public function download(
        GenerateProduksiPerSpkReportRequest $request,
        ProduksiPerSpkReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateProduksiPerSpkReportRequest $request,
        ProduksiPerSpkReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateProduksiPerSpkReportRequest $request,
        ProduksiPerSpkReportService $reportService,
    ): JsonResponse {
        $noSpk = $request->noSpk();

        try {
            $reportData = $reportService->buildReportData($noSpk);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'NoSPK' => $noSpk,
                'dimension_rows' => (int) ($reportData['summary']['dimension_rows'] ?? 0),
                'alive_rows' => (int) ($reportData['summary']['alive_rows'] ?? 0),
                'miss_rows' => (int) ($reportData['summary']['miss_rows'] ?? 0),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateProduksiPerSpkReportRequest $request,
        ProduksiPerSpkReportService $reportService,
    ): JsonResponse {
        $noSpk = $request->noSpk();

        try {
            $result = $reportService->healthCheck($noSpk);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapProduksiPerSPK valid.'
                : 'Struktur output SP_LapProduksiPerSPK berubah.',
            'meta' => ['NoSPK' => $noSpk],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateProduksiPerSpkReportRequest $request,
        ProduksiPerSpkReportService $reportService,
        PdfGenerator $pdfGenerator,
        bool $inline,
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

        $noSpk = $request->noSpk();

        try {
            $reportData = $reportService->buildReportData($noSpk);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.rendemen-kayu.produksi-per-spk-pdf', [
            'noSpk' => $noSpk,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf('Laporan-Produksi-Per-SPK-%s.pdf', preg_replace('/[^A-Za-z0-9._-]+/', '-', $noSpk));
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }
}
