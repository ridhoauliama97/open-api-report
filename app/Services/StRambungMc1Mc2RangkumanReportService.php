<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StRambungMc1Mc2RangkumanReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();

        /** @var array<string, array<string, array{pcs:int, ton:float, kubik:float}>> $byJenisTable */
        $byJenisTable = [];
        foreach ($rows as $r) {
            $jenis = trim((string) ($r['JenisKayu'] ?? ''));
            $jenis = $jenis !== '' ? $jenis : '-';
            $label = trim((string) ($r['IsKering'] ?? ''));
            $label = $label !== '' ? $label : $jenis;

            $byJenisTable[$jenis] ??= [];
            $byJenisTable[$jenis][$label] ??= ['pcs' => 0, 'ton' => 0.0, 'kubik' => 0.0];

            $byJenisTable[$jenis][$label]['pcs'] += (int) ($r['Pcs'] ?? 0);
            $byJenisTable[$jenis][$label]['ton'] += (float) ($r['Ton'] ?? 0.0);
            $byJenisTable[$jenis][$label]['kubik'] += (float) ($r['Kubik'] ?? 0.0);
        }

        ksort($byJenisTable);

        $tableSummaries = [];
        foreach ($byJenisTable as $jenis => $tables) {
            ksort($tables);
            foreach ($tables as $label => $tot) {
                $tableSummaries[] = [
                    'jenis' => $jenis,
                    'tabel' => $label,
                    'pcs' => (int) ($tot['pcs'] ?? 0),
                    'ton' => (float) ($tot['ton'] ?? 0.0),
                    'kubik' => (float) ($tot['kubik'] ?? 0.0),
                ];
            }
        }

        $groupSummaries = [];
        foreach ($byJenisTable as $jenis => $tables) {
            $pcs = 0;
            $ton = 0.0;
            $kubik = 0.0;
            foreach ($tables as $tot) {
                $pcs += (int) ($tot['pcs'] ?? 0);
                $ton += (float) ($tot['ton'] ?? 0.0);
                $kubik += (float) ($tot['kubik'] ?? 0.0);
            }
            $groupSummaries[] = [
                'jenis' => $jenis,
                'pcs' => $pcs,
                'ton' => $ton,
                'kubik' => $kubik,
            ];
        }

        $grand = $this->sumTotals($rows);

        return [
            'rows' => $rows,
            'summary_tables' => [
                'tables' => $tableSummaries,
                'groups' => $groupSummaries,
                'grand' => $grand,
            ],
            'summary' => [
                'total_rows' => count($rows),
                'total_groups' => count($groupSummaries),
                'total_tables' => count($tableSummaries),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $raw = $this->runProcedureQuery();
        $first = (array) ($raw[0] ?? []);
        $detectedColumns = array_keys($first);
        $expectedColumns = config('reports.st_rambung_mc1_mc2_rangkuman.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($raw),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(): array
    {
        $raw = $this->runProcedureQuery();

        $out = [];
        foreach ($raw as $row) {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            $out[] = [
                'JenisKayu' => (string) ($item['JenisKayu'] ?? ''),
                'IsKering' => (string) ($item['IsKering'] ?? ''),
                'Tebal' => (float) ($this->toFloat($item['Tebal'] ?? null) ?? 0.0),
                'Lebar' => (float) ($this->toFloat($item['Lebar'] ?? null) ?? 0.0),
                'Panjang' => (float) ($this->toFloat($item['Panjang'] ?? null) ?? 0.0),
                'Pcs' => (int) ($this->toFloat($item['Pcs'] ?? null) ?? 0.0),
                'Ton' => (float) ($this->toFloat($item['Ton'] ?? null) ?? 0.0),
                'Kubik' => (float) ($this->toFloat($item['Kubik'] ?? null) ?? 0.0),
            ];
        }

        return array_values($out);
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $t = trim($value);
        if ($t === '') {
            return null;
        }
        $t = str_replace(',', '', $t);
        if (!is_numeric($t)) {
            return null;
        }

        return (float) $t;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{pcs:int, ton:float, kubik:float}
     */
    private function sumTotals(array $rows): array
    {
        $pcs = 0;
        $ton = 0.0;
        $kubik = 0.0;

        foreach ($rows as $r) {
            $pcs += (int) ($r['Pcs'] ?? 0);
            $ton += (float) ($r['Ton'] ?? 0.0);
            $kubik += (float) ($r['Kubik'] ?? 0.0);
        }

        return [
            'pcs' => $pcs,
            'ton' => $ton,
            'kubik' => $kubik,
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(): array
    {
        $configKey = 'reports.st_rambung_mc1_mc2_rangkuman';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapSTRambungMC1danMC2Rangkuman');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 0);

        if ($parameterCount !== 0) {
            throw new RuntimeException('Laporan ST Rambung MC1 & MC2 (Rangkuman) tidak menggunakan parameter.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan ST Rambung MC1 & MC2 (Rangkuman) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan ST Rambung MC1 & MC2 (Rangkuman) dikonfigurasi untuk SQL Server. '
                . 'Set ST_RAMBUNG_MC1_MC2_RANGKUMAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan ST Rambung MC1 & MC2 (Rangkuman) belum diisi.');

            return $connection->select($query);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure}",
            'call' => "CALL {$procedure}()",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure}" : "CALL {$procedure}()",
        };

        return $connection->select($sql);
    }
}

