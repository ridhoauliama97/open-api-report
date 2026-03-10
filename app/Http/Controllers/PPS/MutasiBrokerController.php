<?php

namespace App\Http\Controllers\PPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PPS\GenerateMutasiBrokerReportRequest;
use App\Services\PdfGenerator;
use App\Services\PPS\MutasiBrokerReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MutasiBrokerController extends Controller
{
    /**
     * Display the default page for this resource.
     */
    public function index(): View
    {
        return view('pps.broker.mutasi_broker.form');
    }

    /**
     * Execute download logic.
     */
    public function download(
        GenerateMutasiBrokerReportRequest $request,
        MutasiBrokerReportService $reportService,
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

        $pdf = $pdfGenerator->render('pps.broker.mutasi_broker.pdf', [
            'rows' => $rows,
            'subRows' => $subRows,
            'wasteRows' => $wasteRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Mutasi-Broker-PPS-%s-sd-%s.pdf', $startDate, $endDate);
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
        GenerateMutasiBrokerReportRequest $request,
        MutasiBrokerReportService $reportService,
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
                    'Jenis',
                    'BeratAwal',
                    'OutputBSU',
                    'OutputBroker',
                    'BeratMasuk',
                    'InputBSU',
                    'InputBroker',
                    'InputInject',
                    'InputMixer',
                    'BeratKeluar',
                    'BeratAkhir',
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
        GenerateMutasiBrokerReportRequest $request,
        MutasiBrokerReportService $reportService,
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
                ? 'Struktur output SP_PPSLapMutasiBroker valid.'
                : 'Struktur output SP_PPSLapMutasiBroker berubah.',
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
    private function extractDates(GenerateMutasiBrokerReportRequest $request): array
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
    private function fetchRows(MutasiBrokerReportService $reportService, string $startDate, string $endDate): array
    {
        return $reportService->fetch($startDate, $endDate);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSubRows(MutasiBrokerReportService $reportService, string $startDate, string $endDate): array
    {
        return $reportService->fetchSubReport($startDate, $endDate);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchWasteRows(MutasiBrokerReportService $reportService, string $startDate, string $endDate): array
    {
        return $reportService->fetchWasteReport($startDate, $endDate);
    }
}
