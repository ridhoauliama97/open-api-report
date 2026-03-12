<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapProduktivitasSawmillRpReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapProduktivitasSawmillRpReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapProduktivitasSawmillRpController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat-rambung.rekap-produktivitas-sawmill-rp-form');
    }

    public function download(
        GenerateRekapProduktivitasSawmillRpReportRequest $request,
        RekapProduktivitasSawmillRpReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateRekapProduktivitasSawmillRpReportRequest $request,
        RekapProduktivitasSawmillRpReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateRekapProduktivitasSawmillRpReportRequest $request,
        RekapProduktivitasSawmillRpReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $reportData = $reportService->buildReportData($startDate, $endDate);
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
                'total_rows' => count($reportData['rows'] ?? []),
                'total_dates' => $reportData['summary']['total_dates'] ?? 0,
                'total_receipts' => $reportData['summary']['total_receipts'] ?? 0,
                'column_order' => array_keys($reportData['rows'][0] ?? []),
                'date_column' => $reportData['date_column'] ?? null,
                'kategori_column' => $reportData['kategori_column'] ?? null,
                'grade_column' => $reportData['grade_column'] ?? null,
                'value_column' => $reportData['value_column'] ?? null,
                'percent_column' => $reportData['percent_column'] ?? null,
                'no_penerimaan_column' => $reportData['no_penerimaan_column'] ?? null,
                'no_kayu_bulat_column' => $reportData['no_kayu_bulat_column'] ?? null,
                'supplier_column' => $reportData['supplier_column'] ?? null,
                'no_truk_column' => $reportData['no_truk_column'] ?? null,
                'jenis_kayu_column' => $reportData['jenis_kayu_column'] ?? null,
                'meja_column' => $reportData['meja_column'] ?? null,
            ],
            'summary' => $reportData['summary'] ?? [],
            'grouped_data' => $reportData['date_groups'] ?? [],
            'grand_totals' => $reportData['grand_totals'] ?? null,
            'data' => $reportData['rows'] ?? [],
            'sub_data' => $reportData['rows_sub'] ?? [],
        ]);
    }

    public function health(
        GenerateRekapProduktivitasSawmillRpReportRequest $request,
        RekapProduktivitasSawmillRpReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SPWps_LapRekapPenerimaanSawmilRp dan SPWps_LapSubRekapPenerimaanSawmilRp valid.'
                : 'Struktur output SPWps_LapRekapPenerimaanSawmilRp atau SPWps_LapSubRekapPenerimaanSawmilRp berubah.',
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
        GenerateRekapProduktivitasSawmillRpReportRequest $request,
        RekapProduktivitasSawmillRpReportService $reportService,
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
            $reportData = $reportService->buildReportData($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.kayu-bulat-rambung.rekap-produktivitas-sawmill-rp-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
            'pdf_column_count' => 7,
        ]);

        $filename = sprintf('Laporan-Rekap-Produktivitas-Sawmill-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename=\"%s\"', $dispositionType, $filename),
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateRekapProduktivitasSawmillRpReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }
}

