<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class DetailLembarTallyHasilSawmillReportService
{
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
        $rows = $this->fetch($startDate, $endDate);

        if ($rows === []) {
            throw new RuntimeException('Data tally hasil sawmill detail tidak ditemukan untuk rentang tanggal yang dipilih.');
        }

        $groups = $this->buildGroups($rows);

        return [
            'rows' => $rows,
            'groups' => $groups,
            'summary' => [
                'total_rows' => count($rows),
                'total_documents' => count($groups),
                'total_batang' => array_sum(array_map(static fn (array $group): float => (float) ($group['summary']['total_batang'] ?? 0), $groups)),
                'total_ton' => array_sum(array_map(static fn (array $group): float => (float) ($group['summary']['total_ton'] ?? 0), $groups)),
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
        $expectedColumns = config('reports.detail_lembar_tally_hasil_sawmill.expected_columns', []);
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
     * @param  array<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        $normalized = array_map(function (object $row): array {
            $item = (array) $row;

            return [
                'NoSTSawmill' => trim((string) ($item['NoSTSawmill'] ?? '')),
                'NoMeja' => trim((string) ($item['NoMeja'] ?? '')),
                'TglSawmill' => trim((string) ($item['TglSawmill'] ?? '')),
                'NoPlat' => trim((string) ($item['NoPlat'] ?? '')),
                'NmSupplier' => trim((string) ($item['NmSupplier'] ?? '')),
                'Jenis' => trim((string) ($item['Jenis'] ?? '')),
                'NoKayuBulat' => trim((string) ($item['NoKayuBulat'] ?? '')),
                'Operator' => trim((string) ($item['Operator'] ?? '')),
                'NoUrut' => (int) ($item['NoUrut'] ?? 0),
                'Tebal' => $this->toFloat($item['Tebal'] ?? null) ?? 0.0,
                'Lebar' => $this->toFloat($item['Lebar'] ?? null) ?? 0.0,
                'IdUOMTblLebar' => (int) ($item['IdUOMTblLebar'] ?? 0),
                'Panjang' => $this->toFloat($item['Panjang'] ?? null) ?? 0.0,
                'IdUOMPanjang' => (int) ($item['IdUOMPanjang'] ?? 0),
                'JmlhBatang' => (int) round($this->toFloat($item['JmlhBatang'] ?? null) ?? 0.0),
                'Ton' => $this->toFloat($item['Ton'] ?? null) ?? 0.0,
            ];
        }, $rows);

        usort($normalized, static function (array $a, array $b): int {
            $dateCompare = strcmp((string) ($a['TglSawmill'] ?? ''), (string) ($b['TglSawmill'] ?? ''));
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            $mejaCompare = ((int) ($a['NoMeja'] ?? 0)) <=> ((int) ($b['NoMeja'] ?? 0));
            if ($mejaCompare !== 0) {
                return $mejaCompare;
            }

            $stCompare = strcmp((string) ($a['NoSTSawmill'] ?? ''), (string) ($b['NoSTSawmill'] ?? ''));
            if ($stCompare !== 0) {
                return $stCompare;
            }

            $kbCompare = strcmp((string) ($a['NoKayuBulat'] ?? ''), (string) ($b['NoKayuBulat'] ?? ''));
            if ($kbCompare !== 0) {
                return $kbCompare;
            }

            return ((int) ($a['NoUrut'] ?? 0)) <=> ((int) ($b['NoUrut'] ?? 0));
        });

        return array_values($normalized);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildGroups(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $groupKey = implode('|', [
                (string) ($row['NoSTSawmill'] ?? ''),
                (string) ($row['NoMeja'] ?? ''),
                (string) ($row['NoKayuBulat'] ?? ''),
                (string) ($row['NoPlat'] ?? ''),
            ]);

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'header' => [
                        'no_meja' => (string) ($row['NoMeja'] ?? '-'),
                        'no_st' => (string) ($row['NoSTSawmill'] ?? '-'),
                        'tanggal' => (string) ($row['TglSawmill'] ?? ''),
                        'operator' => trim((string) ($row['Operator'] ?? '')) !== '' ? (string) $row['Operator'] : '-',
                        'supplier' => (string) ($row['NmSupplier'] ?? '-'),
                        'jenis_kayu' => (string) ($row['Jenis'] ?? '-'),
                        'no_kb' => (string) ($row['NoKayuBulat'] ?? '-'),
                        'no_plat' => (string) ($row['NoPlat'] ?? '-'),
                    ],
                    'rows' => [],
                    'summary' => [
                        'total_batang' => 0,
                        'total_ton' => 0.0,
                    ],
                ];
            }

            $groups[$groupKey]['rows'][] = [
                'no' => (int) ($row['NoUrut'] ?? 0),
                'tebal' => (float) ($row['Tebal'] ?? 0),
                'lebar' => (float) ($row['Lebar'] ?? 0),
                'uom_tbl_lebar' => $this->mapSizeUom((int) ($row['IdUOMTblLebar'] ?? 0)),
                'panjang' => (float) ($row['Panjang'] ?? 0),
                'uom_panjang' => $this->mapLengthUom((int) ($row['IdUOMPanjang'] ?? 0)),
                'jumlah_batang' => (int) ($row['JmlhBatang'] ?? 0),
                'ton' => (float) ($row['Ton'] ?? 0),
            ];

            $groups[$groupKey]['summary']['total_batang'] += (int) ($row['JmlhBatang'] ?? 0);
            $groups[$groupKey]['summary']['total_ton'] += (float) ($row['Ton'] ?? 0);
        }

        return array_values($groups);
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.detail_lembar_tally_hasil_sawmill';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SPWps_DetailLembarTallyHasilSawmill');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan tally hasil sawmill detail belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = array_slice([$startDate, $endDate], 0, max(0, min($parameterCount, 2)));

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan tally hasil sawmill detail dikonfigurasi untuk SQL Server. '
                .'Set DETAIL_LEMBAR_TALLY_HASIL_SAWMILL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan tally hasil sawmill detail belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?"
                : "CALL {$procedure}(?, ?)",
        };

        try {
            return $connection->select($sql, $bindings);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan tally hasil sawmill detail: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
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
            $normalized = str_replace(',', '.');
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function mapSizeUom(int $id): string
    {
        return match ($id) {
            1 => 'mm',
            2 => 'cm',
            3 => 'inch',
            default => '-',
        };
    }

    private function mapLengthUom(int $id): string
    {
        return match ($id) {
            1 => 'mm',
            2 => 'cm',
            3 => 'm',
            4 => 'feet',
            default => '-',
        };
    }
}
