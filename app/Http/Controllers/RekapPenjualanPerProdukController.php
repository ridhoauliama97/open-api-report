<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapPenjualanPerProdukReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapPenjualanPerProdukReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapPenjualanPerProdukController extends Controller
{
    public function index(GenerateRekapPenjualanPerProdukReportRequest $request): View
    {
        return view('reports.penjualan-kayu.rekap-penjualan-per-produk-form', [
            'startDate' => $request->startDate() !== '' ? $request->startDate() : now()->startOfMonth()->toDateString(),
            'endDate' => $request->endDate() !== '' ? $request->endDate() : now()->endOfMonth()->toDateString(),
        ]);
    }

    public function download(
        GenerateRekapPenjualanPerProdukReportRequest $request,
        RekapPenjualanPerProdukReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateRekapPenjualanPerProdukReportRequest $request,
        RekapPenjualanPerProdukReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateRekapPenjualanPerProdukReportRequest $request,
        RekapPenjualanPerProdukReportService $reportService,
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
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => (int) ($reportData['summary']['total_rows'] ?? 0),
                'total_products' => (int) ($reportData['summary']['total_products'] ?? 0),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateRekapPenjualanPerProdukReportRequest $request,
        RekapPenjualanPerProdukReportService $reportService,
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
                ? 'Struktur output SP_LapJualPerProduk valid.'
                : 'Struktur output SP_LapJualPerProduk berubah.',
            'meta' => [
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateRekapPenjualanPerProdukReportRequest $request,
        RekapPenjualanPerProdukReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.penjualan-kayu.rekap-penjualan-per-produk-pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf(
            'Laporan-Rekap-Penjualan-Per-Produk-%s-s_d-%s.pdf',
            str_replace('-', '-', $startDate),
            str_replace('-', '-', $endDate)
        );
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }
}
