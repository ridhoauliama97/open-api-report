<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MutasiBarangJadiPerJenisPerUkuranReportService
{
    /**
     * @var array<int, string>
     */
    private const EXPECTED_COLUMNS = [
        'Jenis',
        'Tebal',
        'Lebar',
        'Panjang',
        'AwalPcs',
        'AwalM3',
        'MasukPcs',
        'MasukM3',
        'MinusPcs',
        'MinusM3',
        'JualPcs',
        'JualM3',
        'AkhirPcs',
        'AkhirM3',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return $this->normalizeReportRows($this->normalizeRows($rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->normalizeRows($this->runProcedureQuery($startDate, $endDate));
        $detectedColumns = array_keys($rows[0] ?? []);
        $missingColumns = array_values(array_diff(self::EXPECTED_COLUMNS, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, self::EXPECTED_COLUMNS));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => self::EXPECTED_COLUMNS,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
        ];
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.mutasi_barang_jadi_per_jenis_per_ukuran.database_connection');
        $procedure = (string) config('reports.mutasi_barang_jadi_per_jenis_per_ukuran.stored_procedure');
        $syntax = (string) config('reports.mutasi_barang_jadi_per_jenis_per_ukuran.call_syntax', 'exec');
        $customQuery = config('reports.mutasi_barang_jadi_per_jenis_per_ukuran.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan mutasi barang jadi per-jenis per-ukuran belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan mutasi barang jadi per-jenis per-ukuran dikonfigurasi untuk SQL Server. '
                . 'Set MUTASI_BARANG_JADI_PER_JENIS_PER_UKURAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'MUTASI_BARANG_JADI_PER_JENIS_PER_UKURAN_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan MUTASI_BARANG_JADI_PER_JENIS_PER_UKURAN_REPORT_CALL_SYNTAX=query.',
                );

            $resolvedBindings = str_contains($query, '?') ? $bindings : [];

            return $connection->select($query, $resolvedBindings);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "EXEC {$procedure} ?, ?"
                : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, $bindings);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeReportRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $sample = $rows[0];
        $missingColumns = array_values(array_filter(
            self::EXPECTED_COLUMNS,
            static fn(string $column): bool => !array_key_exists($column, $sample),
        ));

        if ($missingColumns !== []) {
            throw new RuntimeException(
                'Output SP_LapMutasiBJPerJenisPerUkuran tidak sesuai. Kolom tidak ditemukan: '
                . implode(', ', $missingColumns),
            );
        }

        $normalized = [];

        foreach ($rows as $row) {
            $entry = [];
            foreach (self::EXPECTED_COLUMNS as $column) {
                $entry[$column] = $row[$column] ?? null;
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }
}
