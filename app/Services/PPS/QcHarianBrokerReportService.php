<?php

namespace App\Services\PPS;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class QcHarianBrokerReportService
{
    public function fetchByDate(string $reportDate): array
    {
        $rows = $this->runProcedureQuery($reportDate);

        return array_map(function ($row): array {
            $item = (array) $row;

            return [
                'DateCreate' => (string) ($item['DateCreate'] ?? ''),
                'NamaMesin' => (string) ($item['NamaMesin'] ?? ''),
                'Shift' => (string) ($item['Shift'] ?? ''),
                'Jenis' => (string) ($item['Jenis'] ?? ''),
                'NoBroker' => (string) ($item['NoBroker'] ?? ''),
                'Moisture' => $this->toFloat($item['Moisture'] ?? null),
                'Moisture2' => $this->toFloat($item['Moisture2'] ?? null),
                'Moisture3' => $this->toFloat($item['Moisture3'] ?? null),
                'Density' => $this->toFloat($item['Density'] ?? null),
                'Density2' => $this->toFloat($item['Density2'] ?? null),
                'Density3' => $this->toFloat($item['Density3'] ?? null),
                'MFI' => $this->toFloat($item['MFI'] ?? null),
                'VisualNote' => $this->normalizeText($item['VisualNote'] ?? ''),
            ];
        }, $rows);
    }

    public function healthCheck(string $reportDate): array
    {
        $rows = $this->fetchByDate($reportDate);
        $detectedColumns = array_values(array_intersect(
            ['DateCreate', 'NamaMesin', 'Shift', 'Jenis', 'NoBroker', 'Moisture', 'Moisture2', 'Moisture3', 'Density', 'Density2', 'Density3', 'MFI', 'VisualNote'],
            array_keys($rows[0] ?? []),
        ));
        $expectedColumns = config('reports.pps_qc_harian_broker.expected_columns', []);
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

    private function runProcedureQuery(string $reportDate): array
    {
        $configPath = 'reports.pps_qc_harian_broker';
        $connectionName = config("{$configPath}.database_connection");
        $procedure = (string) config("{$configPath}.stored_procedure");
        $syntax = (string) config("{$configPath}.call_syntax", 'exec');
        $customQuery = config("{$configPath}.query");
        $parameterName = (string) config("{$configPath}.single_parameter_name", 'EndDate');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan QC Harian Broker belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = [$reportDate];

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan QC Harian Broker dikonfigurasi untuk SQL Server. '
                . 'Set PPS_QC_HARIAN_BROKER_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'PPS_QC_HARIAN_BROKER_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan PPS_QC_HARIAN_BROKER_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $parameterName = ltrim($parameterName, '@');
        $sql = $syntax === 'call'
            ? "CALL {$procedure}(?)"
            : "SET NOCOUNT ON; EXEC {$procedure} @{$parameterName} = ?";

        return $connection->select($sql, $bindings);
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace([',', ' '], ['.', ''], trim($value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function normalizeText(mixed $value): string
    {
        $text = trim((string) $value);

        return strtoupper($text) === 'NULL' ? '' : $text;
    }
}
