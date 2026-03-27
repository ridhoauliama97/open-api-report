<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapProduksiSandingPerJenisPerGradeReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        $normalized = array_map(function ($row): array {
            $item = (array) $row;

            return [
                'Jenis' => trim((string) ($item['Jenis'] ?? $item['JenisKayu'] ?? $item['GroupKayu'] ?? '')) ?: null,
                'NamaGrade' => trim((string) ($item['NamaGrade'] ?? $item['Grade'] ?? '')) ?: null,
                'InFJ' => $this->toFloat($item['FJ'] ?? $item['InFJ'] ?? null),
                'InMoulding' => $this->toFloat($item['Moulding'] ?? $item['InMoulding'] ?? null),
                'InCCAkhir' => $this->toFloat($item['CCAkhir'] ?? $item['InCCAkhir'] ?? null),
                'InWIP' => $this->toFloat($item['WIP'] ?? $item['InWIP'] ?? null),
                'InReproses' => $this->toFloat($item['Reproses'] ?? $item['InReproses'] ?? null),
                'Output' => $this->toFloat($item['Output'] ?? $item['OutPut'] ?? null),
            ];
        }, $rows);

        usort($normalized, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($a['Jenis'] ?? ''), (string) ($b['Jenis'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) ($a['NamaGrade'] ?? ''), (string) ($b['NamaGrade'] ?? ''));
        });

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);
        $first = (array) ($raw[0] ?? []);
        $detected = array_keys($first);

        $required = ['Jenis', 'NamaGrade'];
        $missing = array_values(array_diff($required, $detected));

        return [
            'is_healthy' => empty($missing),
            'required_columns' => $required,
            'detected_columns' => $detected,
            'missing_columns' => $missing,
            'row_count' => count($raw),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.rekap_produksi_sanding_per_jenis_per_grade.database_connection');
        $procedure = (string) config(
            'reports.rekap_produksi_sanding_per_jenis_per_grade.stored_procedure',
            'SP_LapRekapProduksiSandingPerJenisPerGrade',
        );
        $syntax = (string) config('reports.rekap_produksi_sanding_per_jenis_per_grade.call_syntax', 'exec');
        $customQuery = config('reports.rekap_produksi_sanding_per_jenis_per_grade.query');
        $parameterCount = (int) config('reports.rekap_produksi_sanding_per_jenis_per_grade.parameter_count', 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap produksi Sanding per-jenis dan per-grade belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        $allBindings = [$startDate, $endDate];
        $safeParameterCount = $parameterCount > 0 ? min($parameterCount, count($allBindings)) : 0;
        $bindings = array_slice($allBindings, 0, $safeParameterCount);

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap produksi Sanding per-jenis dan per-grade dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PRODUKSI_SANDING_PER_JENIS_PER_GRADE_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'REKAP_PRODUKSI_SANDING_PER_JENIS_PER_GRADE_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan REKAP_PRODUKSI_SANDING_PER_JENIS_PER_GRADE_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => $this->buildExecSql($procedure, $safeParameterCount),
            'call' => $this->buildCallSql($procedure, $safeParameterCount),
            default => $driver === 'sqlsrv'
                ? $this->buildExecSql($procedure, $safeParameterCount)
                : $this->buildCallSql($procedure, $safeParameterCount),
        };

        return $connection->select("SET NOCOUNT ON; {$sql}", $bindings);
    }

    private function buildExecSql(string $procedure, int $parameterCount): string
    {
        if ($parameterCount <= 0) {
            return "EXEC {$procedure}";
        }

        return "EXEC {$procedure} " . implode(', ', array_fill(0, $parameterCount, '?'));
    }

    private function buildCallSql(string $procedure, int $parameterCount): string
    {
        if ($parameterCount <= 0) {
            return "CALL {$procedure}()";
        }

        return "CALL {$procedure}(" . implode(', ', array_fill(0, $parameterCount, '?')) . ")";
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '' || $normalized === '-') {
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
