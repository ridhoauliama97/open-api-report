<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateRekapPenjualanPerProdukReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\RekapPenjualanEksporPerProdukPerBuyerReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapPenjualanEksporPerProdukPerBuyerController extends Controller
{
    public function index(GenerateRekapPenjualanPerProdukReportRequest $request): View
    {
        return view('reports.penjualan-kayu.rekap-penjualan-ekspor-per-produk-per-buyer-form', [
            'startDate' => $request->startDate() !== '' ? $request->startDate() : now()->startOfMonth()->toDateString(),
            'endDate' => $request->endDate() !== '' ? $request->endDate() : now()->endOfMonth()->toDateString(),
        ]);
    }

    public function download(
        GenerateRekapPenjualanPerProdukReportRequest $request,
        RekapPenjualanEksporPerProdukPerBuyerReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator,
            $gotenbergPdfClient, false);
    }

    public function previewPdf(
        GenerateRekapPenjualanPerProdukReportRequest $request,
        RekapPenjualanEksporPerProdukPerBuyerReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator,
            $gotenbergPdfClient, true);
    }

    public function preview(
        GenerateRekapPenjualanPerProdukReportRequest $request,
        RekapPenjualanEksporPerProdukPerBuyerReportService $reportService,
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
                'total_buyers' => (int) ($reportData['summary']['total_buyers'] ?? 0),
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GenerateRekapPenjualanPerProdukReportRequest $request,
        RekapPenjualanEksporPerProdukPerBuyerReportService $reportService,
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
                ? 'Struktur output SP_LapJualPerProdukPerBuyer valid.'
                : 'Struktur output SP_LapJualPerProdukPerBuyer berubah.',
            'meta' => [
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateRekapPenjualanPerProdukReportRequest $request,
        RekapPenjualanEksporPerProdukPerBuyerReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
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

        $html = $pdfGenerator->renderHtml('reports.penjualan-kayu.rekap-penjualan-ekspor-per-produk-per-buyer-pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $paperMetrics = $pdfGenerator->paperMetrics('a4');

        $generatedByName = $generatedBy->name ?? $generatedBy->Username ?? 'sistem';
        $generatedAtText = now()->locale('id')->translatedFormat('d-M-y H:i');

        try {
            $footerHtml = view('reports.partials.gotenberg-footer', [
                'generatedByName' => $generatedByName,
                'generatedAtText' => $generatedAtText,
            ])->render();

            $pdf = $gotenbergPdfClient->convertHtml($html, $paperMetrics, $footerHtml);

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Laporan Rekap Penjualan Ekspor Per Produk Per Buyer"']);
        } catch (GotenbergPdfException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()], 502);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()]);
        }
    }
}
