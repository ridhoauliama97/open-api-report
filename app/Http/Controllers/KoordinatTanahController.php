<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateKoordinatTanahReportRequest;
use App\Services\KoordinatTanahReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class KoordinatTanahController extends Controller
{
    public function index(GenerateKoordinatTanahReportRequest $request): View
    {
        return view('reports.penjualan-kayu.koordinat-tanah-form', [
            'noSpk' => $request->noSpk(),
        ]);
    }

    public function download(
        GenerateKoordinatTanahReportRequest $request,
        KoordinatTanahReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateKoordinatTanahReportRequest $request,
        KoordinatTanahReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateKoordinatTanahReportRequest $request,
        KoordinatTanahReportService $reportService,
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
                'product_rows' => (int) ($reportData['summary']['product_rows'] ?? 0),
                'land_rows' => (int) ($reportData['summary']['land_rows'] ?? 0),
                'gps_percentage_rows' => (int) ($reportData['summary']['gps_percentage_rows'] ?? 0),
                'raw_rows' => (int) ($reportData['summary']['raw_rows'] ?? 0),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateKoordinatTanahReportRequest $request,
        KoordinatTanahReportService $reportService,
    ): JsonResponse {
        $noSpk = $request->noSpk();

        try {
            $result = $reportService->healthCheck($noSpk);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_PrintCariKoordinatTanah valid.'
                : 'Struktur output SP_PrintCariKoordinatTanah berubah.',
            'meta' => ['NoSPK' => $noSpk],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateKoordinatTanahReportRequest $request,
        KoordinatTanahReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.penjualan-kayu.koordinat-tanah-pdf', [
            'noSpk' => $noSpk,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf('Laporan-Koordinat-Tanah-%s.pdf', preg_replace('/[^A-Za-z0-9._-]+/', '-', $noSpk));
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }
}
