<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StHidupKeringReportService
{
    /**
     * @param  array<int, string>  $modes
     * @return array<string, mixed>
     */
    public function buildReportData(int $hari, array $modes): array
    {
        $modes = $this->normalizeModes($modes);
        $rows = $this->sortRowsForDisplay($this->fetch($hari, $modes));
        $jenisGroups = $this->buildJenisGroups($rows);

        return [
            'rows' => $rows,
            'jenis_groups' => $jenisGroups,
            'summary' => [
                'total_rows' => count($rows),
                'total_jenis' => count($jenisGroups),
                'hari' => $hari,
                'include' => in_array('INCLUDE', $modes, true),
                'exclude' => in_array('EXCLUDE', $modes, true),
                'modes' => $modes,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(int $hari, array $modes): array
    {
        $modes = $this->normalizeModes($modes);
        $raw = $this->runProcedureQueries($hari, $modes);
        $first = (array) ($raw[0] ?? []);
        $detectedColumns = array_keys($first);
        $expectedColumns = config('reports.st_hidup_kering.expected_columns', []);
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
            'include' => in_array('INCLUDE', $modes, true),
            'exclude' => in_array('EXCLUDE', $modes, true),
            'modes' => $modes,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(int $hari, array $modes): array
    {
        $raw = $this->runProcedureQueries($hari, $modes);

        $out = [];
        foreach ($raw as $row) {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            $out[] = [
                'NoST' => (string) ($item['NoST'] ?? ''),
                'Tebal' => (float) ($this->toFloat($item['Tebal'] ?? null) ?? 0.0),
                'Lebar' => (float) ($this->toFloat($item['Lebar'] ?? null) ?? 0.0),
                'JmlhBatang' => (int) ($this->toFloat($item['JmlhBatang'] ?? null) ?? 0.0),
                'IdLokasi' => (string) ($item['IdLokasi'] ?? ''),
                'UsiaHari' => (int) ($this->toFloat($item['UsiaHari'] ?? null) ?? 0.0),
                'Jenis' => (string) ($item['Jenis'] ?? ''),
                'BB' => (string) ($item['BB'] ?? ''),
            ];
        }

        return array_values($out);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function sortRowsForDisplay(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            foreach (['Jenis', 'NoST', 'IdLokasi'] as $column) {
                $cmp = strnatcasecmp((string) ($a[$column] ?? ''), (string) ($b[$column] ?? ''));
                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            $cmp = ((float) ($a['Tebal'] ?? 0.0)) <=> ((float) ($b['Tebal'] ?? 0.0));
            if ($cmp !== 0) {
                return $cmp;
            }

            return ((float) ($a['Lebar'] ?? 0.0)) <=> ((float) ($b['Lebar'] ?? 0.0));
        });

        return array_values($rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{name:string,rows:array<int,array<string,mixed>>,summary:array{total_rows:int,total_batang:int}}>
     */
    private function buildJenisGroups(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $jenis = $this->normalizeJenisName($row['Jenis'] ?? '');
            $key = strtoupper($jenis);

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'name' => $jenis,
                    'rows' => [],
                    'summary' => [
                        'total_rows' => 0,
                        'total_batang' => 0,
                    ],
                ];
            }

            $groups[$key]['rows'][] = $row;
            $groups[$key]['summary']['total_rows']++;
            $groups[$key]['summary']['total_batang'] += (int) ($row['JmlhBatang'] ?? 0);
        }

        return array_values($groups);
    }

    private function normalizeJenisName(mixed $value): string
    {
        $jenis = trim((string) $value);

        return $jenis !== '' ? $jenis : 'Tanpa Jenis';
    }

    /**
     * @param  array<int, string>  $modes
     * @return array<int, string>
     */
    private function normalizeModes(array $modes): array
    {
        $normalized = [];
        foreach ($modes as $mode) {
            $mode = strtoupper(trim((string) $mode));
            if (in_array($mode, ['INCLUDE', 'EXCLUDE'], true)) {
                $normalized[$mode] = true;
            }
        }

        return array_keys($normalized);
    }

    /**
     * @param  array<int, string>  $modes
     * @return array<int, object>
     */
    private function runProcedureQueries(int $hari, array $modes): array
    {
        $rows = [];
        $seen = [];

        foreach ($this->normalizeModes($modes) as $mode) {
            foreach ($this->runProcedureQuery($hari, $mode) as $row) {
                $key = $this->rowIdentity($row);
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function rowIdentity(object $row): string
    {
        $data = (array) $row;
        $parts = [
            (string) ($data['NoST'] ?? ''),
            (string) ($data['Tebal'] ?? ''),
            (string) ($data['Lebar'] ?? ''),
            (string) ($data['IdLokasi'] ?? ''),
            (string) ($data['Jenis'] ?? ''),
            (string) ($data['BB'] ?? ''),
        ];

        return md5(implode('|', $parts));
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (! is_string($value)) {
            return null;
        }
        $t = trim($value);
        if ($t === '') {
            return null;
        }
        $t = str_replace(',', '', $t);
        if (! is_numeric($t)) {
            return null;
        }

        return (float) $t;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(int $hari, string $mode): array
    {
        $configKey = 'reports.st_hidup_kering';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapSTHidupKering');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan ST Hidup Kering harus 2 (Hari dan Mode).');
        }

        $mode = strtoupper(trim($mode));
        if (! in_array($mode, ['INCLUDE', 'EXCLUDE'], true)) {
            $mode = 'INCLUDE';
        }

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan ST Hidup Kering belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan ST Hidup Kering dikonfigurasi untuk SQL Server. '
                .'Set ST_HIDUP_KERING_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan ST Hidup Kering belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$hari, $mode] : []);
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
            ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?"
            : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, [$hari, $mode]);
    }
}
