<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateNoParameterReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\LabelS4SHidupPerJenisKayuReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class LabelS4SHidupPerJenisKayuController extends Controller
{
    public function index(): View
    {
        return view('reports.s4s.label-s4s-hidup-per-jenis-kayu-form');
    }

    public function download(
        GenerateNoParameterReportRequest $request,
        LabelS4SHidupPerJenisKayuReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
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
            $rows = $reportService->fetch();
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.s4s.label-s4s-hidup-per-jenis-kayu-pdf', [
            'reportData' => [
                'rows' => $rows,
            ],
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

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Laporan Label S4s Hidup Per Jenis Kayu.pdf"']);
        } catch (GotenbergPdfException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()], 502);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()]);
        }
    }

    public function preview(
        GenerateNoParameterReportRequest $request,
        LabelS4SHidupPerJenisKayuReportService $reportService,
    ): JsonResponse {
        try {
            $rows = $reportService->fetch();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function previewPdf(
        GenerateNoParameterReportRequest $request,
        LabelS4SHidupPerJenisKayuReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        try {
            $rows = $reportService->fetch();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $generatedBy = $request->user() ?? auth('api')->user();

        $html = $pdfGenerator->renderHtml('reports.s4s.label-s4s-hidup-per-jenis-kayu-pdf', [
            'reportData' => [
                'rows' => $rows,
            ],
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

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Laporan Label S4s Hidup Per Jenis Kayu.pdf"']);
        } catch (GotenbergPdfException $e) {
            return response()->json(['message' => 'Gagal generate PDF via Gotenberg: '.$e->getMessage()], 502);
        }
    }
}
