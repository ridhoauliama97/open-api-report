<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRekapProduksiBarangJadiConsolidatedReportRequest;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiBarangJadiConsolidatedReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RekapProduksiBarangJadiConsolidatedController extends Controller
{
    public function index(): View
    {
        return view('reports.barang-jadi.rekap-produksi-barang-jadi-consolidated-form');
    }

    public function download(
        GenerateRekapProduksiBarangJadiConsolidatedReportRequest $request,
        RekapProduksiBarangJadiConsolidatedReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        $generatedBy = $request->user() ?? auth('api')->user();

        if ($generatedBy === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return back()->withInput()->withErrors(['auth' => 'Silakan login terlebih dahulu untuk mencetak laporan.']);
        }

        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $rows = $reportService->fetch($startDate, $endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['report' => $exception->getMessage()]);
        }

        $machines = $this->groupByMachine($rows);

        $pdf = $pdfGenerator->render('reports.barang-jadi.rekap-produksi-barang-jadi-consolidated-pdf', [
            'reportData' => ['start_date' => $startDate, 'end_date' => $endDate, 'machines' => $machines],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf('Laporan-Rekap-Produksi-Packing-Consolidated-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateRekapProduksiBarangJadiConsolidatedReportRequest $request,
        RekapProduksiBarangJadiConsolidatedReportService $reportService,
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
            'meta' => ['start_date' => $startDate, 'end_date' => $endDate, 'total_rows' => count($rows), 'column_order' => array_keys($rows[0] ?? [])],
            'data' => $rows,
        ]);
    }

    public function previewPdf(
        GenerateRekapProduksiBarangJadiConsolidatedReportRequest $request,
        RekapProduksiBarangJadiConsolidatedReportService $reportService,
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

        $pdf = $pdfGenerator->render('reports.barang-jadi.rekap-produksi-barang-jadi-consolidated-pdf', [
            'reportData' => ['start_date' => $startDate, 'end_date' => $endDate, 'machines' => $machines],
            'generatedBy' => $request->user() ?? auth('api')->user(),
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
            'pdf_pack_table_data' => false,
        ]);

        $filename = sprintf('Laporan-Rekap-Produksi-Packing-Consolidated-%s-sd-%s.pdf', $startDate, $endDate);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateRekapProduksiBarangJadiConsolidatedReportRequest $request,
        RekapProduksiBarangJadiConsolidatedReportService $reportService,
    ): JsonResponse {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy'] ? 'Struktur output SP_LapRekapProduksiBarangJadiConsolidated valid.' : 'Struktur output SP_LapRekapProduksiBarangJadiConsolidated berubah.',
            'meta' => ['start_date' => $startDate, 'end_date' => $endDate],
            'health' => $result,
        ]);
    }

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
            $result[] = ['nama_mesin' => $namaMesin, 'rows' => $machineRows, 'totals' => $this->computeTotals($machineRows), 'hk' => count($machineRows)];
        }
        usort($result, static fn(array $a, array $b): int => strcmp($a['nama_mesin'], $b['nama_mesin']));
        return $result;
    }

    private function computeTotals(array $rows): array
    {
        $sumCols = ['BJ', 'Moulding', 'Sanding', 'Wip', 'TotalInput', 'OutputPacking', 'OutputReproses', 'TotalOutput'];
        $totals = array_fill_keys($sumCols, 0.0);
        $jamSum = 0.0;
        $orgSum = 0.0;
        $m3JamSum = 0.0;
        $m3JamOrgSum = 0.0;
        foreach ($rows as $row) {
            foreach ($sumCols as $col) {
                $totals[$col] += (float) ($row[$col] ?? 0.0);
            }
            $jamSum += (float) ($row['Jam'] ?? 0.0);
            $orgSum += (float) ($row['Org'] ?? 0.0);
            $m3JamSum += (float) ($row['M3Jam'] ?? 0.0);
            $m3JamOrgSum += (float) ($row['M3JamOrg'] ?? 0.0);
        }

        $eps = 0.0000001;
        $rend = abs($totals['TotalInput']) > $eps ? (($totals['TotalOutput'] / $totals['TotalInput']) * 100.0) : 0.0;

        return array_merge($totals, [
            'Jam' => $jamSum,
            'Org' => $orgSum,
            'M3Jam' => $m3JamSum,
            'M3JamOrg' => $m3JamOrgSum,
            'Rend' => $rend,
        ]);
    }
}
