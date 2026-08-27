<?php

namespace App\Http\Controllers;

use App\Exceptions\GotenbergPdfException;
use App\Http\Requests\GenerateRekapProduksiFingerJointConsolidatedReportRequest;
use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiFingerJointConsolidatedReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class RekapProduksiFingerJointConsolidatedController extends Controller
{
    public function index(): View
    {
        return view('reports.finger-joint.rekap-produksi-finger-joint-consolidated-form');
    }

    public function download(
        GenerateRekapProduksiFingerJointConsolidatedReportRequest $request,
        RekapProduksiFingerJointConsolidatedReportService $reportService,
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

        $hk = $this->hkFromRange($startDate, $endDate);
        $machines = $this->groupByMachine($rows, $hk);
        $grandTotals = $this->computeTotals($rows);

        $viewData = [
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'hk' => $hk,
                'machines' => $machines,
                'grand_totals' => $grandTotals,
            ],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ];

        $html = $pdfGenerator->renderHtml('reports.finger-joint.rekap-produksi-finger-joint-consolidated-pdf', $viewData);

        $metrics = $pdfGenerator->paperMetrics(['reportData' => $viewData['reportData']]);

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
            'Laporan-Rekap-Produksi-Finger-Joint-Consolidated-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error.
     */
    private function gotenbergFailureResponse(GenerateRekapProduksiFingerJointConsolidatedReportRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 502);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
    }

    public function preview(
        GenerateRekapProduksiFingerJointConsolidatedReportRequest $request,
        RekapProduksiFingerJointConsolidatedReportService $reportService,
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
        GenerateRekapProduksiFingerJointConsolidatedReportRequest $request,
        RekapProduksiFingerJointConsolidatedReportService $reportService,
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

        $hk = $this->hkFromRange($startDate, $endDate);
        $machines = $this->groupByMachine($rows, $hk);
        $grandTotals = $this->computeTotals($rows);
        $generatedBy = $request->user() ?? auth('api')->user();

        $viewData = [
            'reportData' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'hk' => $hk,
                'machines' => $machines,
                'grand_totals' => $grandTotals,
            ],
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ];

        $html = $pdfGenerator->renderHtml('reports.finger-joint.rekap-produksi-finger-joint-consolidated-pdf', $viewData);

        $metrics = $pdfGenerator->paperMetrics(['reportData' => $viewData['reportData']]);

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
            'Laporan-Rekap-Produksi-Finger-Joint-Consolidated-%s-sd-%s.pdf',
            $startDate,
            $endDate,
        );

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    public function health(
        GenerateRekapProduksiFingerJointConsolidatedReportRequest $request,
        RekapProduksiFingerJointConsolidatedReportService $reportService,
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
                ? 'Struktur output SP_LapRekapProduksiFingerJointConsolidated valid.'
                : 'Struktur output SP_LapRekapProduksiFingerJointConsolidated berubah.',
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
    private function groupByMachine(array $rows, int $hkTotal): array
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
                // Reference report shows HK as "days in range", while Jmlh/HK uses working days.
                'hk' => $hkTotal,
                'hk_working' => $this->hkWorkingFromRows($machineRows),
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
        $sumCols = ['CCAkhir', 'S4S', 'TotalInput', 'OutputFJ', 'Jam'];
        $totals = array_fill_keys($sumCols, 0.0);
        $orgSum = 0.0;
        $personHoursSum = 0.0;
        $m3JamSum = 0.0;
        $m3JamOrgSum = 0.0;

        foreach ($rows as $row) {
            foreach ($sumCols as $col) {
                $totals[$col] += (float) ($row[$col] ?? 0.0);
            }

            $org = (float) ($row['Org'] ?? 0.0);
            $orgSum += $org;
            $personHoursSum += (float) ($row['_person_hours'] ?? 0.0);

            // Match reference report: total M3/Jam and M3/jam/Org are the SUM of per-row ratios (not recomputed from grand totals).
            $m3JamSum += (float) ($row['M3Jam'] ?? 0.0);
            $m3JamOrgSum += (float) ($row['M3JamOrg'] ?? 0.0);
        }

        $eps = 0.0000001;
        $rend = abs($totals['TotalInput']) > $eps ? (($totals['OutputFJ'] / $totals['TotalInput']) * 100.0) : 0.0;

        return array_merge($totals, [
            'Org' => $orgSum,
            'PersonHours' => $personHoursSum,
            'M3Jam' => $m3JamSum,
            'M3JamOrg' => $m3JamOrgSum,
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
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function hkFromRows(array $rows): int
    {
        $dates = [];

        foreach ($rows as $row) {
            $tanggal = (string) ($row['Tanggal'] ?? '');
            if ($tanggal === '') {
                continue;
            }
            $dates[$tanggal] = true;
        }

        return count($dates);
    }

    /**
     * Working days: count distinct Tanggal where there is actual activity (Jam / input / output > 0).
     * This matches the reference "Jmlh/HK" row, which is not divided by full calendar days.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function hkWorkingFromRows(array $rows): int
    {
        $eps = 0.0000001;
        $dates = [];

        foreach ($rows as $row) {
            $tanggal = (string) ($row['Tanggal'] ?? '');
            if ($tanggal === '') {
                continue;
            }

            $jam = (float) ($row['Jam'] ?? 0.0);
            $totalInput = (float) ($row['TotalInput'] ?? 0.0);
            $output = (float) ($row['OutputFJ'] ?? 0.0);

            if (abs($jam) > $eps || abs($totalInput) > $eps || abs($output) > $eps) {
                $dates[$tanggal] = true;
            }
        }

        return count($dates);
    }
}
