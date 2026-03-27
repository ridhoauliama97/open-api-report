<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class UmurSandingDetailReportService
{
    /**
     * @param array{Umur1:int,Umur2:int,Umur3:int,Umur4:int} $parameters
     * @return array<int, array<string, mixed>>
     */
    public function fetch(array $parameters): array
    {
        $rows = $this->runProcedureQuery($parameters);
        $eps = 0.0000001;

        $normalizedRows = array_map(function ($row): array {
            $item = (array) $row;
            $jenis = trim((string) ($item['Jenis'] ?? ''));
            $grade = trim((string) ($item['NamaGrade'] ?? ''));
            $jenisDisplay = $jenis !== ''
                ? trim($jenis . ($grade !== '' ? ' - ' . $grade : ''))
                : $grade;

            $p1 = $this->toFloat($item['Period1'] ?? null) ?? 0.0;
            $p2 = $this->toFloat($item['Period2'] ?? null) ?? 0.0;
            $p3 = $this->toFloat($item['Period3'] ?? null) ?? 0.0;
            $p4 = $this->toFloat($item['Period4'] ?? null) ?? 0.0;
            $p5 = $this->toFloat($item['Period5'] ?? null) ?? 0.0;
            $total = $p1 + $p2 + $p3 + $p4 + $p5;

            return [
                'Jenis' => $jenisDisplay !== '' ? $jenisDisplay : null,
                'Tebal' => $this->toFloat($item['Tebal'] ?? null),
                'Lebar' => $this->toFloat($item['Lebar'] ?? null),
                'Panjang' => $this->toFloat($item['Panjang'] ?? null),
                'Period1' => $p1,
                'Period2' => $p2,
                'Period3' => $p3,
                'Period4' => $p4,
                'Period5' => $p5,
                'Total' => $this->toFloat($item['Total'] ?? null) ?? $total,
                '_keep' => $jenisDisplay !== '',
            ];
        }, $rows);

        $grouped = [];
        foreach ($normalizedRows as $row) {
            $jenisKey = strtoupper(trim((string) ($row['Jenis'] ?? '')));
            $tebalKey = $row['Tebal'] === null ? '' : (string) $row['Tebal'];
            $lebarKey = $row['Lebar'] === null ? '' : (string) $row['Lebar'];
            $panjangKey = $row['Panjang'] === null ? '' : (string) $row['Panjang'];
            $key = implode('|', [$jenisKey, $tebalKey, $lebarKey, $panjangKey]);

            if (!isset($grouped[$key])) {
                $grouped[$key] = $row;
                continue;
            }

            foreach (['Period1', 'Period2', 'Period3', 'Period4', 'Period5'] as $col) {
                $grouped[$key][$col] = (float) ($grouped[$key][$col] ?? 0.0) + (float) ($row[$col] ?? 0.0);
            }

            $grouped[$key]['Total'] =
                (float) ($grouped[$key]['Period1'] ?? 0.0)
                + (float) ($grouped[$key]['Period2'] ?? 0.0)
                + (float) ($grouped[$key]['Period3'] ?? 0.0)
                + (float) ($grouped[$key]['Period4'] ?? 0.0)
                + (float) ($grouped[$key]['Period5'] ?? 0.0);
        }

        $normalizedRows = array_values($grouped);

        $normalizedRows = array_values(array_filter($normalizedRows, static function (array $row) use ($eps): bool {
            $hasJenis = trim((string) ($row['Jenis'] ?? '')) !== '' && ($row['_keep'] ?? false) === true;
            $total = (float) ($row['Total'] ?? 0.0);

            return $hasJenis && abs($total) > $eps;
        }));

        $normalizedRows = array_map(static function (array $row): array {
            unset($row['_keep']);
            return $row;
        }, $normalizedRows);

        usort($normalizedRows, function (array $a, array $b): int {
            $aJenis = strtoupper(trim((string) ($a['Jenis'] ?? '')));
            $bJenis = strtoupper(trim((string) ($b['Jenis'] ?? '')));

            if ($aJenis !== $bJenis) {
                return strcmp($aJenis, $bJenis);
            }

            $cmpNullableFloat = static function (?float $x, ?float $y): int {
                if ($x === null && $y === null) {
                    return 0;
                }
                if ($x === null) {
                    return 1;
                }
                if ($y === null) {
                    return -1;
                }

                return $x <=> $y;
            };

            $cmp = $cmpNullableFloat($a['Tebal'] ?? null, $b['Tebal'] ?? null);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = $cmpNullableFloat($a['Lebar'] ?? null, $b['Lebar'] ?? null);
            if ($cmp !== 0) {
                return $cmp;
            }

            return $cmpNullableFloat($a['Panjang'] ?? null, $b['Panjang'] ?? null);
        });

        return $normalizedRows;
    }

    /**
     * @param array{Umur1:int,Umur2:int,Umur3:int,Umur4:int} $parameters
     * @return array<string, mixed>
     */
    public function healthCheck(array $parameters): array
    {
        $rows = $this->fetch($parameters);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.umur_sanding_detail.expected_columns', []);
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
     * @param array{Umur1:int,Umur2:int,Umur3:int,Umur4:int} $parameters
     * @return array<int, object>
     */
    private function runProcedureQuery(array $parameters): array
    {
        $connectionName = config('reports.umur_sanding_detail.database_connection');
        $procedure = (string) config('reports.umur_sanding_detail.stored_procedure', 'SP_LapUmurSanding');
        $syntax = (string) config('reports.umur_sanding_detail.call_syntax', 'exec');
        $customQuery = config('reports.umur_sanding_detail.query');
        $parameterCount = (int) config('reports.umur_sanding_detail.parameter_count', 4);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan umur Sanding belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $allBindings = [$parameters['Umur1'], $parameters['Umur2'], $parameters['Umur3'], $parameters['Umur4']];
        $safeParameterCount = $parameterCount > 0 ? min($parameterCount, count($allBindings)) : 0;
        $bindings = array_slice($allBindings, 0, $safeParameterCount);

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan umur Sanding dikonfigurasi untuk SQL Server. '
                . 'Set UMUR_SANDING_DETAIL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'UMUR_SANDING_DETAIL_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan UMUR_SANDING_DETAIL_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => $this->buildExecSql($procedure, $safeParameterCount),
            'call' => $this->buildCallSql($procedure, $safeParameterCount),
            default => $driver === 'sqlsrv'
                ? $this->buildExecSql($procedure, $safeParameterCount)
                : $this->buildCallSql($procedure, $safeParameterCount),
        };

        return $connection->select($sql, $bindings);
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

        $normalized = str_replace(',', '', $normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
