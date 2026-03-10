<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaldoHidupKayuBulatKgReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        return $this->normalizeMainRows($this->runProcedureQuery(false));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSubReport(): array
    {
        return $this->normalizeSubRows($this->runProcedureQuery(true));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();
        $subRows = $this->fetchSubReport();

        if ($subRows === []) {
            $subRows = $this->buildSubRowsFromMain($rows);
        }

        $totalBruto = array_sum(array_map(static fn (array $row): float => (float) ($row['Bruto'] ?? 0.0), $rows));
        $totalTara = array_sum(array_map(static fn (array $row): float => (float) ($row['Tara'] ?? 0.0), $rows));
        $totalBerat = array_sum(array_map(static fn (array $row): float => (float) ($row['Berat'] ?? 0.0), $rows));
        $distinctKayuBulat = count(array_unique(array_map(static fn (array $row): string => (string) ($row['NoKayuBulat'] ?? ''), $rows)));

        $gradeTotals = [];
        foreach ($subRows as $row) {
            $grade = trim((string) ($row['NamaGrade'] ?? 'Tanpa Grade'));
            $gradeTotals[$grade] = (float) ($row['Berat'] ?? 0.0);
        }

        return [
            'rows' => $rows,
            'sub_rows' => $subRows,
            'summary' => [
                'total_rows' => count($rows),
                'total_distinct_logs' => $distinctKayuBulat,
                'total_bruto' => $totalBruto,
                'total_tara' => $totalTara,
                'total_berat' => $totalBerat,
                'grade_totals' => $gradeTotals,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $rows = $this->fetch();
        $subRows = $this->fetchSubReport();
        $detectedColumns = array_keys($rows[0] ?? []);
        $detectedSubColumns = array_keys($subRows[0] ?? []);
        $expectedColumns = config('reports.saldo_hidup_kayu_bulat_kg.expected_columns', []);
        $expectedSubColumns = config('reports.saldo_hidup_kayu_bulat_kg.expected_sub_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $expectedSubColumns = is_array($expectedSubColumns) ? array_values($expectedSubColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns))
                && empty(array_diff($expectedSubColumns, $detectedSubColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($rows),
            'expected_sub_columns' => $expectedSubColumns,
            'detected_sub_columns' => $detectedSubColumns,
            'missing_sub_columns' => array_values(array_diff($expectedSubColumns, $detectedSubColumns)),
            'extra_sub_columns' => array_values(array_diff($detectedSubColumns, $expectedSubColumns)),
            'sub_row_count' => count($subRows),
        ];
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMainRows(array $rows): array
    {
        return array_values(array_map(function (object $row): array {
            $item = (array) $row;
            $item['Bruto'] = $this->toFloat($item['Bruto'] ?? null) ?? 0.0;
            $item['Tara'] = $this->toFloat($item['Tara'] ?? null) ?? 0.0;
            $item['Berat'] = $this->toFloat($item['Berat'] ?? null) ?? 0.0;

            return $item;
        }, $rows));
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSubRows(array $rows): array
    {
        return array_values(array_map(function (object $row): array {
            $item = (array) $row;
            $item['Berat'] = $this->toFloat($item['Berat'] ?? null) ?? 0.0;

            return $item;
        }, $rows));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildSubRowsFromMain(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $grade = trim((string) ($row['NamaGrade'] ?? 'Tanpa Grade'));
            $grouped[$grade] = ($grouped[$grade] ?? 0.0) + (float) ($row['Berat'] ?? 0.0);
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        $result = [];
        foreach ($grouped as $grade => $berat) {
            $result[] = [
                'NamaGrade' => $grade,
                'Berat' => $berat,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(bool $isSubProcedure): array
    {
        $configKey = 'reports.saldo_hidup_kayu_bulat_kg';
        $connectionName = config("{$configKey}.database_connection");
        $procedureKey = $isSubProcedure ? 'sub_stored_procedure' : 'stored_procedure';
        $queryKey = $isSubProcedure ? 'sub_query' : 'query';
        $procedure = (string) config("{$configKey}.{$procedureKey}");
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.{$queryKey}");

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException(
                $isSubProcedure
                    ? 'Stored procedure sub laporan saldo hidup kayu bulat timbang KG belum dikonfigurasi.'
                    : 'Stored procedure laporan saldo hidup kayu bulat timbang KG belum dikonfigurasi.'
            );
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan saldo hidup kayu bulat timbang KG dikonfigurasi untuk SQL Server. '
                . 'Set SALDO_HIDUP_KAYU_BULAT_KG_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan saldo hidup kayu bulat timbang KG belum diisi.');

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

        $trimmed = preg_replace('/[^0-9,.\-]/', '', $trimmed) ?? '';
        if ($trimmed === '' || $trimmed === '-' || $trimmed === '.' || $trimmed === ',') {
            return null;
        }

        if (preg_match('/^-?\d{1,3}(\.\d{3})*(,\d+)?$/', $trimmed) === 1) {
            $trimmed = str_replace('.', '', $trimmed);
            $trimmed = str_replace(',', '.', $trimmed);
        } elseif (preg_match('/^-?\d{1,3}(,\d{3})*(\.\d+)?$/', $trimmed) === 1) {
            $trimmed = str_replace(',', '', $trimmed);
        } else {
            $trimmed = str_replace(',', '.', $trimmed);
        }

        return is_numeric($trimmed) ? (float) $trimmed : null;
    }
}
