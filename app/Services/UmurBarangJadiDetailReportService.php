<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class UmurBarangJadiDetailReportService
{
    public function fetch(array $parameters): array
    {
        $rows = $this->runProcedureQuery($parameters);
        $eps = 0.0000001;

        $normalizedRows = array_map(function ($row): array {
            $item = (array) $row;

            $jenis = trim((string) ($item['Jenis'] ?? ''));
            $barangJadi = trim((string) ($item['NamaBarangJadi'] ?? ''));
            $jenisDisplay = $jenis !== ''
                ? trim($jenis . ($barangJadi !== '' ? ' - ' . $barangJadi : ''))
                : $barangJadi;

            $p1 = $this->toFloat($item['Period1'] ?? null) ?? 0.0;
            $p2 = $this->toFloat($item['Period2'] ?? null) ?? 0.0;
            $p3 = $this->toFloat($item['Period3'] ?? null) ?? 0.0;
            $p4 = $this->toFloat($item['Period4'] ?? null) ?? 0.0;
            $p5 = $this->toFloat($item['Period5'] ?? null) ?? 0.0;
            $total = $this->toFloat($item['Total'] ?? null) ?? ($p1 + $p2 + $p3 + $p4 + $p5);

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
                'Total' => $total,
                '_keep' => $jenisDisplay !== '',
            ];
        }, $rows);

        $grouped = [];
        foreach ($normalizedRows as $row) {
            $key = implode('|', [
                strtoupper(trim((string) ($row['Jenis'] ?? ''))),
                $row['Tebal'] === null ? '' : (string) $row['Tebal'],
                $row['Lebar'] === null ? '' : (string) $row['Lebar'],
                $row['Panjang'] === null ? '' : (string) $row['Panjang'],
            ]);

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

        $normalizedRows = array_values(array_filter(array_values($grouped), static function (array $row) use ($eps): bool {
            return trim((string) ($row['Jenis'] ?? '')) !== ''
                && ($row['_keep'] ?? false) === true
                && abs((float) ($row['Total'] ?? 0.0)) > $eps;
        }));

        $normalizedRows = array_map(static function (array $row): array {
            unset($row['_keep']);
            return $row;
        }, $normalizedRows);

        usort($normalizedRows, function (array $a, array $b): int {
            $cmp = strcmp(strtoupper(trim((string) ($a['Jenis'] ?? ''))), strtoupper(trim((string) ($b['Jenis'] ?? ''))));
            if ($cmp !== 0) {
                return $cmp;
            }
            foreach (['Tebal', 'Lebar', 'Panjang'] as $field) {
                $x = $a[$field] ?? null;
                $y = $b[$field] ?? null;
                if ($x === null && $y === null) {
                    continue;
                }
                if ($x === null) {
                    return 1;
                }
                if ($y === null) {
                    return -1;
                }
                $cmp = $x <=> $y;
                if ($cmp !== 0) {
                    return $cmp;
                }
            }
            return 0;
        });

        return $normalizedRows;
    }

    public function healthCheck(array $parameters): array
    {
        $rows = $this->fetch($parameters);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.umur_barang_jadi_detail.expected_columns', []);
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

    private function runProcedureQuery(array $parameters): array
    {
        $connection = DB::connection(config('reports.umur_barang_jadi_detail.database_connection') ?: null);
        $procedure = (string) config('reports.umur_barang_jadi_detail.stored_procedure', 'SP_LapUmurBarangJadi');
        $syntax = (string) config('reports.umur_barang_jadi_detail.call_syntax', 'exec');
        $customQuery = config('reports.umur_barang_jadi_detail.query');
        $parameterCount = (int) config('reports.umur_barang_jadi_detail.parameter_count', 4);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan umur Barang Jadi belum dikonfigurasi.');
        }

        $driver = $connection->getDriverName();
        $allBindings = [$parameters['Umur1'], $parameters['Umur2'], $parameters['Umur3'], $parameters['Umur4']];
        $safeParameterCount = $parameterCount > 0 ? min($parameterCount, count($allBindings)) : 0;
        $bindings = array_slice($allBindings, 0, $safeParameterCount);

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException('Laporan umur Barang Jadi dikonfigurasi untuk SQL Server.');
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('UMUR_BARANG_JADI_DETAIL_REPORT_QUERY belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => $safeParameterCount > 0 ? "EXEC {$procedure} " . implode(', ', array_fill(0, $safeParameterCount, '?')) : "EXEC {$procedure}",
            'call' => $safeParameterCount > 0 ? "CALL {$procedure}(" . implode(', ', array_fill(0, $safeParameterCount, '?')) . ")" : "CALL {$procedure}()",
            default => $driver === 'sqlsrv'
                ? ($safeParameterCount > 0 ? "EXEC {$procedure} " . implode(', ', array_fill(0, $safeParameterCount, '?')) : "EXEC {$procedure}")
                : ($safeParameterCount > 0 ? "CALL {$procedure}(" . implode(', ', array_fill(0, $safeParameterCount, '?')) . ")" : "CALL {$procedure}()"),
        };

        return $connection->select($sql, $bindings);
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $normalized = trim(str_replace(',', '', $value));
        return $normalized !== '' && is_numeric($normalized) ? (float) $normalized : null;
    }
}
