<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateSpkSawmillReportRequest;
use App\Services\PdfGenerator;
use App\Services\SpkSawmillReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SpkSawmillController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.spk.spk-sawmill-form', [
            'noSpk' => trim((string) $request->input('no_spk', $request->input('NoSPK', ''))),
            'idProduk' => $request->integer('id_produk', $request->integer('IdProduk')) ?: null,
        ]);
    }

    public function download(
        GenerateSpkSawmillReportRequest $request,
        SpkSawmillReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateSpkSawmillReportRequest $request,
        SpkSawmillReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateSpkSawmillReportRequest $request,
        SpkSawmillReportService $reportService,
    ): JsonResponse {
        [$noSpk, $idProduk] = $this->extractParams($request);

        try {
            $reportData = $reportService->buildReportData($noSpk, $idProduk);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'NoSPK' => $noSpk,
                'IdProduk' => $idProduk,
                'header_rows' => (int) ($reportData['summary']['header_rows'] ?? 0),
                'detail_rows' => (int) ($reportData['summary']['detail_rows'] ?? 0),
            ],
            'header_rows' => $reportData['header_rows'] ?? [],
            'detail_rows' => $reportData['detail_rows'] ?? [],
            'report_data' => $reportData,
        ]);
    }

    public function health(
        GenerateSpkSawmillReportRequest $request,
        SpkSawmillReportService $reportService,
    ): JsonResponse {
        [$noSpk, $idProduk] = $this->extractParams($request);

        try {
            $result = $reportService->healthCheck($noSpk, $idProduk);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output laporan SPK Sawmill valid.'
                : 'Struktur output laporan SPK Sawmill berubah.',
            'meta' => [
                'NoSPK' => $noSpk,
                'IdProduk' => $idProduk,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateSpkSawmillReportRequest $request,
        SpkSawmillReportService $reportService,
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

        [$noSpk, $idProduk] = $this->extractParams($request);

        try {
            $reportData = $reportService->buildReportData($noSpk, $idProduk);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.spk.spk-sawmill-pdf', [
            'noSpk' => $noSpk,
            'idProduk' => $idProduk,
            'reportData' => $reportData,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-SPK-Sawmill-%s-%s.pdf', preg_replace('/[^A-Za-z0-9._-]+/', '-', $noSpk), $idProduk);
        $dispositionType = $attachment ? 'attachment' : 'inline';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * @return array{0:string,1:int}
     */
    private function extractParams(GenerateSpkSawmillReportRequest $request): array
    {
        return [$request->noSpk(), $request->idProduk()];
    }
}
