<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateMutasiBarangJadiPpsReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\MutasiBarangJadiPpsReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MutasiBarangJadiPpsController extends Controller
{
    /**
     * Display the default page for this resource.
     */
    public function index(): View
    {
        return view('pps.barang_jadi.mutasi_barang_jadi.form');
    }

    /**
     * Execute download logic.
     */
    public function download(
        GenerateMutasiBarangJadiPpsReportRequest $request,
        MutasiBarangJadiPpsReportService $mutasiBarangJadiReportService,
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
            $rows = $this->fetchRows($mutasiBarangJadiReportService, $startDate, $endDate);
            $subRows = $this->fetchSubRows($mutasiBarangJadiReportService, $startDate, $endDate);
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

        $pdf = $pdfGenerator->render('pps.barang_jadi.mutasi_barang_jadi.pdf', [
            'rows' => $rows,
            'subRows' => $subRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Mutasi-Barang-Jadi-PPS-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $request->boolean('preview_pdf') ? 'inline' : 'attachment';
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * Execute preview logic.
     */
    public function preview(
        GenerateMutasiBarangJadiPpsReportRequest $request,
        MutasiBarangJadiPpsReportService $mutasiBarangJadiReportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $this->fetchRows($mutasiBarangJadiReportService, $startDate, $endDate);
            $subRows = $this->fetchSubRows($mutasiBarangJadiReportService, $startDate, $endDate);
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
                'column_order' => [
                    'NamaBJ',
                    'Awal',
                    'PackOutput',
                    'InjectOutput',
                    'BSUOutput',
                    'ReturOutput',
                    'Masuk',
                    'BSUInput',
                    'BSortInput',
                    'BJJual',
                    'Keluar',
                    'Akhir',
                ],
            ],
            'data' => $rows,
            'sub_data' => $subRows,
        ]);
    }

    /**
     * Execute health logic.
     */
    public function health(
        GenerateMutasiBarangJadiPpsReportRequest $request,
        MutasiBarangJadiPpsReportService $mutasiBarangJadiReportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $mutasiBarangJadiReportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_PPSLapMutasiBarangJadi valid.'
                : 'Struktur output SP_PPSLapMutasiBarangJadi berubah.',
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
    private function extractDates(GenerateMutasiBarangJadiPpsReportRequest $request): array
    {
        $startDate = $request->input('start_date', $request->input('TglAwal'));
        $endDate = $request->input('end_date', $request->input('TglAkhir'));

        return [
            (string) $startDate,
            (string) $endDate,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(MutasiBarangJadiPpsReportService $mutasiBarangJadiReportService, string $startDate, string $endDate): array
    {
        return $mutasiBarangJadiReportService->fetch($startDate, $endDate);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSubRows(MutasiBarangJadiPpsReportService $mutasiBarangJadiReportService, string $startDate, string $endDate): array
    {
        return $mutasiBarangJadiReportService->fetchSubReport($startDate, $endDate);
    }
}
