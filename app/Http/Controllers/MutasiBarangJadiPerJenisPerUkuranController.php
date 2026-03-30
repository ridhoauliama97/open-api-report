<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest;
use App\Services\MutasiBarangJadiPerJenisPerUkuranReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MutasiBarangJadiPerJenisPerUkuranController extends Controller
{
    public function index(): View
    {
        return view('reports.barang-jadi.mutasi-barang-jadi-per-jenis-per-ukuran-form');
    }

    public function download(
        GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest $request,
        MutasiBarangJadiPerJenisPerUkuranReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest $request,
        MutasiBarangJadiPerJenisPerUkuranReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
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

        $pdf = $pdfGenerator->render('reports.barang-jadi.mutasi-barang-jadi-per-jenis-per-ukuran-pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf(
            'Laporan-Mutasi-Barang-Jadi-Per-Jenis-Per-Ukuran-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateMutasiBarangJadiPerJenisPerUkuranReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}
