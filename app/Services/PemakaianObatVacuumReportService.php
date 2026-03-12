<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PemakaianObatVacuumReportService
{
    private const EPS = 0.0000001;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        return $this->normalizeRows($this->runProcedureQuery($startDate, $endDate));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rawRows = $this->fetch($startDate, $endDate);

        $rows = [];

        $sumAir = 0.0;
        $sumBorax = 0.0;
        $sumBoric = 0.0;
        $sumKaporit = 0.0;
        $sumSt = 0.0;
        $sumJabon = 0.0;
        $sumJabonTg = 0.0;
        $sumPulai = 0.0;
        $sumRambung = 0.0;
        $sumCharge = 0.0;
        $sumMenit = 0.0;

        foreach ($rawRows as $row) {
            $tanggal = trim((string) ($row['Tanggal'] ?? ''));
            $air = (float) ($this->toFloat($row['Air'] ?? null) ?? 0.0);
            $borax = (float) ($this->toFloat($row['Borax'] ?? null) ?? 0.0);
            $boric = (float) ($this->toFloat($row['Boric'] ?? null) ?? 0.0);
            $kaporit = (float) ($this->toFloat($row['Kaporit'] ?? null) ?? 0.0);

            $st = (float) ($this->toFloat($row['STTon'] ?? null) ?? 0.0);
            $jabon = (float) ($this->toFloat($row['STJabon'] ?? null) ?? 0.0);
            $jabonTg = (float) ($this->toFloat($row['STTG'] ?? null) ?? 0.0);
            $pulai = (float) ($this->toFloat($row['STPulai'] ?? null) ?? 0.0);
            $rambung = (float) ($this->toFloat($row['STRambung'] ?? null) ?? 0.0);

            $charge = (float) ($this->toFloat($row['Charge'] ?? null) ?? 0.0);
            $menit = (float) ($this->toFloat($row['JamKerja'] ?? null) ?? 0.0);

            $rasioBorax = $st > self::EPS && $borax > self::EPS ? $borax / $st : 0.0;
            $rasioBoric = $st > self::EPS && $boric > self::EPS ? $boric / $st : 0.0;
            $obatPercent = $air > self::EPS && ($borax + $boric) > self::EPS ? (($borax + $boric) / $air) * 100.0 : 0.0;
            $boraxPerBoric = $boric > self::EPS && $borax > self::EPS ? $borax / $boric : 0.0;
            $kaporitPercent = $air > self::EPS && $kaporit > self::EPS ? ($kaporit / $air) * 100.0 : 0.0;

            $chargeMenit = $charge > self::EPS && $menit > self::EPS ? $menit / $charge : 0.0;
            $stPerCharge = $charge > self::EPS && $st > self::EPS ? $st / $charge : 0.0;

            $rows[] = [
                'Tanggal' => $tanggal,
                'Air' => $air,
                'Borax (kg)' => $borax,
                'Rasio Borax (kg/ton)' => $rasioBorax,
                'Boric (kg)' => $boric,
                'Rasio Boric (kg/ton)' => $rasioBoric,
                'Obat (%)' => $obatPercent,
                'Borax / Boric' => $boraxPerBoric,
                'Kaporit (Kg)' => $kaporit,
                'Persen (%)' => $kaporitPercent,
                'ST (Ton)' => $st,
                'Jabon' => $jabon,
                'Jabon TG' => $jabonTg,
                'Pulai' => $pulai,
                'Rambung' => $rambung,
                'Charge' => $charge,
                'Menit' => $menit,
                'Charge (Menit)' => $chargeMenit,
                'ST Ton/Charge' => $stPerCharge,
            ];

            $sumAir += $air;
            $sumBorax += $borax;
            $sumBoric += $boric;
            $sumKaporit += $kaporit;
            $sumSt += $st;
            $sumJabon += $jabon;
            $sumJabonTg += $jabonTg;
            $sumPulai += $pulai;
            $sumRambung += $rambung;
            $sumCharge += $charge;
            $sumMenit += $menit;
        }

        $grand = [
            'Tanggal' => 'Total',
            'Air' => $sumAir,
            'Borax (kg)' => $sumBorax,
            'Rasio Borax (kg/ton)' => $sumSt > self::EPS && $sumBorax > self::EPS ? $sumBorax / $sumSt : 0.0,
            'Boric (kg)' => $sumBoric,
            'Rasio Boric (kg/ton)' => $sumSt > self::EPS && $sumBoric > self::EPS ? $sumBoric / $sumSt : 0.0,
            'Obat (%)' => $sumAir > self::EPS && ($sumBorax + $sumBoric) > self::EPS ? (($sumBorax + $sumBoric) / $sumAir) * 100.0 : 0.0,
            'Borax / Boric' => $sumBoric > self::EPS && $sumBorax > self::EPS ? $sumBorax / $sumBoric : 0.0,
            'Kaporit (Kg)' => $sumKaporit,
            'Persen (%)' => $sumAir > self::EPS && $sumKaporit > self::EPS ? ($sumKaporit / $sumAir) * 100.0 : 0.0,
            'ST (Ton)' => $sumSt,
            'Jabon' => $sumJabon,
            'Jabon TG' => $sumJabonTg,
            'Pulai' => $sumPulai,
            'Rambung' => $sumRambung,
            'Charge' => $sumCharge,
            'Menit' => $sumMenit,
            'Charge (Menit)' => $sumCharge > self::EPS && $sumMenit > self::EPS ? $sumMenit / $sumCharge : 0.0,
            'ST Ton/Charge' => $sumCharge > self::EPS && $sumSt > self::EPS ? $sumSt / $sumCharge : 0.0,
        ];

        return [
            'rows_raw' => $rawRows,
            'rows' => $rows,
            'grand_total' => $grand,
            'summary' => [
                'total_rows' => count($rows),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.pemakaian_obat_vacuum.expected_columns', []);
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
        $normalized = array_values(array_map(function (object $row): array {
            $item = (array) $row;

            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            return $item;
        }, $rows));

        // Best-effort sort by date if any date-like column exists.
        $columns = array_keys($normalized[0] ?? []);
        $dateCol = $this->guessDateColumn($columns);
        if ($dateCol !== null) {
            usort($normalized, function (array $a, array $b) use ($dateCol): int {
                return strcmp(
                    $this->normalizeDateKey($a[$dateCol] ?? null),
                    $this->normalizeDateKey($b[$dateCol] ?? null),
                );
            });
        }

        return $normalized;
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

    /**
     * @param array<int, string> $columns
     */
    private function guessDateColumn(array $columns): ?string
    {
        foreach ($columns as $col) {
            $k = $this->normKey($col);
            if (str_contains($k, 'tanggal') || str_contains($k, 'tgl') || str_contains($k, 'date')) {
                return $col;
            }
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

    private function normKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '', $value) ?? $value;

        return $value;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.pemakaian_obat_vacuum';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", '');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan pemakaian obat vacuum belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = array_slice([$startDate, $endDate], 0, max(0, min($parameterCount, 2)));

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan pemakaian obat vacuum dikonfigurasi untuk SQL Server. '
                . 'Set PEMAKAIAN_OBAT_VACUUM_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan pemakaian obat vacuum belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv' ? "EXEC {$procedure} ?, ?" : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, $bindings);
    }
}
