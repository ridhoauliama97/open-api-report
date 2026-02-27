<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockSTKeringReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $endDate): array
    {
        $cacheTtl = (int) config('reports.stock_st_kering.cache_ttl_seconds', 60);
        if ($cacheTtl <= 0) {
            $rows = $this->runProcedureQuery($endDate);

            return array_map(static fn($row): array => (array) $row, $rows);
        }

        $cacheKey = sprintf(
            'report:stock_st_kering:%s:%s:%s',
            $endDate,
            (string) config('reports.stock_st_kering.call_syntax', 'exec'),
            md5((string) config('reports.stock_st_kering.stored_procedure', 'SP_LapStockSTKering') . '|' . (string) config('reports.stock_st_kering.query', '')),
        );
        $rows = Cache::remember($cacheKey, now()->addSeconds($cacheTtl), fn(): array => $this->runProcedureQuery($endDate));

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $endDate): array
    {
        $rows = $this->fetch($endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.stock_st_kering.expected_columns', []);
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
    private function runProcedureQuery(string $endDate): array
    {
        $connectionName = config('reports.stock_st_kering.database_connection');
        $procedure = (string) config('reports.stock_st_kering.stored_procedure', 'SP_LapStockSTKering');
        $syntax = (string) config('reports.stock_st_kering.call_syntax', 'exec');
        $customQuery = config('reports.stock_st_kering.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Stock ST kering belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan stock ST kering dikonfigurasi untuk SQL Server. '
                . 'Set STOCK_ST_KERING_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'STOCK_ST_KERING_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan STOCK_ST_KERING_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} ?",
            'call' => "CALL {$procedure}(?)",
            default => $driver === 'sqlsrv' ? "EXEC {$procedure} ?" : "CALL {$procedure}(?)",
        };

        return $connection->select($sql, $bindings);
    }
}

