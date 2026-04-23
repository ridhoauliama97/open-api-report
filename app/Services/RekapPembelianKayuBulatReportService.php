<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapPembelianKayuBulatReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        return array_map(function (object $row): array {
            $item = (array) $row;
            $item['Tahun'] = (int) ($item['Tahun'] ?? 0);
            $item['Bulan'] = (int) ($item['Bulan'] ?? 0);
            $item['Ton'] = $this->toFloat($item['Ton'] ?? null) ?? 0.0;

            return $item;
        }, $this->runProcedureQuery());
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();
        $endYear = (int) now()->format('Y');
        $startYear = $endYear - 10;
        $monthLabels = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
        $years = [];
        $monthTotals = array_fill(1, 12, 0.0);

        foreach (range($startYear, $endYear) as $year) {
            $years[$year] = [
                'tahun' => $year,
                'months' => array_fill(1, 12, 0.0),
                'total' => 0.0,
            ];
        }

        foreach ($rows as $row) {
            $year = (int) ($row['Tahun'] ?? 0);
            $month = (int) ($row['Bulan'] ?? 0);
            $ton = (float) ($row['Ton'] ?? 0.0);

            if ($year < $startYear || $year > $endYear) {
                continue;
            }

            if ($month >= 1 && $month <= 12) {
                $years[$year]['months'][$month] += $ton;
                $monthTotals[$month] += $ton;
            }

            $years[$year]['total'] += $ton;
        }

        ksort($years);
        $rows = array_values(array_filter(
            $rows,
            static fn(array $row): bool => (int) ($row['Tahun'] ?? 0) >= $startYear
                && (int) ($row['Tahun'] ?? 0) <= $endYear,
        ));

        return [
            'rows' => $rows,
            'year_rows' => array_values($years),
            'month_labels' => $monthLabels,
            'month_totals' => $monthTotals,
            'summary' => [
                'total_rows' => count($rows),
                'total_years' => count($years),
                'grand_total' => array_sum($monthTotals),
                'start_year' => $startYear,
                'end_year' => $endYear,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $rows = $this->fetch();
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.rekap_pembelian_kayu_bulat.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($rows),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(): array
    {
        $configKey = 'reports.rekap_pembelian_kayu_bulat';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure");
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap pembelian kayu bulat belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap pembelian kayu bulat dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PEMBELIAN_KAYU_BULAT_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan rekap pembelian kayu bulat belum diisi.');

            return $connection->select($query);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure}",
            'call' => "CALL {$procedure}()",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure}" : "CALL {$procedure}()",
        };

        return $connection->select($sql);
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $trimmed = str_replace(',', '.', $trimmed);

        return is_numeric($trimmed) ? (float) $trimmed : null;
    }
}
