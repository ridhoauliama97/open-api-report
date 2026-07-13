<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateSuratJalanReportRequest;
use App\Services\PdfGenerator;
use App\Services\SuratJalanReportService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class SuratJalanController extends Controller
{
    public function preview(
        GenerateSuratJalanReportRequest $request,
        SuratJalanReportService $reportService,
    ): JsonResponse {
        $noJual = $request->noJual();

        try {
            $reportData = $reportService->buildReportData($noJual);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview surat jalan berhasil diambil.',
            'meta' => [
                'no_jual' => $noJual,
                'NoJual' => $noJual,
                'no_surat_jalan' => (string) ($reportData['header']['no_surat_jalan'] ?? $noJual),
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_pcs' => (int) ($reportData['summary']['total_pcs'] ?? 0),
                'total_m3' => (float) ($reportData['summary']['total_m3'] ?? 0.0),
                'total_ton' => (float) ($reportData['summary']['total_ton'] ?? 0.0),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
            ],
            'data' => $reportData['rows'] ?? [],
            'report_data' => $reportData,
        ]);
    }

    public function download(
        GenerateSuratJalanReportRequest $request,
        SuratJalanReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return back()
                ->withInput()
                ->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak surat jalan.']);
        }

        $noJual = $request->noJual();

        try {
            $reportData = $reportService->buildReportData($noJual);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.penjualan.surat-jalan-pdf', [
            'reportData' => $reportData,
            'noJual' => $noJual,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
            'pdf_title' => 'Surat Jalan',
        ]);

        $filename = sprintf('Surat-Jalan-%s.pdf', str_replace(['/', '\\'], '-', $noJual));
        $dispositionType = $request->routeIs('reports.penjualan.surat-jalan.preview-pdf')
            || $request->expectsJson()
            ? 'attachment'
            : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function health(
        GenerateSuratJalanReportRequest $request,
        SuratJalanReportService $reportService,
    ): JsonResponse {
        $noJual = $request->noJual();

        try {
            $result = $reportService->healthCheck($noJual);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_CetakSuratjalan valid.'
                : 'Struktur output SP_CetakSuratjalan berubah.',
            'meta' => [
                'no_jual' => $noJual,
                'NoJual' => $noJual,
            ],
            'health' => $result,
        ]);
    }
}
