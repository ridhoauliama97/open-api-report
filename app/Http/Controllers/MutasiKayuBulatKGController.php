<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateMutasiKayuBulatKGReportRequest;
use App\Services\MutasiKayuBulatKGReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MutasiKayuBulatKGController extends Controller
{
    public function index(): View
    {
        return view('reports.mutasi.kayu-bulat-kg-form');
    }

    public function download(
        GenerateMutasiKayuBulatKGReportRequest $request,
        MutasiKayuBulatKGReportService $mutasiKayuBulatKGReportService,
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
            $rows = $mutasiKayuBulatKGReportService->fetch($startDate, $endDate);
            $subRows = $mutasiKayuBulatKGReportService->fetchSubReport($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.mutasi.kayu-bulat-kg-pdf', [
            'rows' => $rows,
            'subRows' => $subRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Mutasi-Kayu-Bulat-Timbang-KG-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateMutasiKayuBulatKGReportRequest $request,
        MutasiKayuBulatKGReportService $mutasiKayuBulatKGReportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $mutasiKayuBulatKGReportService->fetch($startDate, $endDate);
            $subRows = $mutasiKayuBulatKGReportService->fetchSubReport($startDate, $endDate);
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
                'total_sub_rows' => count($subRows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
            'sub_data' => $subRows,
        ]);
    }

    public function health(
        GenerateMutasiKayuBulatKGReportRequest $request,
        MutasiKayuBulatKGReportService $mutasiKayuBulatKGReportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $mutasiKayuBulatKGReportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_Mutasi_KayuBulatKG valid.'
                : 'Struktur output SP_Mutasi_KayuBulatKG berubah.',
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
    private function extractDates(GenerateMutasiKayuBulatKGReportRequest $request): array
    {
        $startDate = $request->input('start_date', $request->input('TglAwal'));
        $endDate = $request->input('end_date', $request->input('TglAkhir'));

        return [(string) $startDate, (string) $endDate];
    }
}

