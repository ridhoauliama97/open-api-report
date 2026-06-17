<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePenerimaanKayuBulatKgReportRequest;
use App\Services\PdfGenerator;
use App\Services\PenerimaanKayuBulatKgReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PenerimaanKayuBulatKgController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat-rambung.penerimaan-kayu-bulat-kg-form');
    }

    public function download(
        GeneratePenerimaanKayuBulatKgReportRequest $request,
        PenerimaanKayuBulatKgReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GeneratePenerimaanKayuBulatKgReportRequest $request,
        PenerimaanKayuBulatKgReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GeneratePenerimaanKayuBulatKgReportRequest $request,
        PenerimaanKayuBulatKgReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->fetch($request->noKayuBulat());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'NoKayuBulat' => $request->noKayuBulat(),
                'no_kayu_bulat' => $request->noKayuBulat(),
                'total_rows' => count($reportData['rows'] ?? []),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'data' => $reportData['rows'] ?? [],
            'report_data' => $reportData,
        ]);
    }

    public function health(
        GeneratePenerimaanKayuBulatKgReportRequest $request,
        PenerimaanKayuBulatKgReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck($request->noKayuBulat());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_PenKBInTon_KG valid.'
                : 'Struktur output SP_PenKBInTon_KG berubah.',
            'meta' => [
                'NoKayuBulat' => $request->noKayuBulat(),
                'no_kayu_bulat' => $request->noKayuBulat(),
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GeneratePenerimaanKayuBulatKgReportRequest $request,
        PenerimaanKayuBulatKgReportService $reportService,
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

        try {
            $reportData = $reportService->fetch($request->noKayuBulat());
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.kayu-bulat-rambung.penerimaan-kayu-bulat-kg-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Penerimaan-Kayu-Bulat-Int-KG-%s.pdf', $request->noKayuBulat());
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }
}
