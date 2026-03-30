<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapProduksiBarangJadiConsolidatedReportService
{
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);
        $eps = 0.0000001;

        $normalized = array_map(function ($row) use ($eps): array {
            $item = (array) $row;
            $bj = $this->toFloat($item['BJ'] ?? null);
            $moulding = $this->toFloat($item['Moulding'] ?? null);
            $sanding = $this->toFloat($item['Sanding'] ?? null);
            $wip = $this->toFloat($item['WIP'] ?? $item['Wip'] ?? null);
            $outputPacking = $this->toFloat($item['OutputPacking'] ?? null);
            $outputReproses = $this->toFloat($item['OutputReproses'] ?? null);
            $jam = $this->toFloat($item['JamKerja'] ?? null);
            $org = (int) ($this->toFloat($item['JmlhAnggota'] ?? null) ?? 0.0);

            $totalInput = (float) ($bj ?? 0.0) + (float) ($moulding ?? 0.0) + (float) ($sanding ?? 0.0) + (float) ($wip ?? 0.0);
            $totalOutput = (float) ($outputPacking ?? 0.0) + (float) ($outputReproses ?? 0.0);
            $m3PerJam = ($jam !== null && abs($jam) > $eps && abs($totalOutput) > $eps) ? $totalOutput / $jam : null;
            $personHours = ($jam !== null && abs($jam) > $eps && $org > 0) ? ($jam * $org) : null;
            $m3PerJamOrg = ($personHours !== null && abs($personHours) > $eps && abs($totalOutput) > $eps) ? $totalOutput / $personHours : null;
            $rend = abs($totalInput) > $eps && abs($totalOutput) > $eps ? ($totalOutput / $totalInput) * 100.0 : null;

            return [
                'Tanggal' => (string) ($item['Tanggal'] ?? ''),
                'Shift' => (int) ($item['Shift'] ?? 0),
                'NamaMesin' => trim((string) ($item['NamaMesin'] ?? '')),
                'BJ' => $bj,
                'Moulding' => $moulding,
                'Sanding' => $sanding,
                'Wip' => $wip,
                'TotalInput' => $totalInput,
                'OutputPacking' => $outputPacking,
                'OutputReproses' => $outputReproses,
                'TotalOutput' => $totalOutput,
                'Jam' => $jam !== null ? (int) round($jam) : null,
                'Org' => $org > 0 ? $org : null,
                'M3Jam' => $m3PerJam,
                'M3JamOrg' => $m3PerJamOrg,
                'Rend' => $rend,
            ];
        }, $rows);

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

    public function healthCheck(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);
        $first = (array) ($raw[0] ?? []);
        $detected = array_keys($first);
        $required = ['Tanggal', 'Shift', 'NamaMesin', 'JamKerja', 'JmlhAnggota', 'BJ', 'Moulding', 'Sanding', 'WIP', 'OutputPacking', 'OutputReproses'];

        return [
            'is_healthy' => empty(array_diff($required, $detected)),
            'required_columns' => $required,
            'detected_columns' => $detected,
            'missing_columns' => array_values(array_diff($required, $detected)),
            'row_count' => count($raw),
        ];
    }

    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connection = DB::connection(config('reports.rekap_produksi_barang_jadi_consolidated.database_connection') ?: null);
        $procedure = (string) config('reports.rekap_produksi_barang_jadi_consolidated.stored_procedure', 'SP_LapRekapProduksiBarangJadiConsolidated');
        $syntax = (string) config('reports.rekap_produksi_barang_jadi_consolidated.call_syntax', 'exec');
        $customQuery = config('reports.rekap_produksi_barang_jadi_consolidated.query');
        $parameterCount = (int) config('reports.rekap_produksi_barang_jadi_consolidated.parameter_count', 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap produksi Packing consolidated belum dikonfigurasi.');
        }

        $driver = $connection->getDriverName();
        $bindings = array_slice([$startDate, $endDate], 0, max(0, min($parameterCount, 2)));

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException('Laporan rekap produksi Packing consolidated dikonfigurasi untuk SQL Server.');
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== '' ? $customQuery : throw new RuntimeException('REKAP_PRODUKSI_BARANG_JADI_CONSOLIDATED_REPORT_QUERY belum diisi.');
            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = $parameterCount > 0
            ? "SET NOCOUNT ON; EXEC {$procedure} " . implode(', ', array_fill(0, count($bindings), '?'))
            : "SET NOCOUNT ON; EXEC {$procedure}";

        return $connection->select($sql, $bindings);
    }

    private function toFloat(mixed $value): ?float
    {
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
