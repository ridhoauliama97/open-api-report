<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateTotalBagusKulitRambungReportRequest;
use App\Services\PdfGenerator;
use App\Services\TotalBagusKulitRambungReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TotalBagusKulitRambungController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.sawn-timber.total-bagus-kulit-rambung-form', [
            'reportDate' => (string) $request->input('TglSawmill', now()->toDateString()),
        ]);
    }

    public function download(
        GenerateTotalBagusKulitRambungReportRequest $request,
        TotalBagusKulitRambungReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateTotalBagusKulitRambungReportRequest $request,
        TotalBagusKulitRambungReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateTotalBagusKulitRambungReportRequest $request,
        TotalBagusKulitRambungReportService $reportService,
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
                'report_date' => $reportDate,
                'TglSawmill' => $reportDate,
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_bagus' => (int) ($reportData['summary']['total_bagus'] ?? 0),
                'total_kulit' => (int) ($reportData['summary']['total_kulit'] ?? 0),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'data' => $reportData['rows'] ?? [],
            'summary' => $reportData['summary'] ?? [],
        ]);
    }

    public function health(
        GenerateTotalBagusKulitRambungReportRequest $request,
        TotalBagusKulitRambungReportService $reportService,
    ): JsonResponse {
        $reportDate = $request->reportDate();

        try {
            $result = $reportService->healthCheck($reportDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output laporan total bagus/kulit rambung valid.'
                : 'Struktur output laporan total bagus/kulit rambung berubah.',
            'meta' => [
                'report_date' => $reportDate,
                'TglSawmill' => $reportDate,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateTotalBagusKulitRambungReportRequest $request,
        TotalBagusKulitRambungReportService $reportService,
        PdfGenerator $pdfGenerator,
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

        $pdf = $pdfGenerator->render('reports.sawn-timber.total-bagus-kulit-rambung-pdf', [
            'reportData' => $reportData,
            'reportDate' => $reportDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Total-Bagus-Kulit-Rambung-%s.pdf', $reportDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $attachment ? 'attachment' : 'attachment', $filename),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
