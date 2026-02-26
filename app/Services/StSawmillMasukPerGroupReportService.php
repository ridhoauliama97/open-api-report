<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StSawmillMasukPerGroupReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_map(function ($row): array {
            $item = (array) $row;

            return [
                'Group' => $item['Group'] ?? null,
                'Jenis' => $item['Jenis'] ?? null,
                'Tebal' => $this->toFloat($item['Tebal'] ?? null),
                'STTon' => $this->toFloat($item['STTon'] ?? null),
            ];
        }, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);

        $groups = [];
        $grandTotalTon = 0.0;

        foreach ($rows as $row) {
            $groupName = trim((string) ($row['Group'] ?? ''));
            $groupName = $groupName !== '' ? $groupName : 'Tanpa Group';

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'name' => $groupName,
                    'rows' => [],
                    'total_ton' => 0.0,
                ];
            }

            $ton = (float) ($row['STTon'] ?? 0.0);
            $groups[$groupName]['rows'][] = $row;
            $groups[$groupName]['total_ton'] += $ton;
            $grandTotalTon += $ton;
        }

        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'groups' => array_values($groups),
            'summary' => [
                'total_rows' => count($rows),
                'total_groups' => count($groups),
                'grand_total_ton' => $grandTotalTon,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.st_sawmill_masuk_per_group.expected_columns', []);
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
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.st_sawmill_masuk_per_group.database_connection');
        $procedure = (string) config('reports.st_sawmill_masuk_per_group.stored_procedure', 'SPWps_LapSTMasukPerGroup');
        $syntax = (string) config('reports.st_sawmill_masuk_per_group.call_syntax', 'exec');
        $customQuery = config('reports.st_sawmill_masuk_per_group.query');
        $parameterCount = (int) config('reports.st_sawmill_masuk_per_group.parameter_count', 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan ST (Sawmill) masuk per-group belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $allBindings = [$startDate, $endDate];
        $safeParameterCount = $parameterCount > 0 ? min($parameterCount, count($allBindings)) : 0;
        $bindings = array_slice($allBindings, 0, $safeParameterCount);

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan ST (Sawmill) masuk per-group dikonfigurasi untuk SQL Server. '
                . 'Set ST_SAWMILL_MASUK_PER_GROUP_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'ST_SAWMILL_MASUK_PER_GROUP_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan ST_SAWMILL_MASUK_PER_GROUP_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sqlWithParameters = match ($syntax) {
            'exec' => $this->buildExecSql($procedure, $safeParameterCount),
            'call' => $this->buildCallSql($procedure, $safeParameterCount),
            default => $driver === 'sqlsrv'
                ? $this->buildExecSql($procedure, $safeParameterCount)
                : $this->buildCallSql($procedure, $safeParameterCount),
        };

        return $connection->select($sqlWithParameters, $bindings);
    }

    private function buildExecSql(string $procedure, int $parameterCount): string
    {
        if ($parameterCount <= 0) {
            return "EXEC {$procedure}";
        }

        $placeholders = implode(', ', array_fill(0, $parameterCount, '?'));

        return "EXEC {$procedure} {$placeholders}";
    }

    private function buildCallSql(string $procedure, int $parameterCount): string
    {
        if ($parameterCount <= 0) {
            return "CALL {$procedure}()";
        }

        $placeholders = implode(', ', array_fill(0, $parameterCount, '?'));

        return "CALL {$procedure}({$placeholders})";
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $normalized);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
