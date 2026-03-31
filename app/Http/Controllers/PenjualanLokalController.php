<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateBalokSudahSemprotReportRequest;
use App\Services\PenjualanLokalReportService;
use App\Services\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PenjualanLokalController extends Controller
{
    public function index(): View
    {
        return view('reports.penjualan-kayu.penjualan-lokal-form');
    }

    public function download(
        GenerateBalokSudahSemprotReportRequest $request,
        PenjualanLokalReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateBalokSudahSemprotReportRequest $request,
        PenjualanLokalReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateBalokSudahSemprotReportRequest $request,
        PenjualanLokalReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $rows = $reportService->fetch($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $summary = $this->buildSummary($rows);

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
                'section_count' => count($summary['sections']),
                'summary_rows' => count($summary['rows']),
            ],
            'summary' => $summary,
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateBalokSudahSemprotReportRequest $request,
        PenjualanLokalReportService $reportService,
    ): JsonResponse {
        [$startDate, $endDate] = $this->extractDates($request);

        try {
            $result = $reportService->healthCheck($startDate, $endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output query Penjualan Lokal valid.'
                : 'Struktur output query Penjualan Lokal berubah.',
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'TglAwal' => $startDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateBalokSudahSemprotReportRequest $request,
        PenjualanLokalReportService $reportService,
        PdfGenerator $pdfGenerator,
        bool $inline,
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

        [$startDate, $endDate] = $this->extractDates($request);

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

        $summary = $this->buildSummary($rows);

        $pdf = $pdfGenerator->render('reports.penjualan-kayu.penjualan-lokal-pdf', [
            'rows' => $rows,
            'sections' => $summary['sections'],
            'summaryRows' => $summary['rows'],
            'subtotalTon' => $summary['subtotal_ton'],
            'grandTotalTon' => $summary['grand_total_ton'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ]);

        $filename = sprintf('Laporan-Penjualan-Lokal-%s-sd-%s.pdf', $startDate, $endDate);
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractDates(GenerateBalokSudahSemprotReportRequest $request): array
    {
        return [$request->startDate(), $request->endDate()];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{rows: array<int, array<string, mixed>>, subtotal_ton: float, grand_total_ton: float}
     */
    private function buildSummary(array $rows): array
    {
        $sections = [];
        $grouped = [];

        foreach ($rows as $row) {
            $proses = trim((string) ($row['Proses'] ?? ''));
            $jenisLabel = trim((string) ($row['Jenis'] ?? ''));
            $namaGrade = trim((string) ($row['NamaGrade'] ?? ''));
            $ton = round($this->toFloat($row['TonAndm3'] ?? null) ?? 0.0, 4);

            if ($jenisLabel === null || $ton <= 0) {
                continue;
            }

            $sectionKey = $proses !== '' ? $proses : 'LAINNYA';
            if (!isset($sections[$sectionKey])) {
                $sections[$sectionKey] = [
                    'proses' => $sectionKey,
                    'rows' => [],
                    'subtotal_ton' => 0.0,
                ];
            }

            $sections[$sectionKey]['rows'][] = [
                'no' => count($sections[$sectionKey]['rows']) + 1,
                'jenis' => $jenisLabel,
                'nama_grade' => $namaGrade,
                'ton' => $ton,
            ];
            $sections[$sectionKey]['subtotal_ton'] += $ton;

            $groupKey = $jenisLabel . '|' . $namaGrade;
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'jenis' => $jenisLabel,
                    'nama_grade' => $namaGrade,
                    'ton' => 0.0,
                ];
            }

            $grouped[$groupKey]['ton'] += $ton;
        }

        foreach ($sections as &$section) {
            $section['subtotal_ton'] = round((float) $section['subtotal_ton'], 4);
        }
        unset($section);

        $summaryRows = array_values($grouped);
        usort($summaryRows, static function (array $left, array $right): int {
            $jenisCompare = strnatcasecmp((string) $left['jenis'], (string) $right['jenis']);
            if ($jenisCompare !== 0) {
                return $jenisCompare;
            }

            return strnatcasecmp((string) $left['nama_grade'], (string) $right['nama_grade']);
        });

        foreach ($summaryRows as $index => &$summaryRow) {
            $summaryRow['no'] = $index + 1;
            $summaryRow['ton'] = round((float) $summaryRow['ton'], 4);
        }
        unset($summaryRow);

        $grandTotal = round(array_sum(array_column($summaryRows, 'ton')), 4);

        return [
            'sections' => array_values($sections),
            'rows' => $summaryRows,
            'subtotal_ton' => $grandTotal,
            'grand_total_ton' => $grandTotal,
        ];
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace(',', '.', trim($value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
