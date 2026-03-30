<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RendemenSemuaProsesReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);
        $eps = 0.0000001;

        $normalized = array_map(static function ($row) use ($eps): array {
            $item = (array) $row;
            $input = is_numeric($item['Input'] ?? null) ? (float) $item['Input'] : 0.0;
            $output = is_numeric($item['Output'] ?? null) ? (float) $item['Output'] : 0.0;
            $rendemen = abs($input) > $eps ? ($output / $input) * 100.0 : null;

            return [
                'Tanggal' => (string) ($item['Tanggal'] ?? ''),
                'Input' => $input,
                'Output' => $output,
                'Rendemen' => $rendemen,
                'GRP' => trim((string) ($item['GRP'] ?? 'LAINNYA')),
            ];
        }, $rows);

        usort($normalized, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($a['GRP'] ?? ''), (string) ($b['GRP'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) ($a['Tanggal'] ?? ''), (string) ($b['Tanggal'] ?? ''));
        });

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $groups = [];
        $grandTotals = ['Input' => 0.0, 'Output' => 0.0];
        $eps = 0.0000001;

        foreach ($rows as $row) {
            $group = $row['GRP'] ?: 'LAINNYA';
            if (!isset($groups[$group])) {
                $groups[$group] = [
                    'name' => $group,
                    'rows' => [],
                    'totals' => ['Input' => 0.0, 'Output' => 0.0, 'Rendemen' => null],
                ];
            }

            $groups[$group]['rows'][] = $row;
            $groups[$group]['totals']['Input'] += (float) ($row['Input'] ?? 0.0);
            $groups[$group]['totals']['Output'] += (float) ($row['Output'] ?? 0.0);
            $grandTotals['Input'] += (float) ($row['Input'] ?? 0.0);
            $grandTotals['Output'] += (float) ($row['Output'] ?? 0.0);
        }

        foreach ($groups as &$group) {
            $input = (float) ($group['totals']['Input'] ?? 0.0);
            $output = (float) ($group['totals']['Output'] ?? 0.0);
            $group['totals']['Rendemen'] = abs($input) > $eps ? ($output / $input) * 100.0 : null;
        }
        unset($group);

        $groupList = array_values($groups);
        usort($groupList, static fn(array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

        $grandTotals['Rendemen'] = abs($grandTotals['Input']) > $eps ? ($grandTotals['Output'] / $grandTotals['Input']) * 100.0 : null;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'groups' => $groupList,
            'summary' => [
                'total_rows' => count($rows),
                'total_groups' => count($groupList),
                'grand_totals' => $grandTotals,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);
        $first = (array) ($raw[0] ?? []);
        $detected = array_keys($first);
        $required = ['Tanggal', 'Input', 'Output', 'GRP'];

        return [
            'is_healthy' => empty(array_diff($required, $detected)),
            'required_columns' => $required,
            'detected_columns' => $detected,
            'missing_columns' => array_values(array_diff($required, $detected)),
            'row_count' => count($raw),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.rendemen_semua_proses';
        $connection = DB::connection(config("{$configKey}.database_connection") ?: null);
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapRekapRendemenSemuaProses');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rendemen semua proses belum dikonfigurasi.');
        }

        $driver = $connection->getDriverName();
        $bindings = array_slice([$startDate, $endDate], 0, max(0, min($parameterCount, 2)));

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException('Laporan rendemen semua proses dikonfigurasi untuk SQL Server.');
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('RENDMEN_SEMUA_PROSES_REPORT_QUERY belum diisi.');
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
}
