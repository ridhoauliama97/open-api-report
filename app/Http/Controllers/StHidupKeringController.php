<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStHidupKeringReportRequest;
use App\Services\PdfGenerator;
use App\Services\StHidupKeringReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StHidupKeringController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.st-hidup-kering-form');
    }

    public function previewPdf(
        GenerateStHidupKeringReportRequest $request,
        StHidupKeringReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, true);
    }

    public function download(
        GenerateStHidupKeringReportRequest $request,
        StHidupKeringReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->renderPdf($request, $reportService, $pdfGenerator, false);
    }

    private function renderPdf(
        GenerateStHidupKeringReportRequest $request,
        StHidupKeringReportService $reportService,
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

        $hari = $request->hari();
        $mode = $request->mode();

        try {
            $reportData = $reportService->buildReportData($hari, $mode);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.sawn-timber.st-hidup-kering-pdf', [
            'reportData' => $reportData,
            'hari' => $hari,
            'mode' => $mode,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-ST-Hidup-Kering-%s-%s.pdf', $hari, $mode);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename=\"%s\"', $inline ? 'inline' : 'attachment', $filename),
        ]);
    }

    public function preview(
        GenerateStHidupKeringReportRequest $request,
        StHidupKeringReportService $reportService,
    ): JsonResponse {
        $hari = $request->hari();
        $mode = $request->mode();

        try {
            $reportData = $reportService->buildReportData($hari, $mode);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'hari' => $hari,
                'mode' => $mode,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateStHidupKeringReportRequest $request,
        StHidupKeringReportService $reportService,
    ): JsonResponse {
        $hari = $request->hari();
        $mode = $request->mode();

        try {
            $result = $reportService->healthCheck($hari, $mode);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapSTHidupKering valid.'
                : 'Struktur output SP_LapSTHidupKering berubah.',
            'meta' => [
                'hari' => $hari,
                'mode' => $mode,
            ],
            'health' => $result,
        ]);
    }
}

