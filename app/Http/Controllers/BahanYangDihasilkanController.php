<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGotenbergPdfResponses;
use App\Http\Requests\GenerateBahanYangDihasilkanReportRequest;
use App\Services\BahanYangDihasilkanReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BahanYangDihasilkanController extends Controller
{
    use BuildsGotenbergPdfResponses;

    public function index(Request $request): View
    {
        return view('reports.verifikasi.bahan-yang-dihasilkan-form', [
            'reportDate' => (string) $request->input('TglAwal', now()->toDateString()),
        ]);
    }

    public function download(
        GenerateBahanYangDihasilkanReportRequest $request,
        BahanYangDihasilkanReportService $reportService,
        PdfGenerator $pdfGenerator,
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

        $reportDate = $request->reportDate();

        try {
            $reportData = $reportService->buildReportData($reportDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.verifikasi.bahan-yang-dihasilkan-pdf', [
            'reportDate' => $reportDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'reportData' => $reportData,
        ]);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        $filename = sprintf('Laporan-Bahan-Yang-Dihasilkan-%s.pdf', $reportDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'attachment' : 'inline';

        return $this->buildGotenbergPdfResponse($request, $html, $filename, $metrics, $footerHtml, $dispositionType);
    }

    public function preview(
        GenerateBahanYangDihasilkanReportRequest $request,
        BahanYangDihasilkanReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();

        try {
            $reportData = $reportService->buildReportData($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'date' => $reportDate,
                'TglAwal' => $reportDate,
                'row_count' => (int) ($reportData['summary']['row_count'] ?? 0),
                'category_count' => (int) ($reportData['summary']['category_count'] ?? 0),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateBahanYangDihasilkanReportRequest $request,
        BahanYangDihasilkanReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();

        try {
            $result = $reportService->healthCheck($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapBahanYangDihasilkan valid.'
                : 'Struktur output SPWps_LapBahanYangDihasilkan berubah.',
            'meta' => [
                'date' => $reportDate,
                'TglAwal' => $reportDate,
            ],
            'health' => $result,
        ]);
    }
}
