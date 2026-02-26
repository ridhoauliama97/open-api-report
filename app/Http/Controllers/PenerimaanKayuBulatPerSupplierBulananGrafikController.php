<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest;
use App\Services\PdfGenerator;
use App\Services\PenerimaanKayuBulatPerSupplierBulananGrafikReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PenerimaanKayuBulatPerSupplierBulananGrafikController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat.penerimaan-bulanan-per-supplier-grafik-form');
    }

    public function download(
        GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest $request,
        PenerimaanKayuBulatPerSupplierBulananGrafikReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.kayu-bulat.penerimaan-bulanan-per-supplier-grafik-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Penerimaan-Kayu-Bulat-Per-Supplier-Bulanan-Grafik-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest $request,
        PenerimaanKayuBulatPerSupplierBulananGrafikReportService $reportService,
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
                'total_groups' => count($reportData['groups'] ?? []),
                'raw_row_count' => $reportData['raw_row_count'] ?? 0,
            ],
            'data' => $reportData,
        ]);
    }

    public function health(
        GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest $request,
        PenerimaanKayuBulatPerSupplierBulananGrafikReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LaPenerimaanKayuBulatBulananPerSupplier valid.'
                : 'Struktur output SP_LaPenerimaanKayuBulatBulananPerSupplier berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GeneratePenerimaanKayuBulatBulananPerSupplierReportRequest $request): array
    {
        $startDate = $request->input('start_date', $request->input('TglAwal'));
        $endDate = $request->input('end_date', $request->input('TglAkhir'));

        return [(string) $startDate, (string) $endDate];
    }
}
