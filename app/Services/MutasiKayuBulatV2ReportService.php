<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MutasiKayuBulatV2ReportService
{
    /**
     * @var array<int, string>
     */
    private const DEFAULT_SUB_COLUMNS = [
        'Jenis',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        [$startDate, $endDate] = $this->normalizeReportDates($startDate, $endDate);
        $rows = $this->runProcedureQuery($startDate, $endDate, false);

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSubReport(string $startDate, string $endDate): array
    {
        $subProcedure = (string) config('reports.mutasi_kayu_bulat_v2b.sub_stored_procedure', '');
        $subQuery = config('reports.mutasi_kayu_bulat_v2b.sub_query');

        $hasSubProcedure = trim($subProcedure) !== '';
        $hasSubQuery = is_string($subQuery) && trim($subQuery) !== '';

        if (!$hasSubProcedure && !$hasSubQuery) {
            return [];
        }

        [$startDate, $endDate] = $this->normalizeReportDates($startDate, $endDate);
        $rows = $this->normalizeRows($this->runProcedureQuery($startDate, $endDate, true));
        $configuredColumns = config('reports.mutasi_kayu_bulat_v2b.expected_sub_columns', []);
        $configuredColumns = is_array($configuredColumns) ? array_values($configuredColumns) : [];
        $expectedColumns = $configuredColumns !== [] ? $configuredColumns : self::DEFAULT_SUB_COLUMNS;

        if ($rows === []) {
            return [];
        }

        $sample = $rows[0];
        $missingColumns = array_values(array_filter(
            $expectedColumns,
            static fn(string $column): bool => !array_key_exists($column, $sample),
        ));

        if ($missingColumns !== []) {
            throw new RuntimeException(
                'Output sub report kayu bulat V2 tidak sesuai. Kolom tidak ditemukan: '
                . implode(', ', $missingColumns),
            );
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.mutasi_kayu_bulat_v2b.expected_columns', []);
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
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @param string $query
     * @param array<int, string> $bindings
     * @return array<int, string>|array<string, string>
     */
    private function resolveBindings(string $query, array $bindings): array
    {
        if (str_contains($query, '?')) {
            return $bindings;
        }

        $namedBindings = [
            'start_date' => $bindings[0] ?? null,
            'end_date' => $bindings[1] ?? null,
            'TglAwal' => $bindings[0] ?? null,
            'TglAkhir' => $bindings[1] ?? null,
            ':start_date' => $bindings[0] ?? null,
            ':end_date' => $bindings[1] ?? null,
            ':TglAwal' => $bindings[0] ?? null,
            ':TglAkhir' => $bindings[1] ?? null,
        ];

        return array_filter($namedBindings, static fn($value): bool => $value !== null);
    }

    /**
     * @param string $startDate
     * @param string $endDate
     * @return array{0: string, 1: string}
     */
    private function normalizeReportDates(string $startDate, string $endDate): array
    {
        return [
            Carbon::parse($startDate)->toDateString(),
            Carbon::parse($endDate)->toDateString(),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate, bool $isSubProcedure): array
    {
        $connectionName = config('reports.mutasi_kayu_bulat_v2b.database_connection');
        $procedure = (string) config(
            $isSubProcedure
            ? 'reports.mutasi_kayu_bulat_v2b.sub_stored_procedure'
            : 'reports.mutasi_kayu_bulat_v2b.stored_procedure'
        );
        $syntax = (string) config('reports.mutasi_kayu_bulat_v2b.call_syntax', 'exec');
        $customQuery = config(
            $isSubProcedure
            ? 'reports.mutasi_kayu_bulat_v2b.sub_query'
            : 'reports.mutasi_kayu_bulat_v2b.query'
        );

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException(
                $isSubProcedure
                ? 'Stored procedure sub laporan mutasi kayu bulat V2 belum dikonfigurasi.'
                : 'Stored procedure laporan mutasi kayu bulat V2 belum dikonfigurasi.',
            );
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan mutasi kayu bulat V2 dikonfigurasi untuk SQL Server. '
                . 'Set MUTASI_KAYU_BULAT_V2B_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'MUTASI_KAYU_BULAT_V2B_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan MUTASI_KAYU_BULAT_V2B_REPORT_CALL_SYNTAX=query '
                    . 'atau MUTASI_KAYU_BULAT_V2B_SUB_REPORT_QUERY untuk sub report.',
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
}

