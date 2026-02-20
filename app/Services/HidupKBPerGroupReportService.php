<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class HidupKBPerGroupReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();
        $totalTon = 0.0;

        foreach ($rows as $row) {
            $totalTon += $this->toFloat($row['Ton'] ?? 0) ?? 0.0;
        }

        $rowsWithRatio = array_map(function (array $row) use ($totalTon): array {
            $ton = $this->toFloat($row['Ton'] ?? 0) ?? 0.0;
            $ratio = $totalTon > 0 ? ($ton / $totalTon) * 100 : 0.0;
            $row['Rasio'] = $ratio;

            return $row;
        }, $rows);

        return [
            'rows' => $rowsWithRatio,
            'summary' => [
                'total_rows' => count($rowsWithRatio),
                'total_ton' => $totalTon,
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
        $expectedColumns = config('reports.hidup_kb_per_group.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(): array
    {
        $connectionName = config('reports.hidup_kb_per_group.database_connection');
        $procedure = (string) config('reports.hidup_kb_per_group.stored_procedure', 'sp_LapHidupKBPerGroup');
        $syntax = (string) config('reports.hidup_kb_per_group.call_syntax', 'exec');
        $customQuery = config('reports.hidup_kb_per_group.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan hidup KB per group belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan hidup KB per group dikonfigurasi untuk SQL Server. '
                . 'Set HIDUP_KB_PER_GROUP_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'HIDUP_KB_PER_GROUP_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan HIDUP_KB_PER_GROUP_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure}",
            'call' => "CALL {$procedure}()",
            default => $driver === 'sqlsrv' ? "EXEC {$procedure}" : "CALL {$procedure}()",
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

        $normalized = str_replace([' ', ','], ['', '.'], trim($value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
