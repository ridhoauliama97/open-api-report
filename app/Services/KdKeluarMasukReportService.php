<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KdKeluarMasukReportService
{
    private const EPS = 0.0000001;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate, ?int $noKd): array
    {
        return $this->normalizeRows($this->runProcedureQuery($startDate, $endDate, $noKd));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate, ?int $noKd): array
    {
        $rawRows = $this->fetch($startDate, $endDate, $noKd);

        $groupColumns = [
            'JABON',
            'JABON TG',
            'PULAI',
            'RAMBUNG',
            'RAMBUNG MC1',
            'RAMBUNG MC2',
        ];

        $keyed = [];

        foreach ($rawRows as $row) {
            $noKamar = (int) ($row['NoKamarKD'] ?? 0);
            $tglMasuk = $this->normalizeDateKey($row['TglMasuk'] ?? null);
            $tglKeluar = $this->normalizeDateKey($row['TglKeluar'] ?? null);
            $hari = (int) ($this->toFloat($row['JmlhHari'] ?? null) ?? 0);
            $aveTebal = (float) ($this->toFloat($row['AveTebal'] ?? null) ?? 0.0);
            $ton = (float) ($this->toFloat($row['Ton'] ?? null) ?? 0.0);

            $group = trim((string) ($row['Group'] ?? ''));
            $groupKey = $this->normalizeGroupKey($group);

            $key = implode('|', [
                $noKamar,
                $tglMasuk,
                $tglKeluar,
                $hari,
                // keep 1 decimal stable key to avoid float noise
                number_format($aveTebal, 1, '.', ''),
            ]);

            if (!isset($keyed[$key])) {
                $record = [
                    'Tanggal (Out)' => $tglKeluar,
                    'Tanggal (In)' => $tglMasuk,
                    'No.KD' => $noKamar === 0 ? '' : (string) $noKamar,
                    'Hari' => $hari,
                    'Ave Tebal' => $aveTebal,
                ];
                foreach ($groupColumns as $col) {
                    $record[$col] = 0.0;
                }
                $record['Total'] = 0.0;
                $keyed[$key] = $record;
            }

            if ($groupKey !== null && array_key_exists($groupKey, $keyed[$key])) {
                $keyed[$key][$groupKey] = (float) $keyed[$key][$groupKey] + $ton;
            }
        }

        $rows = array_values($keyed);

        // Compute total per row.
        foreach ($rows as &$r) {
            $sum = 0.0;
            foreach ($groupColumns as $col) {
                $sum += (float) ($r[$col] ?? 0.0);
            }
            $r['Total'] = $sum;
        }
        unset($r);

        // Sort consistent with SP: non-null TglKeluar first, then by TglKeluar, then TglMasuk.
        usort($rows, function (array $a, array $b): int {
            $aOut = trim((string) ($a['Tanggal (Out)'] ?? ''));
            $bOut = trim((string) ($b['Tanggal (Out)'] ?? ''));
            $aNull = $aOut === '' ? 1 : 0;
            $bNull = $bOut === '' ? 1 : 0;
            if ($aNull !== $bNull) {
                return $aNull <=> $bNull;
            }

            $cmpOut = strcmp($aOut, $bOut);
            if ($cmpOut !== 0) {
                return $cmpOut;
            }

            return strcmp(
                trim((string) ($a['Tanggal (In)'] ?? '')),
                trim((string) ($b['Tanggal (In)'] ?? '')),
            );
        });

        $rowsKeluar = array_values(array_filter($rows, static fn(array $r): bool => trim((string) ($r['Tanggal (Out)'] ?? '')) !== ''));
        $rowsMasih = array_values(array_filter($rows, static fn(array $r): bool => trim((string) ($r['Tanggal (Out)'] ?? '')) === ''));

        $sumCols = function (array $items) use ($groupColumns): array {
            $tot = [];
            foreach ($groupColumns as $col) {
                $tot[$col] = 0.0;
            }
            $tot['Total'] = 0.0;

            foreach ($items as $r) {
                foreach ($groupColumns as $col) {
                    $tot[$col] += (float) ($r[$col] ?? 0.0);
                }
                $tot['Total'] += (float) ($r['Total'] ?? 0.0);
            }

            return $tot;
        };

        $totalKeluar = $sumCols($rowsKeluar);
        $totalMasih = $sumCols($rowsMasih);
        $grandTotal = $sumCols($rows);

        return [
            'rows_raw' => $rawRows,
            'rows_keluar' => $rowsKeluar,
            'rows_masih' => $rowsMasih,
            'group_columns' => $groupColumns,
            'totals' => [
                'keluar' => $totalKeluar,
                'masih' => $totalMasih,
                'grand' => $grandTotal,
            ],
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'no_kd' => $noKd,
            ],
            'summary' => [
                'total_rows' => count($rows),
                'total_rows_keluar' => count($rowsKeluar),
                'total_rows_masih' => count($rowsMasih),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate, null);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.kd_keluar_masuk.expected_columns', []);
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

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_values(array_map(static function (object $row): array {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }
            return $item;
        }, $rows));
    }

    private function normalizeGroupKey(string $raw): ?string
    {
        $t = strtoupper(trim($raw));
        $t = preg_replace('/\\s+/', ' ', $t) ?? $t;

        return match ($t) {
            'JABON' => 'JABON',
            'JABON TG', 'JABON TGI', 'JABON TANGGUNG' => 'JABON TG',
            'PULAI' => 'PULAI',
            'RAMBUNG' => 'RAMBUNG',
            'RAMBUNG MC1', 'RAMBUNG MC 1' => 'RAMBUNG MC1',
            'RAMBUNG MC2', 'RAMBUNG MC 2' => 'RAMBUNG MC2',
            default => null,
        };
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            $trimmed = str_replace(',', '', $trimmed);
            if (!is_numeric($trimmed)) {
                return null;
            }
            return (float) $trimmed;
        }

        return null;
    }

    private function normalizeDateKey(mixed $raw): string
    {
        $value = trim((string) ($raw ?? ''));
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return $value;
        }
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate, ?int $noKd): array
    {
        $configKey = 'reports.kd_keluar_masuk';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapKDKeluarMasuk');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan KD (Keluar - Masuk) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan KD (Keluar - Masuk) dikonfigurasi untuk SQL Server. '
                . 'Set KD_KELUAR_MASUK_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $bindings = [$startDate, $endDate, $noKd];

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'KD_KELUAR_MASUK_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan KD_KELUAR_MASUK_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?, ?",
            'call' => "CALL {$procedure}(?, ?, ?)",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?, ?" : "CALL {$procedure}(?, ?, ?)",
        };

        return $connection->select($sql, $bindings);
    }
}

