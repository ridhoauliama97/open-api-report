<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStokOpnameStDetailKdReportRequest;
use App\Services\PdfGenerator;
use App\Services\StokOpnameStDetailKdReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StokOpnameStDetailKdController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.stok-opname-st-detail-kd-form');
    }

    public function download(
        GenerateStokOpnameStDetailKdReportRequest $request,
        StokOpnameStDetailKdReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateStokOpnameStDetailKdReportRequest $request,
        StokOpnameStDetailKdReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateStokOpnameStDetailKdReportRequest $request,
        StokOpnameStDetailKdReportService $reportService,
    ): JsonResponse {
        $noProcKd = $request->noProcKd();

        try {
            $reportData = $reportService->buildReportData($noProcKd);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'no_proc_kd' => $noProcKd,
                'NoProcKD' => $noProcKd,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'summary' => $reportData['summary'] ?? [],
            'header' => $reportData['header'] ?? [],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateStokOpnameStDetailKdReportRequest $request,
        StokOpnameStDetailKdReportService $reportService,
    ): JsonResponse {
        $noProcKd = $request->noProcKd();

        try {
            $result = $reportService->healthCheck($noProcKd);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapStokOpnameSTDetail valid.'
                : 'Struktur output SP_LapStokOpnameSTDetail berubah.',
            'meta' => [
                'no_proc_kd' => $noProcKd,
                'NoProcKD' => $noProcKd,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateStokOpnameStDetailKdReportRequest $request,
        StokOpnameStDetailKdReportService $reportService,
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

        $noProcKd = $request->noProcKd();

        try {
            $reportData = $reportService->buildReportData($noProcKd);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.sawn-timber.stok-opname-st-detail-kd-pdf', [
            'reportData' => $reportData,
            'noProcKd' => $noProcKd,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            // 'pdf_shrink_tables_to_fit' => 1,
        ]);

        $filename = sprintf('Laporan Stok Opname ST Detail Pada KD %s.pdf', $noProcKd);
        $dispositionType = $attachment ? 'attachment' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }
}
