<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateMutasiMouldingReportRequest;
use App\Services\MutasiMouldingReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MutasiMouldingController extends Controller
{
    /**
     * Display the default page for this resource.
     */
    public function index(): View
    {
        return view('reports.mutasi.moulding-form');
    }

    /**
     * Execute download logic.
     */
    public function download(
        GenerateMutasiMouldingReportRequest $request,
        MutasiMouldingReportService $mutasiMouldingReportService,
        PdfGenerator $pdfGenerator,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return back()
                ->withInput()
                ->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $mutasiMouldingReportService->fetch($startDate, $endDate);
            $subRows = $mutasiMouldingReportService->fetchSubReport($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $pdf = $pdfGenerator->render('reports.mutasi.moulding-pdf', [
            'rows' => $rows,
            'subRows' => $subRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Mutasi-Moulding-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Execute preview logic.
     */
    public function preview(
        GenerateMutasiMouldingReportRequest $request,
        MutasiMouldingReportService $mutasiMouldingReportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $mutasiMouldingReportService->fetch($startDate, $endDate);
            $subRows = $mutasiMouldingReportService->fetchSubReport($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
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

    /**
     * Execute health logic.
     */
    public function health(
        GenerateMutasiMouldingReportRequest $request,
        MutasiMouldingReportService $mutasiMouldingReportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $mutasiMouldingReportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_Mutasi_Moulding valid.'
                : 'Struktur output SP_Mutasi_Moulding berubah.',
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
    private function extractDates(GenerateMutasiMouldingReportRequest $request): array
    {
        $startDate = $request->input('start_date', $request->input('TglAwal'));
        $endDate = $request->input('end_date', $request->input('TglAkhir'));

        return [
            (string) $startDate,
            (string) $endDate,
        ];
    }
}
