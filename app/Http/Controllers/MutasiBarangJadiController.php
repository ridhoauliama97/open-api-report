<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateMutasiBarangJadiReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\MutasiBarangJadiReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class MutasiBarangJadiController extends Controller
{
    /**
     * Display the default page for this resource.
     */
    public function index(): View
    {
        return view('reports.mutasi.barang-jadi-form');
    }

    /**
     * Execute download logic.
     */
    public function download(
        GenerateMutasiBarangJadiReportRequest $request,
        MutasiBarangJadiReportService $mutasiBarangJadiReportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
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

        $html = $pdfGenerator->renderHtml('reports.mutasi.barang-jadi-pdf', [
            'rows' => $rows,
            'subRows' => $subRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'rows' => $rows,
            'subRows' => $subRows,
        ]);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy->name ?? $generatedBy->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        try {
            $pdfBytes = $gotenbergPdfClient->convertHtml($html, $metrics, $footerHtml);
        } catch (GotenbergPdfException $exception) {
            return $this->gotenbergFailureResponse($request, $exception->getMessage());
        }

        $filename = sprintf('Laporan-Mutasi-Barang-Jadi-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateMutasiBarangJadiReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
    }

    /**
     * Execute preview logic.
     */
    public function preview(
        GenerateMutasiBarangJadiReportRequest $request,
        MutasiBarangJadiReportService $mutasiBarangJadiReportService,
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
                    'Jenis',
                    'Awal',
                    'Masuk',
                    'AdjOutput',
                    'BSOutput',
                    'AdjInput',
                    'BSInput',
                    'Keluar',
                    'Jual',
                    'MLDInput',
                    'LMTInput',
                    'CCAInput',
                    'SANDInput',
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
        GenerateMutasiBarangJadiReportRequest $request,
        MutasiBarangJadiReportService $mutasiBarangJadiReportService,
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
                ? 'Struktur output SP_Mutasi_BarangJadi valid.'
                : 'Struktur output SP_Mutasi_BarangJadi berubah.',
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
    private function extractDates(GenerateMutasiBarangJadiReportRequest $request): array
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
    private function fetchRows(MutasiBarangJadiReportService $mutasiBarangJadiReportService, string $startDate, string $endDate): array
    {
        return $mutasiBarangJadiReportService->fetch($startDate, $endDate);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSubRows(MutasiBarangJadiReportService $mutasiBarangJadiReportService, string $startDate, string $endDate): array
    {
        return $mutasiBarangJadiReportService->fetchSubReport($startDate, $endDate);
    }
}
