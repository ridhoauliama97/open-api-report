<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MutasiBarangJadiReportService
{
    /**
     * Kolom standar dari output SP_Mutasi_BarangJadi.
     *
     * @var array<int, string>
     */
    private const EXPECTED_COLUMNS = [
        'Jenis',
        'Awal',
        'Masuk',
        'AdjOutput',
        'BSOutput',
        'AdjInput',
        'BSInput',
        'Keluar',
        'Jual',
        'MLDInput',
        'LMTInput',
        'CCAInput',
        'SANDInput',
        'Akhir',
    ];

    /**
     * Kolom standar dari output SP_SubMutasi_BarangJadi.
     *
     * @var array<int, string>
     */
    private const EXPECTED_SUB_COLUMNS = [
        'Jenis',
        'BarangJadi',
        'Moulding',
        'Sanding',
        'WIP',
        'WIPLama',
        'CCAkhir',
    ];

    /**
     * @var array<int, string>
     */
    private const NUMERIC_COLUMNS = [
        'Awal',
        'Masuk',
        'AdjOutput',
        'BSOutput',
        'AdjInput',
        'BSInput',
        'Keluar',
        'Jual',
        'MLDInput',
        'LMTInput',
        'CCAInput',
        'SANDInput',
        'Akhir',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate, false);

        return $this->normalizeReportRows($this->normalizeRows($rows));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSubReport(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate, true);

        return $this->normalizeSubReportRows($this->normalizeRows($rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->normalizeRows($this->runProcedureQuery($startDate, $endDate, false));
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
        // Ubah setiap baris object dari database menjadi array asosiatif.
        return array_map(function ($row) {
            return (array) $row;
        }, $rows);
    }

    /**
     * @param array<int, string> $bindings
     * @return array<int, string>
     */
    private function resolveBindings(string $query, array $bindings): array
    {
        return str_contains($query, '?') ? $bindings : [];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate, bool $isSubProcedure): array
    {
        $connectionName = config('reports.mutasi_barang_jadi.database_connection');
        $procedure = (string) config(
            $isSubProcedure
                ? 'reports.mutasi_barang_jadi.sub_stored_procedure'
                : 'reports.mutasi_barang_jadi.stored_procedure'
        );
        $syntax = (string) config('reports.mutasi_barang_jadi.call_syntax', 'exec');
        $customQuery = config(
            $isSubProcedure
                ? 'reports.mutasi_barang_jadi.sub_query'
                : 'reports.mutasi_barang_jadi.query'
        );

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException(
                $isSubProcedure
                    ? 'Stored procedure sub laporan mutasi barang jadi belum dikonfigurasi.'
                    : 'Stored procedure laporan mutasi barang jadi belum dikonfigurasi.'
            );
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan mutasi barang jadi dikonfigurasi untuk SQL Server. '
                . 'Set MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'MUTASI_BARANG_JADI_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
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
        if (empty($rows)) {
            return [];
        }

        $sample = $rows[0];
        $missingColumns = array_values(array_filter(
            self::EXPECTED_COLUMNS,
            static fn(string $column): bool => !array_key_exists($column, $sample),
        ));

        if (!empty($missingColumns)) {
            throw new RuntimeException(
                'Output SP_Mutasi_BarangJadi tidak sesuai. Kolom tidak ditemukan: '
                . implode(', ', $missingColumns),
            );
        }

        $normalized = [];

        foreach ($rows as $row) {
            $entry = [];

            foreach (self::EXPECTED_COLUMNS as $column) {
                // Pertahankan nilai asli dari SP (termasuk NULL) agar output identik.
                $entry[$column] = $row[$column] ?? null;
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSubReportRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $sample = $rows[0];
        $missingColumns = array_values(array_filter(
            self::EXPECTED_SUB_COLUMNS,
            static fn(string $column): bool => !array_key_exists($column, $sample),
        ));

        if (!empty($missingColumns)) {
            throw new RuntimeException(
                'Output SP_SubMutasi_BarangJadi tidak sesuai. Kolom tidak ditemukan: '
                . implode(', ', $missingColumns),
            );
        }

        $normalized = [];

        foreach ($rows as $row) {
            $entry = [];

            foreach (self::EXPECTED_SUB_COLUMNS as $column) {
                $entry[$column] = $row[$column] ?? null;
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }
}

