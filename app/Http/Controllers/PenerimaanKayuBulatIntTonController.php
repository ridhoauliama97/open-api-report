<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGotenbergPdfResponses;
use App\Http\Requests\GeneratePenerimaanKayuBulatIntTonReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\PenerimaanKayuBulatIntTonReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PenerimaanKayuBulatIntTonController extends Controller
{
    use BuildsGotenbergPdfResponses;

    public function index(): View
    {
        return view('reports.kayu-bulat.penerimaan-kayu-bulat-int-ton-form');
    }

    public function download(
        GeneratePenerimaanKayuBulatIntTonReportRequest $request,
        PenerimaanKayuBulatIntTonReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient, false);
    }

    public function previewPdf(
        GeneratePenerimaanKayuBulatIntTonReportRequest $request,
        PenerimaanKayuBulatIntTonReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient, true);
    }

    public function preview(
        GeneratePenerimaanKayuBulatIntTonReportRequest $request,
        PenerimaanKayuBulatIntTonReportService $reportService,
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
        GeneratePenerimaanKayuBulatIntTonReportRequest $request,
        PenerimaanKayuBulatIntTonReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck($request->noKayuBulat());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_PenKBInTon valid.'
                : 'Struktur output SP_PenKBInTon berubah.',
            'meta' => [
                'NoKayuBulat' => $request->noKayuBulat(),
                'no_kayu_bulat' => $request->noKayuBulat(),
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GeneratePenerimaanKayuBulatIntTonReportRequest $request,
        PenerimaanKayuBulatIntTonReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
        bool $attachment,
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

        $pdfData = [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ];

        $html = $pdfGenerator->renderHtml('reports.kayu-bulat.penerimaan-kayu-bulat-int-ton-pdf', $pdfData);

        $metrics = $pdfGenerator->paperMetrics($pdfData);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        $filename = sprintf('Laporan-Penerimaan-Kayu-Bulat-Int-Ton-%s.pdf', $request->noKayuBulat());
        $dispositionType = $attachment ? 'attachment' : 'inline';

        return $this->buildGotenbergPdfResponse($request, $html, $filename, $metrics, $footerHtml, $dispositionType);
    }
}
