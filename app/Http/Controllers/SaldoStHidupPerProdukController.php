<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateSaldoStHidupPerProdukReportRequest;
use App\Services\PdfGenerator;
use App\Services\SaldoStHidupPerProdukReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class SaldoStHidupPerProdukController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.saldo-st-hidup-per-produk-form');
    }

    public function download(
        GenerateSaldoStHidupPerProdukReportRequest $request,
        SaldoStHidupPerProdukReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.sawn-timber.saldo-st-hidup-per-produk-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = 'Laporan-Saldo-ST-Hidup-Per-Jenis-Per-Tebal-Per-Group-Jenis-Kayu.pdf';
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateSaldoStHidupPerProdukReportRequest $request,
        SaldoStHidupPerProdukReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];
        $firstGroup = is_array($groups[0] ?? null) ? $groups[0] : [];
        $firstProduct = is_array(($firstGroup['products'][0] ?? null)) ? $firstGroup['products'][0] : [];
        $firstRow = is_array(($firstProduct['rows'][0] ?? null)) ? $firstProduct['rows'][0] : [];

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_groups' => count($groups),
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'column_order' => array_keys($firstRow),
            ],
            'summary' => $reportData['summary'],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateSaldoStHidupPerProdukReportRequest $request,
        SaldoStHidupPerProdukReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapSTHidupPerProduk valid.'
                : 'Struktur output SPWps_LapSTHidupPerProduk berubah.',
            'meta' => [],
            'health' => $result,
        ]);
    }
}
