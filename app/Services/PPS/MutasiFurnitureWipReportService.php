<?php

namespace App\Services\PPS;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MutasiFurnitureWipReportService
{
    /**
     * Kolom standar dari output SP_PPSLapMutasiBarangJadi.
     *
     * @var array<int, string>
     */
    private const EXPECTED_COLUMNS = [
        'Nama',
        'Awal',
        'Masuk',
        'Keluar',
        'Akhir',
        'OutputInjc',
        'OutHStamp',
        'OutputPKunci',
        'OutputSpan',
        'InputBJSort',
        'InputHStamp',
        'InputPack',
        'InputPKunci',
        'InputSpaner',
        'InputBSU',
    ];

    /**
     * Kolom standar dari output SP_PPSLapSubMutasiBarangJadi.
     *
     * @var array<int, string>
     */
    private const EXPECTED_SUB_COLUMNS = [
        'DimType',
        'Jenis',
        'BeratInjctBroker',
        'BeratInjctMixer',
        'BeratInjcGili',
        'PcsInjcFWIP',
        'PcsHStamFWIP',
        'PcsPKunciFWIP',
        'PcsSpanFWIP',
        'PcsHStampMaterial',
        'PcsPkncMaterial',
        'PcsSPNMaterial',
        'PcsINJCMaterial',
    ];

    /**
     * Kolom standar dari output SP_PPSLapWasteMutasiBarangJadi.
     *
     * @var array<int, string>
     */
    private const EXPECTED_WASTE_COLUMNS = [
        'Jenis',
        'Berat',
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
        $rows = $this->runProcedureQuery($startDate, $endDate, 'sub');

        return $this->normalizeSubReportRows($this->normalizeRows($rows));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchWasteReport(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate, 'waste');

        return $this->normalizeWasteReportRows($this->normalizeRows($rows));
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
    private function runProcedureQuery(string $startDate, string $endDate, string $procedureType = 'main'): array
    {
        $connectionName = config('reports.pps_mutasi_furniture_wip.database_connection');
        $procedureConfig = match ($procedureType) {
            'sub' => 'reports.pps_mutasi_furniture_wip.sub_stored_procedure',
            'waste' => 'reports.pps_mutasi_furniture_wip.waste_stored_procedure',
            default => 'reports.pps_mutasi_furniture_wip.stored_procedure',
        };
        $queryConfig = match ($procedureType) {
            'sub' => 'reports.pps_mutasi_furniture_wip.sub_query',
            'waste' => 'reports.pps_mutasi_furniture_wip.waste_query',
            default => 'reports.pps_mutasi_furniture_wip.query',
        };
        $procedure = (string) config($procedureConfig);
        $syntax = (string) config('reports.pps_mutasi_furniture_wip.call_syntax', 'exec');
        $customQuery = config($queryConfig);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException(match ($procedureType) {
                'sub' => 'Stored procedure sub laporan PPS mutasi furniture WIP belum dikonfigurasi.',
                'waste' => 'Stored procedure waste laporan PPS mutasi furniture WIP belum dikonfigurasi.',
                default => 'Stored procedure laporan PPS mutasi furniture WIP belum dikonfigurasi.',
            });
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan PPS mutasi furniture WIP dikonfigurasi untuk SQL Server. '
                . 'Set PPS_MUTASI_FURNITURE_WIP_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'PPS_MUTASI_FURNITURE_WIP_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan PPS_MUTASI_FURNITURE_WIP_REPORT_CALL_SYNTAX=query.',
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
                'Output SP_PPSLapMutasiFurnitureWIP tidak sesuai. Kolom tidak ditemukan: '
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
                'Output SP_PPSLapSubMutasiFurnitureWIP tidak sesuai. Kolom tidak ditemukan: '
                . implode(', ', $missingColumns),
            );
        }

        return array_map(static function (array $row): array {
            $entry = [];
            foreach (self::EXPECTED_SUB_COLUMNS as $column) {
                $entry[$column] = $row[$column] ?? null;
            }

            return $entry;
        }, $rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeWasteReportRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $sample = $rows[0];
        $missingColumns = array_values(array_filter(
            self::EXPECTED_WASTE_COLUMNS,
            static fn(string $column): bool => !array_key_exists($column, $sample),
        ));

        if (!empty($missingColumns)) {
            throw new RuntimeException(
                'Output SP_PPSLapWasteMutasiFurnitureWIP tidak sesuai. Kolom tidak ditemukan: '
                . implode(', ', $missingColumns),
            );
        }

        return array_map(static function (array $row): array {
            return [
                'Jenis' => $row['Jenis'] ?? null,
                'Berat' => $row['Berat'] ?? null,
            ];
        }, $rows);
    }
}

