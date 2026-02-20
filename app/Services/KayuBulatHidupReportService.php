<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class KayuBulatHidupReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        $normalizedRows = array_map(static function ($row): array {
            $item = (array) $row;
            $masuk = is_numeric($item['Pcs'] ?? null) ? (float) $item['Pcs'] : 0.0;
            $terpakai = is_numeric($item['BlkTepakai'] ?? null) ? (float) $item['BlkTepakai'] : 0.0;

            return [
                'NoKayuBulat' => $item['NoKayuBulat'] ?? null,
                'Tanggal' => $item['DateCreate'] ?? null,
                'Supplier' => $item['NmSupplier'] ?? null,
                'NoTruk' => $item['NoTruk'] ?? null,
                'Jenis' => $item['Jenis'] ?? null,
                'Pcs' => $item['Pcs'] ?? null,
                'BlkTepakai' => $item['BlkTepakai'] ?? null,
                'BatangBalokMasuk' => $masuk,
                'BatangBalokTerpakai' => $terpakai,
                'FisikBatangBalokDiLapangan' => max($masuk - $terpakai, 0),
            ];
        }, $rows);

        usort($normalizedRows, static function (array $a, array $b): int {
            $dateA = (string) ($a['Tanggal'] ?? '');
            $dateB = (string) ($b['Tanggal'] ?? '');
            if ($dateA !== $dateB) {
                return $dateA <=> $dateB;
            }

            return ((int) ($a['NoTruk'] ?? 0)) <=> ((int) ($b['NoTruk'] ?? 0));
        });

        return $normalizedRows;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $summary = $this->buildSummary($rows);

        return [
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.kayu_bulat_hidup.expected_columns', []);
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
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function buildSummary(array $rows): array
    {
        $totalPcs = 0.0;
        $totalBlkTepakai = 0.0;
        $totalFisikLapangan = 0.0;

        foreach ($rows as $row) {
            $totalPcs += $this->toFloat($row['Pcs'] ?? 0) ?? 0.0;
            $totalBlkTepakai += $this->toFloat($row['BlkTepakai'] ?? 0) ?? 0.0;
            $totalFisikLapangan += $this->toFloat($row['FisikBatangBalokDiLapangan'] ?? 0) ?? 0.0;
        }

        return [
            'total_rows' => count($rows),
            'total_pcs' => $totalPcs,
            'total_blk_terpakai' => $totalBlkTepakai,
            'total_fisik_lapangan' => $totalFisikLapangan,
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.kayu_bulat_hidup.database_connection');
        $procedure = (string) config('reports.kayu_bulat_hidup.stored_procedure', 'SPWps_LapkayuBulatHidup');
        $syntax = (string) config('reports.kayu_bulat_hidup.call_syntax', 'exec');
        $customQuery = config('reports.kayu_bulat_hidup.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan kayu bulat hidup belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan kayu bulat hidup dikonfigurasi untuk SQL Server. '
                . 'Set KAYU_BULAT_HIDUP_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'KAYU_BULAT_HIDUP_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan KAYU_BULAT_HIDUP_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv' ? "EXEC {$procedure} ?, ?" : "CALL {$procedure}(?, ?)",
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

        $normalized = str_replace([' ', ','], ['', '.'], trim($value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
