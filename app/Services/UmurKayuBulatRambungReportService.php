<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UmurKayuBulatRambungReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.umur_kayu_bulat_rambung.expected_columns', []);
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
        return array_map(function ($row): array {
            $item = (array) $row;

            $status = is_numeric($item['Status'] ?? null) ? (string) (int) $item['Status'] : (string) ($item['Status'] ?? '');
            $dateCreate = $this->parseDate($item['DateCreate'] ?? null);
            $tanggalRacip = $this->parseDate($item['TanggalRacip'] ?? null);
            $dateUsage = $this->parseDate($item['DateUsage'] ?? null);

            $lamaRacip = null;
            if ($dateCreate && $tanggalRacip) {
                $lamaRacip = $dateCreate->diffInDays($tanggalRacip, false);
                $lamaRacip = $lamaRacip >= 0 ? $lamaRacip : null;
            }

            // Lama tunggu dihitung dalam hari:
            // - Status 1 (sudah mati): dari tanggal masuk ke DateUsage
            // - Status 0 (masih hidup): dari tanggal masuk ke tanggal hari ini
            $anchorDate = $status === '1' ? $dateUsage : Carbon::today();
            $lamaTunggu = null;
            if ($dateCreate && $anchorDate) {
                $lamaTunggu = $dateCreate->diffInDays($anchorDate, false);
                $lamaTunggu = $lamaTunggu >= 0 ? $lamaTunggu : null;
            }

            return [
                'Status' => $status,
                'No.KB' => $item['NoKayuBulat'] ?? null,
                'Tanggal' => $dateCreate?->toDateString() ?? ($item['DateCreate'] ?? null),
                'Nama Supplier' => $item['NmSupplier'] ?? null,
                'Jenis Kayu' => $item['Jenis'] ?? null,
                'Truk' => $item['NoTruk'] ?? null,
                'Ton' => $this->toFloat($item['TonKBKG'] ?? null),
                'Tanggal Racip' => $tanggalRacip?->toDateString() ?? ($item['TanggalRacip'] ?? null),
                'Lama Racip' => $lamaRacip,
                'Lama Tunggu' => $lamaTunggu,
            ];
        }, $rows);
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.umur_kayu_bulat_rambung.database_connection');
        $procedure = (string) config('reports.umur_kayu_bulat_rambung.stored_procedure', 'SPWps_LapUmurKayuBulatRambung');
        $syntax = (string) config('reports.umur_kayu_bulat_rambung.call_syntax', 'exec');
        $customQuery = config('reports.umur_kayu_bulat_rambung.query');
        $parameterCount = (int) config('reports.umur_kayu_bulat_rambung.parameter_count', 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan umur kayu bulat (rambung) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $allBindings = [$startDate, $endDate];
        $safeParameterCount = $parameterCount > 0 ? min($parameterCount, count($allBindings)) : 0;
        $bindings = array_slice($allBindings, 0, $safeParameterCount);

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan umur kayu bulat (rambung) dikonfigurasi untuk SQL Server. '
                . 'Set UMUR_KAYU_BULAT_RAMBUNG_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'UMUR_KAYU_BULAT_RAMBUNG_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan UMUR_KAYU_BULAT_RAMBUNG_REPORT_CALL_SYNTAX=query.',
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

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }
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

