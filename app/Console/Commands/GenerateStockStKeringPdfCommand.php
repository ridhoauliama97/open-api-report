<?php

namespace App\Console\Commands;

use App\Services\FilePdfJobStore;
use App\Services\PdfGenerator;
use App\Services\StockSTKeringReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateStockStKeringPdfCommand extends Command
{
    protected $signature = 'reports:generate-stock-st-kering-pdf
        {jobId : File-based PDF job id}
        {--requested-by=async-job : User label printed in the PDF footer}';

    protected $description = 'Generate Stock ST Kering PDF in a database-free background process.';

    public function handle(
        FilePdfJobStore $jobStore,
        StockSTKeringReportService $reportService,
        PdfGenerator $pdfGenerator,
    ): int {
        $jobId = (string) $this->argument('jobId');
        $requestedBy = (string) $this->option('requested-by');

        @ini_set('memory_limit', (string) env('STOCK_ST_KERING_PDF_MEMORY_LIMIT', '1024M'));
        @set_time_limit(3600);

        $jobStore->markProcessing($jobId);

        try {
            $job = $jobStore->find($jobId);
            $payload = is_array($job['request_payload'] ?? null) ? $job['request_payload'] : [];
            $endDate = (string) ($payload['end_date'] ?? $payload['TglAkhir'] ?? '');

            if ($endDate === '') {
                throw new \RuntimeException('Parameter end_date tidak ditemukan pada job PDF Stock ST Kering.');
            }

            $rows = $reportService->fetch($endDate);
            $storagePath = trim((string) config('app.pdf_storage_path', 'pdf_reports'), '/')
                .'/stock-st-kering-'.$endDate.'-'.now()->format('Ymd_His').'-'.substr($jobId, 0, 8).'.pdf';

            $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));
            $outputPath = $disk->path($storagePath);
            $outputDir = dirname($outputPath);
            if (! is_dir($outputDir)) {
                @mkdir($outputDir, 0777, true);
            }

            $pdfGenerator->renderToFile('reports.sawn-timber.stock-st-kering-pdf', [
                'rows' => $rows,
                'endDate' => $endDate,
                'generatedBy' => (object) [
                    'name' => $requestedBy,
                    'Username' => $requestedBy,
                ],
                'generatedAt' => now(),
                'pdf_orientation' => 'portrait',
                'summaryStats' => $this->buildSummaryStats($rows),
                'pdf_simple_tables' => false,
            ], $outputPath);

            $jobStore->markDone($jobId, $storagePath);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $jobStore->markFailed($jobId, $exception->getMessage());
            report($exception);

            return self::FAILURE;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, int|float>
     */
    private function buildSummaryStats(array $rows): array
    {
        $normalize = static fn (?string $name): string => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name ?? '') ?? '');
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

            if (! is_string($value)) {
                return null;
            }

            $normalized = trim(str_replace(' ', '', $value));
            if ($normalized === '') {
                return null;
            }

            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                $normalized = str_replace(',', '', $normalized);
            } elseif (str_contains($normalized, ',')) {
                $normalized = str_replace(',', '.', $normalized);
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
            $jenis = trim((string) ($jenisColumn !== null ? ($row[$jenisColumn] ?? '') : '')) ?: 'Tanpa Jenis';
            $produk = trim((string) ($produkColumn !== null ? ($row[$produkColumn] ?? '') : '')) ?: 'Tanpa Produk';
            $jenisSet[$jenis] = true;
            $produkPairSet[$jenis.'||'.$produk] = true;
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
