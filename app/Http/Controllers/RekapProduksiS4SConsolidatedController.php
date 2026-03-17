<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapProduksiS4SConsolidatedReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiS4SConsolidatedReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapProduksiS4SConsolidatedController extends Controller
{
    public function index(): View
    {
        return view('reports.s4s.rekap-produksi-s4s-consolidated-form');
    }

    public function download(
        GenerateRekapProduksiS4SConsolidatedReportRequest $request,
        RekapProduksiS4SConsolidatedReportService $reportService,
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
        $hk = $this->hkFromRange($startDate, $endDate);
        $grandTotals = $this->computeTotals($rows);
        $hkSummary = $this->buildHkSummary($machines);

        $pdf = $pdfGenerator->render('reports.s4s.rekap-produksi-s4s-consolidated-pdf', [
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'hk' => $hk,
                'machines' => $machines,
                'hk_summary' => $hkSummary,
                'grand_totals' => $grandTotals,
            ],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            // Workaround for mPDF collapsed-border table bug (can crash when true).
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf(
            'Laporan-Rekap-Produksi-S4S-Consolidated-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateRekapProduksiS4SConsolidatedReportRequest $request,
        RekapProduksiS4SConsolidatedReportService $reportService,
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
        GenerateRekapProduksiS4SConsolidatedReportRequest $request,
        RekapProduksiS4SConsolidatedReportService $reportService,
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
        $hk = $this->hkFromRange($startDate, $endDate);
        $grandTotals = $this->computeTotals($rows);
        $hkSummary = $this->buildHkSummary($machines);

        $pdf = $pdfGenerator->render('reports.s4s.rekap-produksi-s4s-consolidated-pdf', [
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'hk' => $hk,
                'machines' => $machines,
                'hk_summary' => $hkSummary,
                'grand_totals' => $grandTotals,
            ],
            'generatedBy' => $request->user() ?? auth('api')->user(),
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf(
            'Laporan-Rekap-Produksi-S4S-Consolidated-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateRekapProduksiS4SConsolidatedReportRequest $request,
        RekapProduksiS4SConsolidatedReportService $reportService,
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
                ? 'Struktur output SP_LapRekapProduksiS4SConsolidated valid.'
                : 'Struktur output SP_LapRekapProduksiS4SConsolidated berubah.',
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
                'hk' => $this->hkFromRows($machineRows),
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
        $sumCols = ['CCAkhir', 'FJ', 'Reproses', 'S4S', 'ST', 'TotalInput', 'OutputS4S', 'Jam'];
        $totals = array_fill_keys($sumCols, 0.0);
        $orgSum = 0.0;
        $personHoursSum = 0.0;

        foreach ($rows as $row) {
            foreach ($sumCols as $col) {
                $totals[$col] += (float) ($row[$col] ?? 0.0);
            }

            $org = (float) ($row['Org'] ?? 0.0);
            $orgSum += $org;
            $personHoursSum += (float) ($row['_person_hours'] ?? 0.0);
        }

        $eps = 0.0000001;
        $m3Jam = abs($totals['Jam']) > $eps ? ($totals['OutputS4S'] / $totals['Jam']) : 0.0;
        $m3JamOrg = abs($personHoursSum) > $eps ? ($totals['OutputS4S'] / $personHoursSum) : 0.0;
        $rend = abs($totals['TotalInput']) > $eps ? (($totals['OutputS4S'] / $totals['TotalInput']) * 100.0) : 0.0;

        return array_merge($totals, [
            'Org' => $orgSum,
            'PersonHours' => $personHoursSum,
            'M3Jam' => $m3Jam,
            'M3JamOrg' => $m3JamOrg,
            'Rend' => $rend,
        ]);
    }

    private function hkFromRange(string $startDate, string $endDate): int
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } catch (\Throwable) {
            return 0;
        }

        if ($end->lessThan($start)) {
            return 0;
        }

        return $start->diffInDays($end) + 1;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function hkFromRows(array $rows): int
    {
        $dates = [];
        foreach ($rows as $row) {
            $d = (string) ($row['Tanggal'] ?? '');
            if ($d !== '') {
                $dates[$d] = true;
            }
        }

        return count($dates);
    }

    /**
     * @param array<int, array{nama_mesin:string, rows:array<int, array<string, mixed>>, totals:array<string, float>, hk:int}> $machines
     * @return array<int, array{nama_mesin:string, hk:int, totals:array<string, float>}>
     */
    private function buildHkSummary(array $machines): array
    {
        $rows = [];
        foreach ($machines as $machine) {
            $rows[] = [
                'nama_mesin' => (string) ($machine['nama_mesin'] ?? ''),
                'hk' => (int) ($machine['hk'] ?? 0),
                'totals' => is_array($machine['totals'] ?? null) ? $machine['totals'] : [],
            ];
        }

        return $rows;
    }
}
