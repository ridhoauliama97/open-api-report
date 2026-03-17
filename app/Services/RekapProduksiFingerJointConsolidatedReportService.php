<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapProduksiFingerJointConsolidatedReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        $eps = 0.0000001;

        $normalized = array_map(function ($row) use ($eps): array {
            $item = (array) $row;

            $tanggal = (string) ($item['Tanggal'] ?? '');
            $namaMesin = trim((string) ($item['NamaMesin'] ?? ''));

            $ccaAkhir = $this->toFloat($item['CCAkhir'] ?? null);
            $s4s = $this->toFloat($item['S4S'] ?? null);

            // Output naming can vary across SPs; try common keys.
            $outputFj =
                $this->toFloat($item['OutputFJ'] ?? null)
                ?? $this->toFloat($item['OutPutFJ'] ?? null)
                ?? $this->toFloat($item['OutputFingerJoint'] ?? null)
                ?? $this->toFloat($item['Output'] ?? null);

            $jam = $this->toFloat($item['JamKerja'] ?? null);
            $org = (int) ($this->toFloat($item['JmlhAnggota'] ?? null) ?? 0.0);

            $totalInput = (float) ($ccaAkhir ?? 0.0) + (float) ($s4s ?? 0.0);

            $m3PerJam = ($jam !== null && abs($jam) > $eps && $outputFj !== null)
                ? $outputFj / $jam
                : null;

            $personHours = ($jam !== null && abs($jam) > $eps && $org > 0) ? ($jam * $org) : null;
            $m3PerJamOrg = ($personHours !== null && abs($personHours) > $eps && $outputFj !== null)
                ? $outputFj / $personHours
                : null;

            $rend = (abs($totalInput) > $eps && $outputFj !== null) ? (($outputFj / $totalInput) * 100.0) : null;

            return [
                'Tanggal' => $tanggal,
                'Shift' => (int) ($item['Shift'] ?? 0),
                'NamaMesin' => $namaMesin,
                'CCAkhir' => $ccaAkhir,
                'S4S' => $s4s,
                'TotalInput' => $totalInput,
                'OutputFJ' => $outputFj,
                'Jam' => $jam,
                'Org' => $org > 0 ? $org : null,
                'M3Jam' => $m3PerJam,
                'M3JamOrg' => $m3PerJamOrg,
                'Rend' => $rend,
                // For totals row: keep person-hours sum.
                '_person_hours' => $personHours,
            ];
        }, $rows);

        // Sort for stable display (NamaMesin, Tanggal, Shift).
        usort($normalized, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($a['NamaMesin'] ?? ''), (string) ($b['NamaMesin'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcmp((string) ($a['Tanggal'] ?? ''), (string) ($b['Tanggal'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return ((int) ($a['Shift'] ?? 0)) <=> ((int) ($b['Shift'] ?? 0));
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

        // Minimal required columns (some SPs can name output differently; we validate the core ones only).
        $required = [
            'Tanggal',
            'Shift',
            'NamaMesin',
            'JamKerja',
            'JmlhAnggota',
            'CCAkhir',
            'S4S',
        ];

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
        $connectionName = config('reports.rekap_produksi_finger_joint_consolidated.database_connection');
        $procedure = (string) config(
            'reports.rekap_produksi_finger_joint_consolidated.stored_procedure',
            'SP_LapRekapProduksiFingerJointConsolidated'
        );
        $syntax = (string) config('reports.rekap_produksi_finger_joint_consolidated.call_syntax', 'exec');
        $customQuery = config('reports.rekap_produksi_finger_joint_consolidated.query');
        $parameterCount = (int) config('reports.rekap_produksi_finger_joint_consolidated.parameter_count', 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap produksi Finger Joint consolidated belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        $allBindings = [$startDate, $endDate];
        $safeParameterCount = $parameterCount > 0 ? min($parameterCount, count($allBindings)) : 0;
        $bindings = array_slice($allBindings, 0, $safeParameterCount);

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap produksi Finger Joint consolidated dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PRODUKSI_FINGER_JOINT_CONSOLIDATED_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'REKAP_PRODUKSI_FINGER_JOINT_CONSOLIDATED_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan REKAP_PRODUKSI_FINGER_JOINT_CONSOLIDATED_REPORT_CALL_SYNTAX=query.',
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
