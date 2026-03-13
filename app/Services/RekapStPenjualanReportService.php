<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapStPenjualanReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);

        /** @var array<string, array<int, array<string, mixed>>> $byPembeli */
        $byPembeli = [];
        foreach ($rows as $row) {
            $pembeli = trim((string) ($row['Pembeli'] ?? ''));
            $pembeli = $pembeli !== '' ? $pembeli : '-';
            $byPembeli[$pembeli][] = $row;
        }

        ksort($byPembeli);

        $groups = [];
        foreach ($byPembeli as $pembeli => $items) {
            usort($items, static function (array $a, array $b): int {
                $da = (string) ($a['TanggalSTRaw'] ?? '');
                $db = (string) ($b['TanggalSTRaw'] ?? '');
                $c = strcmp($da, $db);
                if ($c !== 0) {
                    return $c;
                }
                return strcmp((string) ($a['NoST'] ?? ''), (string) ($b['NoST'] ?? ''));
            });

            $sumBatang = array_reduce(
                $items,
                static fn (float $c, array $r): float => $c + (float) ($r['JmlhBtg'] ?? 0.0),
                0.0
            );
            $sumTon = array_reduce(
                $items,
                static fn (float $c, array $r): float => $c + (float) ($r['Ton'] ?? 0.0),
                0.0
            );

            $groups[] = [
                'pembeli' => $pembeli,
                'rows' => array_values($items),
                'totals' => [
                    'jmlh_btg' => $sumBatang,
                    'ton' => $sumTon,
                ],
            ];
        }

        return [
            'groups' => $groups,
            'summary' => [
                'total_groups' => count($groups),
                'total_rows' => count($rows),
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
        $detectedColumns = array_keys($first);
        $expectedColumns = config('reports.rekap_st_penjualan.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'row_count' => count($raw),
        ];
    }

    /**
     * Normalized rows for rendering.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);

        $out = [];
        foreach ($raw as $row) {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            // Report column "NoST" follows SP field "NoST" (e.g. E.427287 in the reference),
            // not "NoSTJual" (e.g. G.0006xx).
            $noSt = (string) ($item['NoST'] ?? $item['NoSTJual'] ?? '');
            $tgl = (string) ($item['DateCreate'] ?? $item['TglJual'] ?? $item['Tanggal'] ?? '');

            $tebal = (float) ($this->toFloat($item['Tebal'] ?? null) ?? 0.0);
            $lebar = (float) ($this->toFloat($item['Lebar'] ?? null) ?? 0.0);
            $panjang = (float) ($this->toFloat($item['Panjang'] ?? null) ?? 0.0);
            $jmlhBtg = (int) ($this->toFloat($item['JmlhBatang'] ?? $item['JmlhBtg'] ?? null) ?? 0.0);
            $ton = (float) ($this->toFloat($item['Ton'] ?? null) ?? 0.0);

            $uomTbl = $item['UOMTblLebar'] ?? null;
            if (!is_string($uomTbl) || trim($uomTbl) === '') {
                $uomTbl = $this->uomLabel((int) ($item['IdUOMTblLebar'] ?? 0));
            }
            $uomPjg = $item['UOMPanjang'] ?? null;
            if (!is_string($uomPjg) || trim($uomPjg) === '') {
                $uomPjg = $this->uomLabel((int) ($item['IdUOMPanjang'] ?? 0));
            }

            $out[] = [
                'Pembeli' => (string) ($item['Pembeli'] ?? ''),
                'NoST' => $noSt,
                // Keep raw date for stable sorting; format happens in Blade.
                'TanggalSTRaw' => $tgl,
                'Tanggal (ST)' => $tgl,
                'Jenis Kayu' => (string) ($item['Jenis'] ?? $item['JenisKayu'] ?? ''),
                'Tebal' => $tebal,
                'Lebar' => $lebar,
                'UOMTblLebar' => is_string($uomTbl) ? trim($uomTbl) : '',
                'Panjang' => $panjang,
                'UOMPanjang' => is_string($uomPjg) ? trim($uomPjg) : '',
                'JmlhBtg' => $jmlhBtg,
                'Ton' => $ton,
            ];
        }

        return array_values($out);
    }

    private function uomLabel(int $id): string
    {
        return match ($id) {
            1 => 'mm',
            3 => 'inch',
            4 => 'feet',
            default => '',
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
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.rekap_st_penjualan';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapRekapSTPenjualan');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan Rekap ST Penjualan harus 2 (Tanggal Awal dan Tanggal Akhir).');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Rekap ST Penjualan belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Rekap ST Penjualan dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_ST_PENJUALAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Rekap ST Penjualan belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$startDate, $endDate] : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?"
                : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, [$startDate, $endDate]);
    }
}
