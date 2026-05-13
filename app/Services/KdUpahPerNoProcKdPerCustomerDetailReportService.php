<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KdUpahPerNoProcKdPerCustomerDetailReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportData(array $filters): array
    {
        $noProcKd = $this->normalizeNoProcKd($filters['no_proc_kd'] ?? null);
        $rows = $this->fetch($noProcKd);

        if ($rows === []) {
            throw new RuntimeException("Data KD Upah Per-No.Proses KD Per-Customer Detail untuk {$noProcKd} tidak ditemukan.");
        }

        return [
            'filters' => [
                'no_proc_kd' => $noProcKd,
            ],
            'header' => $this->buildHeader($rows, $noProcKd),
            'rows' => $rows,
            'no_st_groups' => $this->buildNoStGroups($rows),
            'summary' => $this->buildSummary($rows),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function healthCheck(array $filters): array
    {
        $noProcKd = $this->normalizeNoProcKd($filters['no_proc_kd'] ?? null);
        $rows = $this->fetch($noProcKd);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.kd_upah_per_no_proc_kd_per_customer_detail.expected_columns', []);
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
    public function fetch(string $noProcKd): array
    {
        $rows = $this->runProcedureQuery($noProcKd);
        $normalized = [];

        foreach ($rows as $row) {
            $item = (array) $row;

            $normalized[] = [
                'NamaCustomer' => trim((string) ($item['NamaCustomer'] ?? '')),
                'NoProcKD' => trim((string) ($item['NoProcKD'] ?? '')),
                'NoRuangKD' => (int) round($this->toFloat($item['NoRuangKD'] ?? null) ?? 0.0),
                'TglMasuk' => $this->formatDateValue($item['TglMasuk'] ?? null),
                'TglKeluar' => $this->formatDateValue($item['TglKeluar'] ?? null),
                'NoST' => trim((string) ($item['NoST'] ?? '')),
                'Jenis' => trim((string) ($item['Jenis'] ?? '')),
                'Tebal' => round((float) ($this->toFloat($item['Tebal'] ?? null) ?? 0.0), 2),
                'Lebar' => round((float) ($this->toFloat($item['Lebar'] ?? null) ?? 0.0), 2),
                'Panjang' => round((float) ($this->toFloat($item['Panjang'] ?? null) ?? 0.0), 2),
                'JmlhBatang' => (int) round($this->toFloat($item['JmlhBatang'] ?? null) ?? 0.0),
                'M3' => round((float) ($this->toFloat($item['M3'] ?? null) ?? 0.0), 4),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function buildHeader(array $rows, string $noProcKd): array
    {
        $first = $rows[0] ?? [];

        return [
            'NamaCustomer' => $first['NamaCustomer'] ?? '',
            'NoProcKD' => $first['NoProcKD'] ?? $noProcKd,
            'NoRuangKD' => $first['NoRuangKD'] ?? '',
            'TglMasuk' => $first['TglMasuk'] ?? '',
            'TglKeluar' => $first['TglKeluar'] ?? '',
            'Jenis' => $first['Jenis'] ?? '',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildNoStGroups(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $noSt = (string) ($row['NoST'] !== '' ? $row['NoST'] : 'Tanpa No ST');

            if (! isset($groups[$noSt])) {
                $groups[$noSt] = [
                    'no_st' => $noSt,
                    'rows' => [],
                    'total_pcs' => 0,
                    'total_m3' => 0.0,
                ];
            }

            $groups[$noSt]['rows'][] = $row;
            $groups[$noSt]['total_pcs'] += (int) ($row['JmlhBatang'] ?? 0);
            $groups[$noSt]['total_m3'] += (float) ($row['M3'] ?? 0.0);
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
            'total_no_st' => count(array_unique(array_map(static fn (array $row): string => (string) ($row['NoST'] ?? ''), $rows))),
            'total_pcs' => array_sum(array_map(static fn (array $row): int => (int) ($row['JmlhBatang'] ?? 0), $rows)),
            'grand_total_m3' => round(array_sum(array_map(static fn (array $row): float => (float) ($row['M3'] ?? 0.0), $rows)), 4),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $noProcKd): array
    {
        $configKey = 'reports.kd_upah_per_no_proc_kd_per_customer_detail';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapKDUpahPerNoProcKDPerCustomerDetail');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan KD Upah Per-No.Proses KD Per-Customer Detail belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan KD Upah Per-No.Proses KD Per-Customer Detail dikonfigurasi untuk SQL Server. '
                .'Set KD_UPAH_PER_NO_PROC_KD_PER_CUSTOMER_DETAIL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan KD Upah Per-No.Proses KD Per-Customer Detail belum diisi.');

            return $connection->select($query, [$noProcKd]);
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?",
            'call' => "CALL {$procedure}(?)",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure} ?" : "CALL {$procedure}(?)",
        };

        try {
            return $connection->select($sql, [$noProcKd]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan KD Upah Per-No.Proses KD Per-Customer Detail: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function normalizeNoProcKd(mixed $value): string
    {
        $noProcKd = trim((string) ($value ?? ''));

        if ($noProcKd === '') {
            throw new RuntimeException('No.Proses KD wajib diisi.');
        }

        return $noProcKd;
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
