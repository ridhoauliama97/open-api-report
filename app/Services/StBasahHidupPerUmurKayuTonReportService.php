<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StBasahHidupPerUmurKayuTonReportService
{
    private const EPS = 0.0000001;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();

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

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rawRows = $this->fetch();

        $rows = [];

        $sumLess2 = 0.0;
        $sum2to4 = 0.0;
        $sum4to6 = 0.0;
        $sum6to8 = 0.0;
        $sumMore8 = 0.0;
        $sumAll = 0.0;

        foreach ($rawRows as $row) {
            $group = trim((string) ($row['Group'] ?? ''));
            $less2 = (float) ($this->toFloat($row['Ton2WkLess'] ?? null) ?? 0.0);
            $to4 = (float) ($this->toFloat($row['Ton2to4Wk'] ?? null) ?? 0.0);
            $to6 = (float) ($this->toFloat($row['Ton4to6Wk'] ?? null) ?? 0.0);
            $to8 = (float) ($this->toFloat($row['Ton6to8Wk'] ?? null) ?? 0.0);
            $more8 = (float) ($this->toFloat($row['Ton8WkMore'] ?? null) ?? 0.0);

            $rowTotal = $less2 + $to4 + $to6 + $to8 + $more8;

            $rows[] = [
                'Group' => $group,
                '<= 2 Minggu' => $less2,
                '2 - 4 Minggu' => $to4,
                '4 - 6 Minggu' => $to6,
                '6 - 8 Minggu' => $to8,
                '> 8 Minggu' => $more8,
                'Total' => $rowTotal,
            ];

            $sumLess2 += $less2;
            $sum2to4 += $to4;
            $sum4to6 += $to6;
            $sum6to8 += $to8;
            $sumMore8 += $more8;
            $sumAll += $rowTotal;
        }

        return [
            'rows_raw' => $rawRows,
            'rows' => $rows,
            'totals' => [
                'Group' => 'Total',
                '<= 2 Minggu' => $sumLess2,
                '2 - 4 Minggu' => $sum2to4,
                '4 - 6 Minggu' => $sum4to6,
                '6 - 8 Minggu' => $sum6to8,
                '> 8 Minggu' => $sumMore8,
                'Total' => $sumAll,
            ],
            'summary' => [
                'total_rows' => count($rows),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $rows = $this->fetch();
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.st_basah_hidup_per_umur_kayu_ton.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
        ];
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
     * @return array<int, object>
     */
    private function runProcedureQuery(): array
    {
        $configKey = 'reports.st_basah_hidup_per_umur_kayu_ton';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapSTBasahHidupPerUmurKayu');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 0);

        if ($parameterCount !== 0) {
            throw new RuntimeException('Jumlah parameter laporan ST Basah Hidup Per-Umur Kayu (Ton) harus 0.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan ST basah hidup per-umur kayu (Ton) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan ST basah hidup per-umur kayu (Ton) dikonfigurasi untuk SQL Server. '
                . 'Set ST_BASAH_HIDUP_PER_UMUR_KAYU_TON_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'ST_BASAH_HIDUP_PER_UMUR_KAYU_TON_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan ST_BASAH_HIDUP_PER_UMUR_KAYU_TON_REPORT_CALL_SYNTAX=query.',
                );

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

