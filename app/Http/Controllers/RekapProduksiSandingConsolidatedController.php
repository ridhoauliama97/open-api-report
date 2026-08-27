<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateRekapProduksiSandingConsolidatedReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiSandingConsolidatedReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class RekapProduksiSandingConsolidatedController extends Controller
{
    public function index(): View
    {
        return view('reports.sanding.rekap-produksi-sanding-consolidated-form');
    }

    public function download(
        GenerateRekapProduksiSandingConsolidatedReportRequest $request,
        RekapProduksiSandingConsolidatedReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
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

        $html = $pdfGenerator->renderHtml('reports.sanding.rekap-produksi-sanding-consolidated-pdf', [
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'machines' => $machines,
                'grand_totals' => $grandTotals,
            ],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),

        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'machines' => $machines,
                'grand_totals' => $grandTotals,
            ],
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

        $filename = sprintf(
            'Laporan-Rekap-Produksi-Sanding-Consolidated-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    public function preview(
        GenerateRekapProduksiSandingConsolidatedReportRequest $request,
        RekapProduksiSandingConsolidatedReportService $reportService,
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
        GenerateRekapProduksiSandingConsolidatedReportRequest $request,
        RekapProduksiSandingConsolidatedReportService $reportService,
        PdfGenerator $pdfGenerator,
        GotenbergPdfClient $gotenbergPdfClient,
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

        $generatedBy = $request->user() ?? auth('api')->user();

        $html = $pdfGenerator->renderHtml('reports.sanding.rekap-produksi-sanding-consolidated-pdf', [
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'machines' => $machines,
                'grand_totals' => $grandTotals,
            ],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),

        ]);

        $metrics = $pdfGenerator->paperMetrics([
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'machines' => $machines,
                'grand_totals' => $grandTotals,
            ],
        ]);

        $footerHtml = view('reports.partials.gotenberg-footer', [
            'generatedByName' => $generatedBy?->name ?? $generatedBy?->Username ?? 'sistem',
            'generatedAtText' => now()->locale('id')->translatedFormat('d-M-y H:i'),
        ])->render();

        try {
            $pdfBytes = $gotenbergPdfClient->convertHtml($html, $metrics, $footerHtml);
        } catch (GotenbergPdfException $exception) {
            return $this->gotenbergFailureResponse($request, $exception->getMessage());
        }

        $filename = sprintf(
            'Laporan-Rekap-Produksi-Sanding-Consolidated-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateRekapProduksiSandingConsolidatedReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
    }

    public function health(
        GenerateRekapProduksiSandingConsolidatedReportRequest $request,
        RekapProduksiSandingConsolidatedReportService $reportService,
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
                ? 'Struktur output SP_LapRekapProduksiSandingConsolidated valid.'
                : 'Struktur output SP_LapRekapProduksiSandingConsolidated berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
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

        usort($result, static fn (array $a, array $b): int => strcmp($a['nama_mesin'], $b['nama_mesin']));

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, float>
     */
    private function computeTotals(array $rows): array
    {
        $sumCols = [
            'BJ',
            'CCAkhir',
            'FJ',
            'Moulding',
            'Reproses',
            'Wip',
            'TotalInput',
            'OutputSanding',
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
            ? (($totals['OutputSanding'] / $totals['TotalInput']) * 100.0)
            : 0.0;

        return array_merge($totals, [
            'Org' => $orgSum,
            'M3Jam' => $rowCount > 0 ? ($m3JamSum / $rowCount) : 0.0,
            'M3JamOrg' => $rowCount > 0 ? ($m3JamOrgSum / $rowCount) : 0.0,
            'Rend' => $rend,
        ]);
    }
}
