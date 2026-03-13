<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapKamarKdReportService
{
    private const EPS = 0.0000001;
    private const CAPACITY_M3 = 80.0;

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $mainRows = $this->fetchMain($startDate, $endDate);
        $sub1Rows = $this->fetchSub1($startDate, $endDate);
        $sub2Rows = $this->fetchSub2($startDate, $endDate);

        $jenisOrderByKd = $this->buildJenisOrderMap($sub2Rows);
        $sub1Index = $this->buildSub1Index($sub1Rows);

        /** @var array<int, array<int, array<string, mixed>>> $mainByKdJenis */
        $mainByKdJenis = [];
        /** @var array<int, array<int, array<string, mixed>>> $sub1ByKdJenis */
        $sub1ByKdJenis = [];
        /** @var array<int, array<string, bool>> $jenisSetByKd */
        $jenisSetByKd = [];

        foreach ($mainRows as $row) {
            $kd = (int) ($row['NoRuangKD'] ?? 0);
            $jenis = (string) ($row['Jenis'] ?? '');
            $jenis = trim($jenis);

            if ($kd <= 0 || $jenis === '') {
                continue;
            }

            $mainByKdJenis[$kd][$jenis][] = $row;
            $jenisSetByKd[$kd][$jenis] = true;
        }

        foreach ($sub1Rows as $row) {
            $kd = (int) ($row['NoRuangKD'] ?? 0);
            $jenis = (string) ($row['Jenis'] ?? '');
            $jenis = trim($jenis);

            if ($kd <= 0 || $jenis === '') {
                continue;
            }

            $sub1ByKdJenis[$kd][$jenis][] = $row;
            $jenisSetByKd[$kd][$jenis] = true;
        }

        $kds = array_keys($jenisSetByKd);
        sort($kds, SORT_NUMERIC);

        $rooms = [];
        $grandTon = 0.0;
        $grandM3 = 0.0;

        foreach ($kds as $kd) {
            $jenisKeys = array_keys($jenisSetByKd[$kd] ?? []);
            $jenisKeys = $this->sortJenisForKd($kd, $jenisKeys, $jenisOrderByKd);

            $hari = $this->resolveHariForKd($mainRows, $kd);

            $roomJenis = [];
            $sumPctRounded = 0.0;
            $roomTon = 0.0;
            $roomM3 = 0.0;

            $letterIndex = 0;
            foreach ($jenisKeys as $jenis) {
                $detailRows = array_values($mainByKdJenis[$kd][$jenis] ?? []);
                $summaryRows = array_values($sub1ByKdJenis[$kd][$jenis] ?? []);

                $summaryRows = $this->sortSub1Rows($summaryRows);
                $detailRows = $this->sortMainRows($detailRows);

                $tonTotal = array_reduce($detailRows, static fn (float $c, array $r): float => $c + (float) ($r['Ton'] ?? 0.0), 0.0);
                $m3Total = array_reduce($summaryRows, static fn (float $c, array $r): float => $c + (float) ($r['m3'] ?? 0.0), 0.0);
                $pctTotal = self::CAPACITY_M3 > 0 ? round(($m3Total / self::CAPACITY_M3) * 100.0, 2) : 0.0;

                $sumPctRounded += $pctTotal;
                $roomTon += $tonTotal;
                $roomM3 += $m3Total;

                $factorByTebal = [];
                foreach ($summaryRows as $sr) {
                    $tebalKey = $this->floatKey($sr['Tebal'] ?? 0.0);
                    $ton = (float) ($sr['Ton'] ?? 0.0);
                    $m3 = (float) ($sr['m3'] ?? 0.0);
                    if (abs($ton) > self::EPS) {
                        $factorByTebal[$tebalKey] = $m3 / $ton;
                    }
                }

                $detailComputed = [];
                foreach ($detailRows as $dr) {
                    $ton = (float) ($dr['Ton'] ?? 0.0);
                    $tebalKey = $this->floatKey($dr['Tebal'] ?? 0.0);
                    $factor = $factorByTebal[$tebalKey] ?? null;
                    if ($factor === null) {
                        $fallback = $sub1Index[$kd][$jenis][$tebalKey] ?? null;
                        $factor = is_array($fallback) && abs((float) ($fallback['Ton'] ?? 0.0)) > self::EPS
                            ? ((float) ($fallback['m3'] ?? 0.0) / (float) ($fallback['Ton'] ?? 0.0))
                            : 0.0;
                    }

                    $m3Est = $ton * (float) $factor;
                    $pctRow = self::CAPACITY_M3 > 0 ? (($m3Est / self::CAPACITY_M3) * 100.0) : 0.0;

                    $dr['m3_est'] = $m3Est;
                    $dr['pct_capacity'] = $pctRow;
                    $detailComputed[] = $dr;
                }

                $roomJenis[] = [
                    'label' => chr(ord('A') + $letterIndex) . '.',
                    'jenis' => $jenis,
                    'summary_rows' => $summaryRows,
                    'detail_rows' => $detailComputed,
                    'totals' => [
                        'ton' => $tonTotal,
                        'pct_capacity' => $pctTotal,
                    ],
                ];

                $letterIndex++;
            }

            $grandTon += $roomTon;
            $grandM3 += $roomM3;

            $aveCapacity = self::CAPACITY_M3 > 0 ? (($roomM3 / self::CAPACITY_M3) * 100.0) : 0.0;

            $rooms[] = [
                'no_ruang_kd' => $kd,
                'hari' => $hari,
                'jenis_concat' => (string) ($jenisOrderByKd[$kd]['raw'] ?? ''),
                'jenis_groups' => $roomJenis,
                'totals' => [
                    'jumlah_ton' => $roomTon,
                    // Sum of rounded per-jenis % capacity (matches the legacy printout).
                    'jumlah_pct_capacity' => $sumPctRounded,
                    // Average capacity computed from total m3 / 80 * 100.
                    'ave_pct_capacity' => $aveCapacity,
                ],
            ];
        }

        return [
            'rooms' => $rooms,
            'summary' => [
                'total_rooms' => count($rooms),
                'grand_ton' => $grandTon,
                'grand_m3' => $grandM3,
                'grand_ave_pct_capacity' => self::CAPACITY_M3 > 0 ? (($grandM3 / self::CAPACITY_M3) * 100.0) : 0.0,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $main = $this->fetchMain($startDate, $endDate);
        $sub1 = $this->fetchSub1($startDate, $endDate);
        $sub2 = $this->fetchSub2($startDate, $endDate);

        $detectedMain = array_keys($main[0] ?? []);
        $detectedSub1 = array_keys($sub1[0] ?? []);
        $detectedSub2 = array_keys($sub2[0] ?? []);

        $expectedMain = config('reports.rekap_kamar_kd.expected_columns', []);
        $expectedMain = is_array($expectedMain) ? array_values($expectedMain) : [];

        $expectedSub1 = config('reports.rekap_kamar_kd.expected_sub1_columns', []);
        $expectedSub1 = is_array($expectedSub1) ? array_values($expectedSub1) : [];

        $expectedSub2 = config('reports.rekap_kamar_kd.expected_sub2_columns', []);
        $expectedSub2 = is_array($expectedSub2) ? array_values($expectedSub2) : [];

        $missingMain = array_values(array_diff($expectedMain, $detectedMain));
        $missingSub1 = array_values(array_diff($expectedSub1, $detectedSub1));
        $missingSub2 = array_values(array_diff($expectedSub2, $detectedSub2));

        return [
            'is_healthy' => empty($missingMain) && empty($missingSub1) && empty($missingSub2),
            'main' => [
                'expected_columns' => $expectedMain,
                'detected_columns' => $detectedMain,
                'missing_columns' => $missingMain,
                'row_count' => count($main),
            ],
            'sub1' => [
                'expected_columns' => $expectedSub1,
                'detected_columns' => $detectedSub1,
                'missing_columns' => $missingSub1,
                'row_count' => count($sub1),
            ],
            'sub2' => [
                'expected_columns' => $expectedSub2,
                'detected_columns' => $detectedSub2,
                'missing_columns' => $missingSub2,
                'row_count' => count($sub2),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchMain(string $startDate, string $endDate): array
    {
        return $this->normalizeMainRows($this->runProcedureQuery($startDate, $endDate, 'main'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSub1(string $startDate, string $endDate): array
    {
        return $this->normalizeSub1Rows($this->runProcedureQuery($startDate, $endDate, 'sub1'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSub2(string $startDate, string $endDate): array
    {
        return $this->normalizeSub2Rows($this->runProcedureQuery($startDate, $endDate, 'sub2'));
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMainRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            $item['NoRuangKD'] = (int) ($item['NoRuangKD'] ?? 0);
            $item['Hari'] = (int) ($item['Hari'] ?? 0);
            $item['Tebal'] = (float) ($this->toFloat($item['Tebal'] ?? null) ?? 0.0);
            $item['Lebar'] = (float) ($this->toFloat($item['Lebar'] ?? null) ?? 0.0);
            $item['Ton'] = (float) ($this->toFloat($item['Ton'] ?? null) ?? 0.0);
            $item['AveTebal'] = (float) ($this->toFloat($item['AveTebal'] ?? null) ?? 0.0);
            $item['AvePanjang'] = (float) ($this->toFloat($item['AvePanjang'] ?? null) ?? 0.0);

            $out[] = $item;
        }

        return array_values($out);
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSub1Rows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            $item['NoRuangKD'] = (int) ($item['NoRuangKD'] ?? 0);
            $item['Tebal'] = (float) ($this->toFloat($item['Tebal'] ?? null) ?? 0.0);
            $item['Ton'] = (float) ($this->toFloat($item['Ton'] ?? null) ?? 0.0);
            $item['m3'] = (float) ($this->toFloat($item['m3'] ?? null) ?? 0.0);

            $out[] = $item;
        }

        return array_values($out);
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSub2Rows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            $item['NoRuangKD'] = (int) ($item['NoRuangKD'] ?? 0);
            $out[] = $item;
        }

        return array_values($out);
    }

    /**
     * @return array<int, array{raw: string, order: array<int, string>}>
     */
    private function buildJenisOrderMap(array $sub2Rows): array
    {
        $map = [];
        foreach ($sub2Rows as $row) {
            $kd = (int) ($row['NoRuangKD'] ?? 0);
            $raw = trim((string) ($row['Jenis'] ?? ''));
            if ($kd <= 0 || $raw === '') {
                continue;
            }

            $parts = array_values(array_filter(array_map(static fn (string $p): string => trim($p), preg_split('/\\s*--\\s*/', $raw) ?: []), static fn (string $p): bool => $p !== ''));
            $map[$kd] = [
                'raw' => $raw,
                'order' => $parts,
            ];
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $sub1Rows
     * @return array<int, array<string, array<string, array{Ton: float, m3: float}>>>
     */
    private function buildSub1Index(array $sub1Rows): array
    {
        $idx = [];
        foreach ($sub1Rows as $row) {
            $kd = (int) ($row['NoRuangKD'] ?? 0);
            $jenis = trim((string) ($row['Jenis'] ?? ''));
            $tebalKey = $this->floatKey($row['Tebal'] ?? 0.0);
            if ($kd <= 0 || $jenis === '') {
                continue;
            }
            $idx[$kd][$jenis][$tebalKey] = [
                'Ton' => (float) ($row['Ton'] ?? 0.0),
                'm3' => (float) ($row['m3'] ?? 0.0),
            ];
        }

        return $idx;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolveHariForKd(array $rows, int $kd): int
    {
        $max = 0;
        foreach ($rows as $r) {
            if ((int) ($r['NoRuangKD'] ?? 0) !== $kd) {
                continue;
            }
            $hari = (int) ($r['Hari'] ?? 0);
            if ($hari > $max) {
                $max = $hari;
            }
        }

        return $max;
    }

    /**
     * @param array<int, string> $jenisKeys
     * @param array<int, array{raw: string, order: array<int, string>}> $jenisOrderByKd
     * @return array<int, string>
     */
    private function sortJenisForKd(int $kd, array $jenisKeys, array $jenisOrderByKd): array
    {
        $order = $jenisOrderByKd[$kd]['order'] ?? [];
        if ($order === []) {
            sort($jenisKeys);
            return array_values($jenisKeys);
        }

        $pos = [];
        foreach ($order as $i => $j) {
            $pos[$j] = $i;
        }

        usort($jenisKeys, static function (string $a, string $b) use ($pos): int {
            $pa = $pos[$a] ?? 999999;
            $pb = $pos[$b] ?? 999999;
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }
            return strcmp($a, $b);
        });

        return array_values($jenisKeys);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function sortSub1Rows(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $ta = (float) ($a['Tebal'] ?? 0.0);
            $tb = (float) ($b['Tebal'] ?? 0.0);
            if (abs($ta - $tb) > self::EPS) {
                return $ta <=> $tb;
            }
            return 0;
        });

        return array_values($rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function sortMainRows(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $ta = (float) ($a['Tebal'] ?? 0.0);
            $tb = (float) ($b['Tebal'] ?? 0.0);
            if (abs($ta - $tb) > self::EPS) {
                return $ta <=> $tb;
            }
            $la = (float) ($a['Lebar'] ?? 0.0);
            $lb = (float) ($b['Lebar'] ?? 0.0);
            if (abs($la - $lb) > self::EPS) {
                return $la <=> $lb;
            }
            $tonA = (float) ($a['Ton'] ?? 0.0);
            $tonB = (float) ($b['Ton'] ?? 0.0);
            if (abs($tonA - $tonB) > self::EPS) {
                return $tonB <=> $tonA;
            }
            return 0;
        });

        return array_values($rows);
    }

    private function floatKey(mixed $value): string
    {
        return number_format((float) ($this->toFloat($value) ?? 0.0), 4, '.', '');
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
    private function runProcedureQuery(string $startDate, string $endDate, string $type): array
    {
        $configKey = 'reports.rekap_kamar_kd';
        $connectionName = config("{$configKey}.database_connection");

        $procedure = match ($type) {
            'main' => (string) config("{$configKey}.stored_procedure", 'SP_LapRekapKamarKD'),
            'sub1' => (string) config("{$configKey}.sub1_stored_procedure", 'SP_LapRekapKamarKD_Sub1'),
            'sub2' => (string) config("{$configKey}.sub2_stored_procedure", 'SP_LapRekapKamarKD_Sub2'),
            default => throw new RuntimeException('Tipe stored procedure tidak dikenal.'),
        };

        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = match ($type) {
            'main' => config("{$configKey}.query"),
            'sub1' => config("{$configKey}.sub1_query"),
            'sub2' => config("{$configKey}.sub2_query"),
            default => null,
        };

        $parameterCount = (int) match ($type) {
            'main' => config("{$configKey}.parameter_count", 2),
            'sub1' => config("{$configKey}.sub1_parameter_count", 2),
            'sub2' => config("{$configKey}.sub2_parameter_count", 2),
            default => 2,
        };

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap kamar KD belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        $bindings = [];
        if ($parameterCount >= 2) {
            $bindings = [$startDate, $endDate];
        } elseif ($parameterCount === 1) {
            $bindings = [$startDate];
        }

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap kamar KD dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_KAMAR_KD_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan rekap kamar KD belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $placeholders = $parameterCount >= 2 ? '?, ?' : ($parameterCount === 1 ? '?' : '');
        $sql = match ($syntax) {
            'exec' => $placeholders !== '' ? "SET NOCOUNT ON; EXEC {$procedure} {$placeholders}" : "SET NOCOUNT ON; EXEC {$procedure}",
            'call' => "CALL {$procedure}({$placeholders})",
            default => $driver === 'sqlsrv'
                ? ($placeholders !== '' ? "SET NOCOUNT ON; EXEC {$procedure} {$placeholders}" : "SET NOCOUNT ON; EXEC {$procedure}")
                : "CALL {$procedure}({$placeholders})",
        };

        return $connection->select($sql, $bindings);
    }
}
