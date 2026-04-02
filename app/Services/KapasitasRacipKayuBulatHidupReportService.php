<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class KapasitasRacipKayuBulatHidupReportService
{
    private const TOTAL_TON_CAPACITY = 323.7837;
    private const NON_RAMBUNG_RENDEMEN = 0.85;
    private const RAMBUNG_RENDEMEN = 0.20;

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $nonRambungRows = $this->fetchProcedureRows('kapasitas_racip_kayu_bulat_hidup_non_rambung');
        $rambungRows = $this->fetchProcedureRows('kapasitas_racip_kayu_bulat_hidup_rambung');
        $jmlhHK = $this->fetchSingleCount('kapasitas_racip_kayu_bulat_hidup_jmlh_hk', $startDate, $endDate);
        $jmlhMeja = $this->fetchSingleCount('kapasitas_racip_kayu_bulat_hidup_jmlh_meja', $startDate, $endDate);

        $nonRambungRows = array_map(function (array $row): array {
            return [
                'JenisKayu' => trim((string) ($row['Group'] ?? '')),
                'Ton' => $this->toFloat($row['Ton'] ?? null) ?? 0.0,
            ];
        }, $nonRambungRows);

        $rambungRows = array_map(function (array $row): array {
            return [
                'NamaGrade' => trim((string) ($row['NamaGrade'] ?? '')),
                'Berat' => $this->toFloat($row['Berat'] ?? null) ?? 0.0,
            ];
        }, $rambungRows);

        $nonRambungTotal = array_sum(array_column($nonRambungRows, 'Ton'));
        $rambungTotal = array_sum(array_column($rambungRows, 'Berat'));

        $tonPerHari = $jmlhHK > 0 ? self::TOTAL_TON_CAPACITY / $jmlhHK : 0.0;
        $mejaPerHari = $jmlhHK > 0 ? $jmlhMeja / $jmlhHK : 0.0;
        $tonPerHariMeja = $mejaPerHari > 0 ? $tonPerHari / $mejaPerHari : 0.0;

        $nonRambungEffectiveTon = $nonRambungTotal * self::NON_RAMBUNG_RENDEMEN;
        $rambungEffectiveTon = $rambungTotal * self::RAMBUNG_RENDEMEN;

        $nonRambungDays = $tonPerHari > 0 ? $nonRambungEffectiveTon / $tonPerHari : 0.0;
        $rambungDays = $tonPerHari > 0 ? $rambungEffectiveTon / $tonPerHari : 0.0;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'non_rambung' => [
                'rows' => $nonRambungRows,
                'total_ton' => $nonRambungTotal,
                'rendemen_percent' => self::NON_RAMBUNG_RENDEMEN * 100,
                'effective_ton' => $nonRambungEffectiveTon,
                'required_days' => $nonRambungDays,
            ],
            'rambung' => [
                'rows' => $rambungRows,
                'total_berat' => $rambungTotal,
                'rendemen_percent' => self::RAMBUNG_RENDEMEN * 100,
                'effective_ton' => $rambungEffectiveTon,
                'required_days' => $rambungDays,
            ],
            'capacity' => [
                'jmlh_hk' => $jmlhHK,
                'jmlh_meja' => $jmlhMeja,
                'meja_per_hari' => $mejaPerHari,
                'total_ton' => self::TOTAL_TON_CAPACITY,
                'ton_per_hari' => $tonPerHari,
                'ton_per_hari_meja' => $tonPerHariMeja,
            ],
            'summary' => [
                'required_days' => $nonRambungDays + $rambungDays,
                'row_count' => count($nonRambungRows) + count($rambungRows),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $nonRambungRows = $this->fetchProcedureRows('kapasitas_racip_kayu_bulat_hidup_non_rambung');
        $rambungRows = $this->fetchProcedureRows('kapasitas_racip_kayu_bulat_hidup_rambung');
        $jmlhHK = $this->fetchProcedureRows('kapasitas_racip_kayu_bulat_hidup_jmlh_hk', $startDate, $endDate);
        $jmlhMeja = $this->fetchProcedureRows('kapasitas_racip_kayu_bulat_hidup_jmlh_meja', $startDate, $endDate);

        return [
            'is_healthy' => $nonRambungRows !== [] && $rambungRows !== [] && $jmlhHK !== [] && $jmlhMeja !== [],
            'non_rambung_columns' => array_keys($nonRambungRows[0] ?? []),
            'rambung_columns' => array_keys($rambungRows[0] ?? []),
            'jmlh_hk_columns' => array_keys($jmlhHK[0] ?? []),
            'jmlh_meja_columns' => array_keys($jmlhMeja[0] ?? []),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchProcedureRows(string $configKey, ?string $startDate = null, ?string $endDate = null): array
    {
        $config = (array) config("reports.{$configKey}");
        $connectionName = $config['database_connection'] ?? null;
        $procedure = (string) ($config['stored_procedure'] ?? '');
        $parameterCount = (int) ($config['parameter_count'] ?? 0);

        if ($procedure === '' || !preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException("Stored procedure untuk {$configKey} belum valid.");
        }

        $bindings = match ($parameterCount) {
            0 => [],
            2 => [$startDate, $endDate],
            default => throw new RuntimeException("Parameter count untuk {$configKey} belum didukung."),
        };

        $placeholders = $parameterCount > 0 ? ' ' . implode(', ', array_fill(0, $parameterCount, '?')) : '';
        $rows = DB::connection($connectionName ?: null)->select("SET NOCOUNT ON; EXEC {$procedure}{$placeholders}", $bindings);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    private function fetchSingleCount(string $configKey, string $startDate, string $endDate): int
    {
        $rows = $this->fetchProcedureRows($configKey, $startDate, $endDate);
        $first = (array) ($rows[0] ?? []);
        $value = reset($first);

        return (int) round($this->toFloat($value) ?? 0.0);
    }

    private function toFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
