<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapProduksiCrossCutAkhirConsolidatedReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiCrossCutAkhirConsolidatedReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapProduksiCrossCutAkhirConsolidatedController extends Controller
{
    public function index(): View
    {
        return view('reports.cross-cut-akhir.rekap-produksi-cc-akhir-consolidated-form');
    }

    public function download(
        GenerateRekapProduksiCrossCutAkhirConsolidatedReportRequest $request,
        RekapProduksiCrossCutAkhirConsolidatedReportService $reportService,
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

        $startDate = $request->startDate();
        $endDate = $request->endDate();

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

        $machines = $this->groupByMachine($rows);
        $grandTotals = $this->computeTotals($rows);

        $pdf = $pdfGenerator->render('reports.cross-cut-akhir.rekap-produksi-cc-akhir-consolidated-pdf', [
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'machines' => $machines,
                'grand_totals' => $grandTotals,
            ],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf(
            'Laporan-Rekap-Produksi-CCAkhir-Consolidated-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateRekapProduksiCrossCutAkhirConsolidatedReportRequest $request,
        RekapProduksiCrossCutAkhirConsolidatedReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

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
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
            ],
            'data' => $rows,
        ]);
    }

    public function previewPdf(
        GenerateRekapProduksiCrossCutAkhirConsolidatedReportRequest $request,
        RekapProduksiCrossCutAkhirConsolidatedReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $rows = $reportService->fetch($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $machines = $this->groupByMachine($rows);
        $grandTotals = $this->computeTotals($rows);

        $pdf = $pdfGenerator->render('reports.cross-cut-akhir.rekap-produksi-cc-akhir-consolidated-pdf', [
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'machines' => $machines,
                'grand_totals' => $grandTotals,
            ],
            'generatedBy' => $request->user() ?? auth('api')->user(),
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf(
            'Laporan-Rekap-Produksi-CCAkhir-Consolidated-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateRekapProduksiCrossCutAkhirConsolidatedReportRequest $request,
        RekapProduksiCrossCutAkhirConsolidatedReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapRekapProduksiCrossCutAkhirConsolidated valid.'
                : 'Struktur output SP_LapRekapProduksiCrossCutAkhirConsolidated berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{nama_mesin:string, rows:array<int, array<string, mixed>>, totals:array<string, float>, hk:int}>
     */
    private function groupByMachine(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $namaMesin = (string) ($row['NamaMesin'] ?? '');
            if ($namaMesin === '') {
                $namaMesin = 'MESIN';
            }
            $groups[$namaMesin][] = $row;
        }

        $result = [];
        foreach ($groups as $namaMesin => $machineRows) {
            $result[] = [
                'nama_mesin' => $namaMesin,
                'rows' => $machineRows,
                'totals' => $this->computeTotals($machineRows),
                'hk' => count($machineRows),
            ];
        }

        usort($result, static fn(array $a, array $b): int => strcmp($a['nama_mesin'], $b['nama_mesin']));

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, float>
     */
    private function computeTotals(array $rows): array
    {
        $sumCols = [
            'BJ',
            'FJ',
            'Laminating',
            'Moulding',
            'Reproses',
            'Wip',
            'TotalInput',
            'OutputCCAkhir',
            'Jam',
        ];
        $totals = array_fill_keys($sumCols, 0.0);
        $orgSum = 0.0;
        $m3JamSum = 0.0;
        $m3JamOrgSum = 0.0;
        $rowCount = count($rows);

        foreach ($rows as $row) {
            foreach ($sumCols as $col) {
                $totals[$col] += (float) ($row[$col] ?? 0.0);
            }

            $orgSum += (float) ($row['Org'] ?? 0.0);
            $m3JamSum += (float) ($row['M3Jam'] ?? 0.0);
            $m3JamOrgSum += (float) ($row['M3JamOrg'] ?? 0.0);
        }

        $eps = 0.0000001;
        $rend = abs($totals['TotalInput']) > $eps
            ? (($totals['OutputCCAkhir'] / $totals['TotalInput']) * 100.0)
            : 0.0;

        return array_merge($totals, [
            'Org' => $orgSum,
            'M3Jam' => $rowCount > 0 ? ($m3JamSum / $rowCount) : 0.0,
            'M3JamOrg' => $rowCount > 0 ? ($m3JamOrgSum / $rowCount) : 0.0,
            'Rend' => $rend,
        ]);
    }
}
