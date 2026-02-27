<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStockSTKeringReportRequest;
use App\Services\PdfGenerator;
use App\Services\StockSTKeringReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class StockSTKeringController extends Controller
{
    public function index(): View
    {
        return view('reports.sawn-timber.stock-st-kering-form');
    }

    public function download(
        GenerateStockSTKeringReportRequest $request,
        StockSTKeringReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, false);
    }

    public function previewPdf(
        GenerateStockSTKeringReportRequest $request,
        StockSTKeringReportService $reportService,
        PdfGenerator $pdfGenerator,
    ) {
        return $this->buildPdfResponse($request, $reportService, $pdfGenerator, true);
    }

    public function preview(
        GenerateStockSTKeringReportRequest $request,
        StockSTKeringReportService $reportService,
    ): JsonResponse {
        $endDate = $request->endDate();

        try {
            $rows = $reportService->fetch($endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $previewLimit = $this->resolveWebPreviewLimit($request);
        $isTruncated = false;
        if ($previewLimit > 0 && count($rows) > $previewLimit) {
            $rows = array_slice($rows, 0, $previewLimit);
            $isTruncated = true;
        }

        return response()->json([
            'message' => 'Preview laporan berhasil diambil.',
            'meta' => [
                'end_date' => $endDate,
                'TglAkhir' => $endDate,
                'total_rows' => count($rows),
                'column_order' => array_keys($rows[0] ?? []),
                'is_truncated' => $isTruncated,
                'preview_limit' => $previewLimit,
            ],
            'data' => $rows,
        ]);
    }

    public function health(
        GenerateStockSTKeringReportRequest $request,
        StockSTKeringReportService $reportService,
    ): JsonResponse {
        $endDate = $request->endDate();

        try {
            $result = $reportService->healthCheck($endDate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $result['is_healthy']
                ? 'Struktur output SP_LapStockSTKering valid.'
                : 'Struktur output SP_LapStockSTKering berubah.',
            'meta' => [
                'end_date' => $endDate,
                'TglAkhir' => $endDate,
            ],
            'health' => $result,
        ]);
    }

    private function buildPdfResponse(
        GenerateStockSTKeringReportRequest $request,
        StockSTKeringReportService $reportService,
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

        $endDate = $request->endDate();

        try {
            $rows = $reportService->fetch($endDate);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['report' => $exception->getMessage()]);
        }

        $summaryStats = $this->buildSummaryStats($rows);

        if ($inline) {
            $previewPdfLimit = (int) config('reports.stock_st_kering.preview_pdf_max_rows', 0);
            if ($previewPdfLimit > 0 && count($rows) > $previewPdfLimit) {
                $rows = array_slice($rows, 0, $previewPdfLimit);
            }
        }

        $pdf = $pdfGenerator->render('reports.sawn-timber.stock-st-kering-pdf', [
            'rows' => $rows,
            'endDate' => $endDate,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
            'pdf_orientation' => 'portrait',
            'summaryStats' => $summaryStats,
        ]);

        $filename = sprintf('Laporan-Stock-ST-Kering-%s.pdf', $endDate);
        $dispositionType = $inline ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $dispositionType, $filename),
        ]);
    }

    private function resolveWebPreviewLimit(GenerateStockSTKeringReportRequest $request): int
    {
        // Keep API preview untouched; limit only web preview JSON for faster UI response.
        if ($request->is('api/*')) {
            return 0;
        }

        return (int) config('reports.stock_st_kering.preview_json_max_rows', 100);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, int|float>
     */
    private function buildSummaryStats(array $rows): array
    {
        $normalize = static function (?string $name): string {
            return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name ?? '') ?? '');
        };

        $findColumn = static function (array $columns, array $candidates) use ($normalize): ?string {
            $candidateSet = [];
            foreach ($candidates as $candidate) {
                $candidateSet[$normalize((string) $candidate)] = true;
            }

            foreach ($columns as $column) {
                if (isset($candidateSet[$normalize((string) $column)])) {
                    return (string) $column;
                }
            }

            return null;
        };

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (!is_string($value)) {
                return null;
            }

            $normalized = trim(str_replace(' ', '', $value));
            if ($normalized === '') {
                return null;
            }

            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                    $normalized = str_replace('.', '', $normalized);
                    $normalized = str_replace(',', '.', $normalized);
                } else {
                    $normalized = str_replace(',', '', $normalized);
                }
            } elseif (str_contains($normalized, ',')) {
                $normalized = str_replace(',', '.');
            }

            return is_numeric($normalized) ? (float) $normalized : null;
        };

        $columns = array_keys($rows[0] ?? []);
        $jenisColumn = $findColumn($columns, ['Jenis', 'JenisKayu', 'Type', 'Tipe', 'Kategori']);
        $produkColumn = $findColumn($columns, ['Produk', 'Product', 'NamaProduk', 'NamaBarang', 'Item']);
        $pcsColumn = $findColumn($columns, ['Pcs', 'JmlhBatang', 'JumlahBatang']);
        $tonColumn = $findColumn($columns, ['Ton', 'JmlhTon', 'JumlahTon']);

        $jenisSet = [];
        $produkPairSet = [];
        $produkSet = [];
        $totalPcs = 0.0;
        $totalTon = 0.0;

        foreach ($rows as $row) {
            $jenis = trim((string) ($jenisColumn !== null ? ($row[$jenisColumn] ?? '') : ''));
            $produk = trim((string) ($produkColumn !== null ? ($row[$produkColumn] ?? '') : ''));
            $jenis = $jenis !== '' ? $jenis : 'Tanpa Jenis';
            $produk = $produk !== '' ? $produk : 'Tanpa Produk';

            $jenisSet[$jenis] = true;
            $produkPairSet[$jenis . '||' . $produk] = true;
            $produkSet[$produk] = true;

            $totalPcs += $pcsColumn !== null ? ($toFloat($row[$pcsColumn] ?? null) ?? 0.0) : 0.0;
            $totalTon += $tonColumn !== null ? ($toFloat($row[$tonColumn] ?? null) ?? 0.0) : 0.0;
        }

        return [
            'total_rows' => count($rows),
            'total_jenis' => count($jenisSet),
            'total_produk' => count($produkPairSet),
            'total_produk_unik' => count($produkSet),
            'total_pcs' => $totalPcs,
            'total_ton' => $totalTon,
        ];
    }
}

