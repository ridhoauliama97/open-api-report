<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateMutasiS4SReportRequest;
use App\Services\MutasiS4SReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MutasiS4SController extends Controller
{
    /**
     * Display the default page for this resource.
     */
    public function index(): View
    {
        return view('reports.mutasi.s4s-form');
    }

    /**
     * Execute download logic.
     */
    public function download(
        GenerateMutasiS4SReportRequest $request,
        MutasiS4SReportService $mutasiS4SReportService,
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
            $rows = $mutasiS4SReportService->fetch($startDate, $endDate);
            $subRows = $mutasiS4SReportService->fetchSubReport($startDate, $endDate);
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

        $pdf = $pdfGenerator->render('reports.mutasi.s4s-pdf', [
            'rows' => $rows,
            'subRows' => $subRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $filename = sprintf('Laporan-Mutasi-S4S-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Execute preview logic.
     */
    public function preview(
        GenerateMutasiS4SReportRequest $request,
        MutasiS4SReportService $mutasiS4SReportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $mutasiS4SReportService->fetch($startDate, $endDate);
            $subRows = $mutasiS4SReportService->fetchSubReport($startDate, $endDate);
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
        GenerateMutasiS4SReportRequest $request,
        MutasiS4SReportService $mutasiS4SReportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $mutasiS4SReportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_Mutasi_S4S valid.'
                : 'Struktur output SP_Mutasi_S4S berubah.',
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
    private function extractDates(GenerateMutasiS4SReportRequest $request): array
    {
        $startDate = $request->input('start_date', $request->input('TglAwal'));
        $endDate = $request->input('end_date', $request->input('TglAkhir'));

        return [
            (string) $startDate,
            (string) $endDate,
        ];
    }
}

