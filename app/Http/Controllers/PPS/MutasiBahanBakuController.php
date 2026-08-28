<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateMutasiBahanBakuReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\MutasiBahanBakuReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MutasiBahanBakuController extends Controller
{
    public function index(): View
    {
        return view('pps.bahan_baku.mutasi_bahan_baku.form');
    }

    public function download(
        GenerateMutasiBahanBakuReportRequest $request,
        MutasiBahanBakuReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        [$startDate, $endDate] = $request->reportDates();
        $generatedBy = $this->resolveReportGeneratedBy($request);

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

        $columnCount = count(array_keys($rows[0] ?? [])) + 1;
        if ($columnCount <= 0) {
            $columnCount = 5;
        }

        $pdf = $pdfGenerator->render('pps.bahan_baku.mutasi_bahan_baku.pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_column_count' => $columnCount,
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Mutasi-Bahan-Baku-%s.pdf', $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    public function preview(
        GenerateMutasiBahanBakuReportRequest $request,
        MutasiBahanBakuReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $request->reportDates();

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
        GenerateMutasiBahanBakuReportRequest $request,
        MutasiBahanBakuReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $request->reportDates();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_PPSLapMutasiBahanBaku valid.'
                : 'Struktur output SP_PPSLapMutasiBahanBaku berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }
}
