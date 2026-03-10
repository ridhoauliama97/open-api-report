<?php

namespace App\Services\PPS;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MutasiBonggolanReportService
{
    /**
     * @var array<int, string>
     */
    private const EXPECTED_COLUMNS = [
        'NamaBonggolan',
        'BeratAwal',
        'BeratMasuk',
        'BeratKeluar',
        'BeratAkhir',
        'BeratADJKeluar',
        'BeratBSUKeluar',
        'BeratCRUSKeluar',
        'BeratBROKMasuk',
        'BeratINJCMasuk',
        'BeratADJMasuk',
        'BeratBSUMasuk',
        'BeratGILKeluar',
        'KeluarNot',
        'MasukNot',
    ];

    /**
     * @var array<int, string>
     */
    private const EXPECTED_SUB_COLUMNS = [
        'NamaBonggolan',
        'Berat',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate, 'main');

        return $this->normalizeReportRows($this->normalizeRows($rows));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSubReport(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate, 'sub');

        return $this->normalizeSubReportRows($this->normalizeRows($rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->normalizeRows($this->runProcedureQuery($startDate, $endDate, 'main'));
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
    private function runProcedureQuery(string $startDate, string $endDate, string $procedureType = 'main'): array
    {
        $connectionName = config('reports.pps_mutasi_bonggolan.database_connection');
        $procedureConfig = $procedureType === 'sub'
            ? 'reports.pps_mutasi_bonggolan.sub_stored_procedure'
            : 'reports.pps_mutasi_bonggolan.stored_procedure';
        $queryConfig = $procedureType === 'sub'
            ? 'reports.pps_mutasi_bonggolan.sub_query'
            : 'reports.pps_mutasi_bonggolan.query';
        $procedure = (string) config($procedureConfig);
        $syntax = (string) config('reports.pps_mutasi_bonggolan.call_syntax', 'exec');
        $customQuery = config($queryConfig);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException(
                $procedureType === 'sub'
                    ? 'Stored procedure sub laporan PPS mutasi bonggolan belum dikonfigurasi.'
                    : 'Stored procedure laporan PPS mutasi bonggolan belum dikonfigurasi.'
            );
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan PPS mutasi bonggolan dikonfigurasi untuk SQL Server. '
                . 'Set PPS_MUTASI_BONGGOLAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'PPS_MUTASI_BONGGOLAN_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan PPS_MUTASI_BONGGOLAN_REPORT_CALL_SYNTAX=query.',
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
                'Output SP_PPSLapMutasiBonggolan tidak sesuai. Kolom tidak ditemukan: '
                . implode(', ', $missingColumns),
            );
        }

        return array_map(static function (array $row): array {
            $entry = [];
            foreach (self::EXPECTED_COLUMNS as $column) {
                $entry[$column] = $row[$column] ?? null;
            }

            return $entry;
        }, $rows);
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
                'Output SP_PPSLapSubMutasiBonggolan tidak sesuai. Kolom tidak ditemukan: '
                . implode(', ', $missingColumns),
            );
        }

        return array_map(static function (array $row): array {
            return [
                'NamaBonggolan' => $row['NamaBonggolan'] ?? null,
                'Berat' => $row['Berat'] ?? null,
            ];
        }, $rows);
    }
}
