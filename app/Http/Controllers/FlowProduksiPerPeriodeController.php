<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGotenbergPdfResponses;
use App\Http\Requests\GenerateDateRangeReportRequest;
use App\Services\FlowProduksiPerPeriodeReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class FlowProduksiPerPeriodeController extends Controller
{
    use BuildsGotenbergPdfResponses;

    public function index(Request $request): View
    {
        return view('reports.management.flow-produksi-per-periode-form', [
            'startDate' => (string) $request->input('TglAwal', now()->startOfMonth()->toDateString()),
            'endDate' => (string) $request->input('TglAkhir', now()->toDateString()),
        ]);
    }

    public function download(
        GenerateDateRangeReportRequest $request,
        FlowProduksiPerPeriodeReportService $reportService,
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

        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.management.flow-produksi-per-periode-pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $paperMetrics = $pdfGenerator->paperMetrics('a4');

        $generatedByName = $generatedBy->name ?? $generatedBy->Username ?? 'sistem';
        $generatedAtText = now()->locale('id')->translatedFormat('d-M-y H:i');

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])->render();

        return $this->buildGotenbergPdfResponse($request, $html, 'Laporan Flow Produksi Per Periode', $paperMetrics, $footerHtml, 'attachment');
    }

    public function preview(
        GenerateDateRangeReportRequest $request,
        FlowProduksiPerPeriodeReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'row_count' => (int) ($reportData['summary']['row_count'] ?? 0),
                'group_count' => (int) ($reportData['summary']['group_count'] ?? 0),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateDateRangeReportRequest $request,
        FlowProduksiPerPeriodeReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapFlowProduksiPerPeriode valid.'
                : 'Struktur output SPWps_LapFlowProduksiPerPeriode berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }
}
