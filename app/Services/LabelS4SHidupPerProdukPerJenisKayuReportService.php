<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class LabelS4SHidupPerProdukPerJenisKayuReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();

        $normalized = array_map(function ($row): array {
            $item = (array) $row;

            return [
                'Jenis' => trim((string) ($item['Jenis'] ?? '')) ?: null,
                'Produk' => trim((string) ($item['Produk'] ?? '')) ?: null,
                'NamaGrade' => trim((string) ($item['NamaGrade'] ?? $item['Grade'] ?? '')) ?: null,
                'Tebal' => $this->toFloat($item['Tebal'] ?? null),
                'Lebar' => $this->toFloat($item['Lebar'] ?? null),
                'Panjang' => $this->toFloat($item['Panjang'] ?? null),
                'Pcs' => $this->toInt($item['JmlhBatang'] ?? $item['Pcs'] ?? null),
                'Kubik' => $this->toFloat($item['Kubik'] ?? $item['M3'] ?? null),
                // Keep raw dates for potential debugging.
                'DateCreate' => (string) ($item['DateCreate'] ?? ''),
                'DateUsage' => (string) ($item['DateUsage'] ?? ''),
            ];
        }, $rows);

        // Sort to match reference: Jenis asc, Produk asc, NamaGrade asc, Tebal/Lebar/Panjang asc.
        usort($normalized, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($a['Jenis'] ?? ''), (string) ($b['Jenis'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcmp((string) ($a['Produk'] ?? ''), (string) ($b['Produk'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcmp((string) ($a['NamaGrade'] ?? ''), (string) ($b['NamaGrade'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmpFloat = static function (?float $x, ?float $y): int {
                if ($x === null && $y === null) {
                    return 0;
                }
                if ($x === null) {
                    return 1;
                }
                if ($y === null) {
                    return -1;
                }
                return $x <=> $y;
            };

            $cmp = $cmpFloat($a['Tebal'] ?? null, $b['Tebal'] ?? null);
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = $cmpFloat($a['Lebar'] ?? null, $b['Lebar'] ?? null);
            if ($cmp !== 0) {
                return $cmp;
            }
            return $cmpFloat($a['Panjang'] ?? null, $b['Panjang'] ?? null);
        });

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $raw = $this->runProcedureQuery();
        $first = (array) ($raw[0] ?? []);
        $detected = array_keys($first);

        $required = ['Jenis', 'Produk', 'NamaGrade', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Kubik'];
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
    private function runProcedureQuery(): array
    {
        $connectionName = config('reports.label_s4s_hidup_per_produk_per_jenis_kayu.database_connection');
        $procedure = (string) config(
            'reports.label_s4s_hidup_per_produk_per_jenis_kayu.stored_procedure',
            'SP_LapS4SHidupPerProdukdanPerJenis',
        );
        $syntax = (string) config('reports.label_s4s_hidup_per_produk_per_jenis_kayu.call_syntax', 'exec');
        $customQuery = config('reports.label_s4s_hidup_per_produk_per_jenis_kayu.query');
        $parameterCount = (int) config('reports.label_s4s_hidup_per_produk_per_jenis_kayu.parameter_count', 0);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan label S4S (hidup) per-produk dan per-jenis kayu belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        $safeParameterCount = $parameterCount > 0 ? $parameterCount : 0;
        $bindings = [];

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan label S4S (hidup) per-produk dan per-jenis kayu dikonfigurasi untuk SQL Server. '
                . 'Set LABEL_S4S_HIDUP_PER_PRODUK_PER_JENIS_KAYU_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'LABEL_S4S_HIDUP_PER_PRODUK_PER_JENIS_KAYU_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan LABEL_S4S_HIDUP_PER_PRODUK_PER_JENIS_KAYU_REPORT_CALL_SYNTAX=query.',
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

    private function toInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        $f = $this->toFloat($value);
        if ($f === null) {
            return null;
        }

        return (int) round($f);
    }
}

