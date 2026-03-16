<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StRambungMc1Mc2DetailReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();

        /** @var array<string, array<int, array<string, mixed>>> $byJenis */
        $byJenis = [];
        foreach ($rows as $row) {
            $jenis = trim((string) ($row['JenisKayu'] ?? ''));
            $jenis = $jenis !== '' ? $jenis : '-';
            $byJenis[$jenis][] = $row;
        }

        ksort($byJenis);

        $groups = [];
        foreach ($byJenis as $jenis => $items) {
            /** @var array<string, array<int, array<string, mixed>>> $bySub */
            $bySub = [];
            foreach ($items as $r) {
                $label = trim((string) ($r['IsKering'] ?? ''));
                $label = $label !== '' ? $label : $jenis;
                $bySub[$label][] = $r;
            }
            ksort($bySub);

            $subgroups = [];
            foreach ($bySub as $label => $subItems) {
                usort($subItems, static function (array $a, array $b): int {
                    $c = strcmp((string) ($a['NoST'] ?? ''), (string) ($b['NoST'] ?? ''));
                    if ($c !== 0) {
                        return $c;
                    }
                    $c = ((float) ($a['Tebal'] ?? 0.0)) <=> ((float) ($b['Tebal'] ?? 0.0));
                    if ($c !== 0) {
                        return $c;
                    }
                    return ((float) ($a['Lebar'] ?? 0.0)) <=> ((float) ($b['Lebar'] ?? 0.0));
                });

                $tableTotals = $this->sumTotals($subItems);

                $subgroups[] = [
                    'label' => $label,
                    'rows' => array_values($subItems),
                    'totals' => $tableTotals,
                ];
            }

            $groupTotals = $this->sumTotals($items);

            $groups[] = [
                'jenis' => $jenis,
                'subgroups' => $subgroups,
                'totals' => $groupTotals,
            ];
        }

        $tableSummaries = [];
        foreach ($groups as $g) {
            $jenis = (string) ($g['jenis'] ?? '-');
            $subgroups = is_array($g['subgroups'] ?? null) ? $g['subgroups'] : [];
            foreach ($subgroups as $sg) {
                $label = (string) ($sg['label'] ?? '');
                $tot = is_array($sg['totals'] ?? null) ? $sg['totals'] : [];
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
        foreach ($groups as $g) {
            $jenis = (string) ($g['jenis'] ?? '-');
            $tot = is_array($g['totals'] ?? null) ? $g['totals'] : [];
            $groupSummaries[] = [
                'jenis' => $jenis,
                'pcs' => (int) ($tot['pcs'] ?? 0),
                'ton' => (float) ($tot['ton'] ?? 0.0),
                'kubik' => (float) ($tot['kubik'] ?? 0.0),
            ];
        }

        $grandTotals = $this->sumTotals($rows);

        return [
            'groups' => $groups,
            'summary_tables' => [
                'tables' => $tableSummaries,
                'groups' => $groupSummaries,
                'grand' => $grandTotals,
            ],
            'summary' => [
                'total_groups' => count($groups),
                'total_rows' => count($rows),
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
        $expectedColumns = config('reports.st_rambung_mc1_mc2_detail.expected_columns', []);
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
                'NoST' => (string) ($item['NoST'] ?? ''),
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
        $configKey = 'reports.st_rambung_mc1_mc2_detail';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapSTRambungMC1danMC2Detail');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 0);

        if ($parameterCount !== 0) {
            throw new RuntimeException('Laporan ST Rambung MC1 & MC2 (Detail) tidak menggunakan parameter.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan ST Rambung MC1 & MC2 (Detail) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan ST Rambung MC1 & MC2 (Detail) dikonfigurasi untuk SQL Server. '
                . 'Set ST_RAMBUNG_MC1_MC2_DETAIL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan ST Rambung MC1 & MC2 (Detail) belum diisi.');

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
