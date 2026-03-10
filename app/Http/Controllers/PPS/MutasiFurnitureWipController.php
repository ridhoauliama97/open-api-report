<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateMutasiFurnitureWipReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\MutasiFurnitureWipReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MutasiFurnitureWipController extends Controller
{
    /**
     * Display the default page for this resource.
     */
    public function index(): View
    {
        return view('pps.furniture_wip.mutasi_furniture_wip.form');
    }

    /**
     * Execute download logic.
     */
    public function download(
        GenerateMutasiFurnitureWipReportRequest $request,
        MutasiFurnitureWipReportService $reportService,
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
            $rows = $this->fetchRows($reportService, $startDate, $endDate);
            $subRows = $this->fetchSubRows($reportService, $startDate, $endDate);
            $wasteRows = $this->fetchWasteRows($reportService, $startDate, $endDate);
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

        $pdf = $pdfGenerator->render('pps.furniture_wip.mutasi_furniture_wip.pdf', [
            'rows' => $rows,
            'subRows' => $subRows,
            'wasteRows' => $wasteRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Mutasi-Furniture-WIP-PPS-%s-sd-%s.pdf', $startDate, $endDate);
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
        GenerateMutasiFurnitureWipReportRequest $request,
        MutasiFurnitureWipReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $this->fetchRows($reportService, $startDate, $endDate);
            $subRows = $this->fetchSubRows($reportService, $startDate, $endDate);
            $wasteRows = $this->fetchWasteRows($reportService, $startDate, $endDate);
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
                'total_waste_rows' => count($wasteRows),
                'column_order' => [
                    'Nama',
                    'Awal',
                    'OutputInjc',
                    'OutHStamp',
                    'OutputPKunci',
                    'OutputSpan',
                    'Masuk',
                    'InputBJSort',
                    'InputHStamp',
                    'InputPack',
                    'InputPKunci',
                    'InputSpaner',
                    'InputBSU',
                    'Keluar',
                    'Akhir',
                ],
            ],
            'data' => $rows,
            'sub_data' => $subRows,
            'waste_data' => $wasteRows,
        ]);
    }

    /**
     * Execute health logic.
     */
    public function health(
        GenerateMutasiFurnitureWipReportRequest $request,
        MutasiFurnitureWipReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_PPSLapMutasiFurnitureWIP valid.'
                : 'Struktur output SP_PPSLapMutasiFurnitureWIP berubah.',
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
    private function extractDates(GenerateMutasiFurnitureWipReportRequest $request): array
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
    private function fetchRows(MutasiFurnitureWipReportService $reportService, string $startDate, string $endDate): array
    {
        return $reportService->fetch($startDate, $endDate);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSubRows(MutasiFurnitureWipReportService $reportService, string $startDate, string $endDate): array
    {
        return $reportService->fetchSubReport($startDate, $endDate);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchWasteRows(MutasiFurnitureWipReportService $reportService, string $startDate, string $endDate): array
    {
        return $reportService->fetchWasteReport($startDate, $endDate);
    }
}
