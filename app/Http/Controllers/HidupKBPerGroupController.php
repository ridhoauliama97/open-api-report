<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateHidupKBPerGroupReportRequest;
use App\Services\HidupKBPerGroupReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class HidupKBPerGroupController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat.hidup-per-group-form');
    }

    public function download(
        GenerateHidupKBPerGroupReportRequest $request,
        HidupKBPerGroupReportService $reportService,
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

        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.kayu-bulat.hidup-per-group-pdf', [
            'rows' => $reportData['rows'],
            'summary' => $reportData['summary'],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = 'laporan-hidup-kb-per-group.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateHidupKBPerGroupReportRequest $request,
        HidupKBPerGroupReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => count($reportData['rows'] ?? []),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'summary' => $reportData['summary'],
            'data' => $reportData['rows'],
        ]);
    }

    public function health(
        GenerateHidupKBPerGroupReportRequest $request,
        HidupKBPerGroupReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output sp_LapHidupKBPerGroup valid.'
                : 'Struktur output sp_LapHidupKBPerGroup berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
