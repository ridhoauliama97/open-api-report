<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class UmurSawnTimberDetailTonReportService
{
    /**
     * @param array{Umur1:int,Umur2:int,Umur3:int,Umur4:int} $parameters
     * @return array<int, array<string, mixed>>
     */
    public function fetch(array $parameters): array
    {
        $rows = $this->runProcedureQuery($parameters);

        return array_map(function ($row): array {
            $item = (array) $row;

            return [
                'Jenis' => $item['Jenis'] ?? null,
                'Tebal' => $this->toFloat($item['Tebal'] ?? null),
                'Lebar' => $this->toFloat($item['Lebar'] ?? null),
                'Panjang' => $this->toFloat($item['Panjang'] ?? null),
                'Period1' => $this->toFloat($item['Period1'] ?? null),
                'Period2' => $this->toFloat($item['Period2'] ?? null),
                'Period3' => $this->toFloat($item['Period3'] ?? null),
                'Period4' => $this->toFloat($item['Period4'] ?? null),
                'Period5' => $this->toFloat($item['Period5'] ?? null),
            ];
        }, $rows);
    }

    /**
     * @param array{Umur1:int,Umur2:int,Umur3:int,Umur4:int} $parameters
     * @return array<string, mixed>
     */
    public function healthCheck(array $parameters): array
    {
        $rows = $this->fetch($parameters);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.umur_sawn_timber_detail_ton.expected_columns', []);
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
     * @param array{Umur1:int,Umur2:int,Umur3:int,Umur4:int} $parameters
     * @return array<int, object>
     */
    private function runProcedureQuery(array $parameters): array
    {
        $connectionName = config('reports.umur_sawn_timber_detail_ton.database_connection');
        $procedure = (string) config('reports.umur_sawn_timber_detail_ton.stored_procedure', 'SPWps_LapUmurST');
        $syntax = (string) config('reports.umur_sawn_timber_detail_ton.call_syntax', 'exec');
        $customQuery = config('reports.umur_sawn_timber_detail_ton.query');
        $parameterCount = (int) config('reports.umur_sawn_timber_detail_ton.parameter_count', 4);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan umur sawn timber detail (ton) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $allBindings = [
            $parameters['Umur1'],
            $parameters['Umur2'],
            $parameters['Umur3'],
            $parameters['Umur4'],
        ];
        $safeParameterCount = $parameterCount > 0 ? min($parameterCount, count($allBindings)) : 0;
        $bindings = array_slice($allBindings, 0, $safeParameterCount);

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan umur sawn timber detail (ton) dikonfigurasi untuk SQL Server. '
                . 'Set UMUR_SAWN_TIMBER_DETAIL_TON_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'UMUR_SAWN_TIMBER_DETAIL_TON_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan UMUR_SAWN_TIMBER_DETAIL_TON_REPORT_CALL_SYNTAX=query.',
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
