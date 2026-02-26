<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePenerimaanKayuBulatPerSupplierGroupReportRequest;
use App\Services\PdfGenerator;
use App\Services\PenerimaanKayuBulatPerSupplierGroupReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PenerimaanKayuBulatPerSupplierGroupController extends Controller
{
    public function index(): View
    {
        return view('reports.kayu-bulat.penerimaan-per-supplier-group-form');
    }

    public function download(
        GeneratePenerimaanKayuBulatPerSupplierGroupReportRequest $request,
        PenerimaanKayuBulatPerSupplierGroupReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.kayu-bulat.penerimaan-per-supplier-group-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Penerimaan-Kayu-Bulat-Per-Supplier-Group-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GeneratePenerimaanKayuBulatPerSupplierGroupReportRequest $request,
        PenerimaanKayuBulatPerSupplierGroupReportService $reportService,
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
                'total_suppliers' => (int) ($reportData['summary']['total_suppliers'] ?? 0),
                'column_order' => array_keys($reportData['rows'][0] ?? []),
                'group_names' => $reportData['group_names'] ?? [],
            ],
            'summary' => $reportData['summary'] ?? [],
            'data' => $reportData,
        ]);
    }

    public function health(
        GeneratePenerimaanKayuBulatPerSupplierGroupReportRequest $request,
        PenerimaanKayuBulatPerSupplierGroupReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapPenerimaanKBPerSupplierGroup valid.'
                : 'Struktur output SP_LapPenerimaanKBPerSupplierGroup berubah.',
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
    private function extractDates(GeneratePenerimaanKayuBulatPerSupplierGroupReportRequest $request): array
    {
        $startDate = $request->input('start_date', $request->input('TglAwal'));
        $endDate = $request->input('end_date', $request->input('TglAkhir'));

        return [(string) $startDate, (string) $endDate];
    }
}
