<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateLembarTallyHasilSawmillReportRequest;
use App\Services\LembarTallyHasilSawmillReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class LembarTallyHasilSawmillController extends Controller
{
    public function index(): View
    {
        return view('sawn-timber.lembar-tally-hasil-sawmill-form');
    }

    public function download(
        GenerateLembarTallyHasilSawmillReportRequest $request,
        LembarTallyHasilSawmillReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateLembarTallyHasilSawmillReportRequest $request,
        LembarTallyHasilSawmillReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateLembarTallyHasilSawmillReportRequest $request,
        LembarTallyHasilSawmillReportService $reportService,
    ): JsonResponse {
        $noProduksi = $request->noProduksi();

        try {
            $rows = $reportService->fetch($noProduksi);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'no_produksi' => $noProduksi,
                'NoProduksi' => $noProduksi,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateLembarTallyHasilSawmillReportRequest $request,
        LembarTallyHasilSawmillReportService $reportService,
    ): JsonResponse {
        $noProduksi = $request->noProduksi();

        try {
            $result = $reportService->healthCheck($noProduksi);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapUpahSawmill valid.'
                : 'Struktur output SPWps_LapUpahSawmill berubah.',
            'meta' => [
                'no_produksi' => $noProduksi,
                'NoProduksi' => $noProduksi,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateLembarTallyHasilSawmillReportRequest $request,
        LembarTallyHasilSawmillReportService $reportService,
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

        $noProduksi = $request->noProduksi();

        try {
            $rows = $reportService->fetch($noProduksi);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('sawn-timber.lembar-tally-hasil-sawmill-pdf', [
            'rows' => $rows,
            'noProduksi' => $noProduksi,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Lembar-Tally-Hasil-Sawmill-%s.pdf', str_replace(['\\', '/', ' '], '-', $noProduksi));
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }
}

