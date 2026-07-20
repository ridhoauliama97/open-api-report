<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapRendemenRambungPerSupplierRequest;
use App\Services\PdfGenerator;
use App\Services\RekapRendemenRambungPerSupplierService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapRendemenRambungPerSupplierController extends Controller
{
    /**
     * Display the default page for this resource.
     */
    public function index(): View
    {
        return view('reports.kayu-bulat-rambung.rekap-rendemen-rambung-per-supplier-form');
    }

    /**
     * Execute download logic.
     */
    public function download(
        GenerateRekapRendemenRambungPerSupplierRequest $request,
        RekapRendemenRambungPerSupplierService $rekapRendemenRambungPerSupplierService,
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
            $rows = $this->fetchRows($rekapRendemenRambungPerSupplierService, $startDate, $endDate);
            $subRows = $this->fetchSubRows($rekapRendemenRambungPerSupplierService, $startDate, $endDate);
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

        $pdf = $pdfGenerator->render('reports.kayu-bulat-rambung.rekap-rendemen-rambung-per-supplier-pdf', [
            'rows' => $rows,
            'subRows' => $subRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Rekap-Rendemen-Rambung-Per-Supplier-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    /**
     * Execute preview logic.
     */
    public function preview(
        GenerateRekapRendemenRambungPerSupplierRequest $request,
        RekapRendemenRambungPerSupplierService $rekapRendemenRambungPerSupplierService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $this->fetchRows($rekapRendemenRambungPerSupplierService, $startDate, $endDate);
            $subRows = $this->fetchSubRows($rekapRendemenRambungPerSupplierService, $startDate, $endDate);
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
        GenerateRekapRendemenRambungPerSupplierRequest $request,
        RekapRendemenRambungPerSupplierService $rekapRendemenRambungPerSupplierService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $rekapRendemenRambungPerSupplierService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_RekapRendemenRambungPerSupplier valid.'
                : 'Struktur output SP_RekapRendemenRambungPerSupplier berubah.',
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
    private function extractDates(GenerateRekapRendemenRambungPerSupplierRequest $request): array
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
    private function fetchRows(
        RekapRendemenRambungPerSupplierService $rekapRendemenRambungPerSupplierService,
        string $startDate,
        string $endDate,
    ): array {
        return $rekapRendemenRambungPerSupplierService->fetch($startDate, $endDate);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSubRows(
        RekapRendemenRambungPerSupplierService $rekapRendemenRambungPerSupplierService,
        string $startDate,
        string $endDate,
    ): array {
        return $rekapRendemenRambungPerSupplierService->fetchSubReport($startDate, $endDate);
    }
}
