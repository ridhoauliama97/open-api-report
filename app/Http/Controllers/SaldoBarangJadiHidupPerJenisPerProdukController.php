<?php

namespace App\Http\Controllers;

use App\Services\PdfGenerator;
use App\Services\SaldoBarangJadiHidupPerJenisPerProdukReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class SaldoBarangJadiHidupPerJenisPerProdukController extends Controller
{
    public function index(): View
    {
        return view('reports.barang-jadi.saldo-barang-jadi-hidup-per-jenis-per-produk-form');
    }

    public function download(
        Request $request,
        SaldoBarangJadiHidupPerJenisPerProdukReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return back()->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.barang-jadi.saldo-barang-jadi-hidup-per-jenis-per-produk-pdf', [
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = 'Laporan-Saldo-Barang-Jadi-Hidup-Per-Jenis-Per-Produk.pdf';
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        Request $request,
        SaldoBarangJadiHidupPerJenisPerProdukReportService $reportService,
    ): JsonResponse {
        try {
            $reportData = $reportService->buildReportData();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_jenis' => (int) ($reportData['summary']['total_jenis'] ?? 0),
                'total_produk' => (int) ($reportData['summary']['total_produk'] ?? 0),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        SaldoBarangJadiHidupPerJenisPerProdukReportService $reportService,
    ): JsonResponse {
        try {
            $result = $reportService->healthCheck();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapBJHidupPerProduk valid.'
                : 'Struktur output SP_LapBJHidupPerProduk berubah.',
            'health' => $result,
        ]);
    }
}
