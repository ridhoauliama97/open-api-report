<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class QcSawmillReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);
        return array_map(fn(object $row): array => $this->normalizeRow((array) $row), $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);

        if ($rows === []) {
            throw new RuntimeException('Data QC Sawmill tidak ditemukan untuk rentang tanggal yang dipilih.');
        }

        $qcNumbers = [];
        $mejas = [];
        $groups = [];

        foreach ($rows as $row) {
            $noQc = trim((string) ($row['NoQc'] ?? ''));
            if ($noQc !== '') {
                $qcNumbers[$noQc] = true;
            }

            $meja = trim((string) ($row['NamaMeja'] ?? ''));
            if ($meja !== '') {
                $mejas[$meja] = true;
            }

            $groupKey = implode('|', [
                (string) ($row['QcTgl'] ?? ''),
                (string) ($row['QcNoMeja'] ?? ''),
                (string) ($row['NamaMeja'] ?? ''),
            ]);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'tanggal' => (string) ($row['QcTgl'] ?? ''),
                    'no_meja' => (int) ($row['QcNoMeja'] ?? 0),
                    'nama_meja' => (string) ($row['NamaMeja'] ?? ''),
                    'rows' => [],
                    'summary' => [
                        'total_rows' => 0,
                        'total_accurate' => 0,
                        'avg_deviation_tebal' => 0.0,
                        'avg_deviation_lebar' => 0.0,
                        'accurate_rate' => 0.0,
                    ],
                ];
            }

            $groups[$groupKey]['rows'][] = $row;
        }

        $groups = array_values($groups);
        foreach ($groups as &$group) {
            $groupRows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $totalRows = count($groupRows);
            $sumDeviationTebal = array_sum(array_map(static fn(array $row): float => (float) ($row['DeviationTebal'] ?? 0), $groupRows));
            $sumDeviationLebar = array_sum(array_map(static fn(array $row): float => (float) ($row['DeviationLebar'] ?? 0), $groupRows));
            $totalAccurate = count(array_filter($groupRows, static fn(array $row): bool => (bool) ($row['IsAccurate'] ?? false)));

            $group['summary'] = [
                'total_rows' => $totalRows,
                'total_accurate' => $totalAccurate,
                'avg_deviation_tebal' => $totalRows > 0 ? $sumDeviationTebal / $totalRows : 0.0,
                'avg_deviation_lebar' => $totalRows > 0 ? $sumDeviationLebar / $totalRows : 0.0,
                'accurate_rate' => $totalRows > 0 ? ($totalAccurate / $totalRows) * 100 : 0.0,
            ];
        }
        unset($group);

        return [
            'rows' => $rows,
            'groups' => $groups,
            'summary' => [
                'total_rows' => count($rows),
                'total_documents' => count($qcNumbers),
                'total_meja' => count($mejas),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.qc_sawmill.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($rows),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.qc_sawmill';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapQCSawmill');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan QC Sawmill belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan QC Sawmill dikonfigurasi untuk SQL Server. '
                . 'Set QC_SAWMILL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan QC Sawmill belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$startDate, $endDate] : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure laporan QC Sawmill tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?"
                : "CALL {$procedure}(?, ?)",
        };

        try {
            return $connection->select($sql, [$startDate, $endDate]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan QC Sawmill: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $cuttingTebal = $this->toFloat($row['CuttingTebal'] ?? null) ?? 0.0;
        $cuttingLebar = $this->toFloat($row['CuttingLebar'] ?? null) ?? 0.0;
        $actualTebal = $this->toFloat($row['ActualTebal'] ?? null) ?? 0.0;
        $actualLebar = $this->toFloat($row['ActualLebar'] ?? null) ?? 0.0;
        $deviationTebal = $actualTebal - $cuttingTebal;
        $deviationLebar = $actualLebar - $cuttingLebar;
        $hasNegativeDeviation = $deviationTebal < -0.00001 || $deviationLebar < -0.00001;
        $hasExceededTolerance = $deviationTebal >= 2 || $deviationLebar >= 2;
        $isAccurate = !$hasNegativeDeviation && !$hasExceededTolerance;

        return [
            'NoQc' => trim((string) ($row['NoQc'] ?? '')),
            'QcTgl' => trim((string) ($row['QcTgl'] ?? '')),
            'QcNoMeja' => (int) round($this->toFloat($row['QcNoMeja'] ?? null) ?? 0),
            'NamaMeja' => trim((string) ($row['NamaMeja'] ?? '')),
            'CuttingTebal' => $cuttingTebal,
            'CuttingLebar' => $cuttingLebar,
            'ActualTebal' => $actualTebal,
            'ActualLebar' => $actualLebar,
            'DeviationTebal' => $deviationTebal,
            'DeviationLebar' => $deviationLebar,
            'IsAccurate' => $isAccurate,
            'Accurate' => $isAccurate ? 'Yes' : 'No',
        ];
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
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
