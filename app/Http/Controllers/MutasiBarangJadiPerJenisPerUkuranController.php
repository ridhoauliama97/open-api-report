<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGotenbergPdfResponses;
use App\Http\Requests\GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\MutasiBarangJadiPerJenisPerUkuranReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MutasiBarangJadiPerJenisPerUkuranController extends Controller
{
    use BuildsGotenbergPdfResponses;

    public function index(): View
    {
        return view('reports.barang-jadi.mutasi-barang-jadi-per-jenis-per-ukuran-form');
    }

    public function download(
        GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest $request,
        MutasiBarangJadiPerJenisPerUkuranReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient, false);
    }

    public function previewPdf(
        GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest $request,
        MutasiBarangJadiPerJenisPerUkuranReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, $gotenbergPdfClient, true);
    }

    public function preview(
        GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest $request,
        MutasiBarangJadiPerJenisPerUkuranReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $reportService->fetch($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
                'orientation' => 'portrait',
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest $request,
        MutasiBarangJadiPerJenisPerUkuranReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapMutasiBJPerJenisPerUkuran valid.'
                : 'Struktur output SP_LapMutasiBJPerJenisPerUkuran berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest $request,
        MutasiBarangJadiPerJenisPerUkuranReportService $reportService,
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

        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $reportService->fetch($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $html = $pdfGenerator->renderHtml('reports.barang-jadi.mutasi-barang-jadi-per-jenis-per-ukuran-pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'rows' => $rows,
            'pdf_orientation' => 'portrait',
        ]);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        $filename = sprintf(
            'Laporan-Mutasi-Barang-Jadi-Per-Jenis-Per-Ukuran-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );
        $dispositionType = $attachment ? 'attachment' : 'inline';

        return $this->buildGotenbergPdfResponse($request, $html, $filename, $metrics, $footerHtml, $dispositionType);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
