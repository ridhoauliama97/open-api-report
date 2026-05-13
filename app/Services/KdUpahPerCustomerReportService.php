<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KdUpahPerCustomerReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();

        if ($rows === []) {
            throw new RuntimeException('Data KD Upah Per-Customer tidak ditemukan.');
        }

        return [
            'rows' => $rows,
            'customer_groups' => $this->buildCustomerGroups($rows),
            'summary' => $this->buildSummary($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $rows = $this->fetch();
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.kd_upah_per_customer.expected_columns', []);
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
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();
        $normalized = [];

        foreach ($rows as $row) {
            $item = (array) $row;

            $normalized[] = [
                'NamaCustomer' => trim((string) ($item['NamaCustomer'] ?? '')),
                'NoProcKD' => trim((string) ($item['NoProcKD'] ?? '')),
                'NoRuangKD' => (int) round($this->toFloat($item['NoRuangKD'] ?? null) ?? 0.0),
                'TglMasuk' => $this->formatDateValue($item['TglMasuk'] ?? null),
                'TglKeluar' => $this->formatDateValue($item['TglKeluar'] ?? null),
                'Jenis' => trim((string) ($item['Jenis'] ?? '')),
                'm3' => round((float) ($this->toFloat($item['m3'] ?? null) ?? 0.0), 4),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildCustomerGroups(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $customer = (string) ($row['NamaCustomer'] !== '' ? $row['NamaCustomer'] : 'Tanpa Customer');

            if (! isset($groups[$customer])) {
                $groups[$customer] = [
                    'customer' => $customer,
                    'rows' => [],
                    'total_m3' => 0.0,
                ];
            }

            $groups[$customer]['rows'][] = $row;
            $groups[$customer]['total_m3'] += (float) ($row['m3'] ?? 0.0);
        }

        foreach ($groups as &$group) {
            $group['total_m3'] = round((float) $group['total_m3'], 4);
        }
        unset($group);

        return array_values($groups);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function buildSummary(array $rows): array
    {
        return [
            'total_rows' => count($rows),
            'total_customers' => count(array_unique(array_map(static fn (array $row): string => (string) ($row['NamaCustomer'] ?? ''), $rows))),
            'grand_total_m3' => round(array_sum(array_map(static fn (array $row): float => (float) ($row['m3'] ?? 0.0), $rows)), 4),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(): array
    {
        $configKey = 'reports.kd_upah_per_customer';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapKDUpahPerCutomer');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan KD Upah Per-Customer belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan KD Upah Per-Customer dikonfigurasi untuk SQL Server. '
                .'Set KD_UPAH_PER_CUSTOMER_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan KD Upah Per-Customer belum diisi.');

            return $connection->select($query);
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure}",
            'call' => "CALL {$procedure}()",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure}" : "CALL {$procedure}()",
        };

        try {
            return $connection->select($sql);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan KD Upah Per-Customer: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function formatDateValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim(str_replace(' ', '', $value));
        if ($normalized === '') {
            return null;
        }

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
