<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateRangkumanBongkarSusunReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\RangkumanBongkarSusunReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RangkumanBongkarSusunController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.verifikasi.rangkuman-bongkar-susun-form', [
            'reportDate' => (string) $request->input('TglAwal', now()->toDateString()),
        ]);
    }

    public function download(
        GenerateRangkumanBongkarSusunReportRequest $request,
        RangkumanBongkarSusunReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
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

        $html = $pdfGenerator->renderHtml('reports.verifikasi.rangkuman-bongkar-susun-pdf', [
            'reportDate' => $reportDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $paperMetrics = $pdfGenerator->paperMetrics('a4');

        $generatedByName = $generatedBy->name ?? $generatedBy->Username ?? 'sistem';
        $generatedAtText = now()->locale('id')->translatedFormat('d-M-y H:i');

        try {
            $pdf = $gotenbergPdfClient->convertHtml($html, $paperMetrics, 'reports.partials.gotenberg-footer', [
                'generatedByName' => $generatedByName,
                'generatedAtText' => $generatedAtText,
            ]);

            return $pdf;
        } catch (GotenbergPdfException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()], 502);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()]);
        }
    }

    public function preview(
        GenerateRangkumanBongkarSusunReportRequest $request,
        RangkumanBongkarSusunReportService $reportService,
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
        GenerateRangkumanBongkarSusunReportRequest $request,
        RangkumanBongkarSusunReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();

        try {
            $result = $reportService->healthCheck($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapRangkumanBongkarSusun valid.'
                : 'Struktur output SPWps_LapRangkumanBongkarSusun berubah.',
            'meta' => [
                'date' => $reportDate,
                'TglAwal' => $reportDate,
            ],
            'health' => $result,
        ]);
    }
}
