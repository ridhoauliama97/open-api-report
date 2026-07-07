<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LaporanLabaRugiRuReportService
{
    private const TITLE = 'Laporan Laba Rugi';

    private const PREFIX_TO_AKM = [
        '411.000' => 'PENJUALAN',
        '412.000' => 'PENJUALAN',
        '451.000' => 'RETUR PENJUALAN',
        '452.000' => 'RETUR PENJUALAN',
        '431.000' => 'POTONGAN PENJUALAN',
        '516.000' => 'HPP PENJUALAN',
        '621.000' => 'PEMBELIAN BARANG DAGANG',
        '641.000' => 'BEBAN PEMBELIAN',
        '642.000' => 'BEBAN PEMBELIAN',
        '711.000' => 'BEBAN PENJUALAN',
        '721.000' => 'BEBAN UMUM',
        '800.000' => 'PENDAPATAN LAINNYA (PL)',
        '900.000' => 'BEBAN LAINNYA (BL)',
        '421.001' => 'PENDAPATAN JASA TENAGA AHLI',
        '421.002' => 'PENDAPATAN JASA SEWA',
        '421.003' => 'PENDAPATAN JASA PRODUKSI',
        '421.004' => 'PENDAPATAN JASA PEMBELIAN',
    ];

    private const RASIO_SIGN = [
        'PENJUALAN' => 1,
        'POTONGAN PENJUALAN' => -1,
        'RETUR PENJUALAN' => -1,
        'PENDAPATAN JASA TENAGA AHLI' => 1,
        'PENDAPATAN JASA SEWA' => 1,
        'PENDAPATAN JASA PRODUKSI' => 1,
        'PENDAPATAN JASA PEMBELIAN' => 1,
        'HPP PENJUALAN' => -1,
        'PEMBELIAN BARANG DAGANG' => -1,
        'BEBAN PEMBELIAN' => -1,
        'BEBAN PENJUALAN' => -1,
        'BEBAN UMUM' => -1,
        'PENDAPATAN LAINNYA (PL)' => 1,
        'BEBAN LAINNYA (BL)' => -1,
    ];

    private const AKL_GROUPS = [
        'PENDAPATAN' => [
            'PENJUALAN',
            'POTONGAN PENJUALAN',
            'RETUR PENJUALAN',
            'PENDAPATAN JASA TENAGA AHLI',
            'PENDAPATAN JASA SEWA',
            'PENDAPATAN JASA PRODUKSI',
            'PENDAPATAN JASA PEMBELIAN',
        ],
        'HARGA POKOK PENJUALAN' => [
            'PEMBELIAN BARANG DAGANG',
            'HPP PENJUALAN',
            'BEBAN PEMBELIAN',
        ],
        'BEBAN USAHA' => [
            'BEBAN PENJUALAN',
            'BEBAN UMUM',
        ],
        'PENDAPATAN DAN BEBAN LAINNYA' => [
            'PENDAPATAN LAINNYA (PL)',
            'BEBAN LAINNYA (BL)',
        ],
    ];

    private const AKL_ORDER = ['PENDAPATAN', 'HARGA POKOK PENJUALAN', 'BEBAN USAHA', 'PENDAPATAN DAN BEBAN LAINNYA'];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data jurnal tidak ditemukan pada XML.');
        }

        $startDate = trim((string) ($filters['Date.StartDate'] ?? $filters['Date_StartDate'] ?? $filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['Date.EndDate'] ?? $filters['Date_EndDate'] ?? $filters['EndDate'] ?? $filters['end_date'] ?? ''));

        $bulanB = Carbon::parse($endDate)->startOfMonth();
        $bulanA = $bulanB->copy()->subMonth()->startOfMonth();

        $lastDayOfStartMonth = null;
        if ($startDate !== '') {
            try {
                $lastDayOfStartMonth = Carbon::parse($startDate)->endOfMonth();
            } catch (Throwable) {
            }
        }

        $filtered = $this->applyFilters($allRows, $lastDayOfStartMonth);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $dataB = $this->filterByMonth($filtered, $bulanB);
        $dataA = $this->filterByMonth($filtered, $bulanA);

        if ($dataB === []) {
            throw new RuntimeException('Tidak ada data untuk periode Bulan B ('.(clone $bulanB)->locale('id')->isoFormat('MMM-YY').').');
        }

        $allItemsB = $this->computeItems($dataB);
        $allItemsA = $this->computeItems($dataA);

        $groupedB = $this->groupByAccountAndAkm($allItemsB);
        $groupedA = $this->groupByAccountAndAkm($allItemsA);

        $merged = $this->mergeMonths($groupedB, $groupedA);

        $totalPendapatanB = $this->computeTotalPendapatan($merged);
        $totalPendapatanA = $this->computeTotalPendapatan($merged);

        $sections = $this->buildSections($merged, $totalPendapatanB, $totalPendapatanA);

        $pendapatan = $this->findSectionTotal($sections, 'PENDAPATAN');
        $hpp = $this->findSectionTotal($sections, 'HARGA POKOK PENJUALAN');
        $bebanUsaha = $this->findSectionTotal($sections, 'BEBAN USAHA');
        $lainnya = $this->findSectionTotal($sections, 'PENDAPATAN DAN BEBAN LAINNYA');

        $labaKotorB = $pendapatan['subtotal_b'] - $hpp['subtotal_b'];
        $labaKotorA = $pendapatan['subtotal_a'] - $hpp['subtotal_a'];
        $labaUsahaB = $labaKotorB - $bebanUsaha['subtotal_b'];
        $labaUsahaA = $labaKotorA - $bebanUsaha['subtotal_a'];
        $labaBersihB = $labaUsahaB + $lainnya['subtotal_b'];
        $labaBersihA = $labaUsahaA + $lainnya['subtotal_a'];

        $period = $this->resolvePeriod($bulanB);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $period['label'],
            'start_date' => $period['start'],
            'end_date' => $period['end'],
            'bulan_b_label' => (clone $bulanB)->locale('id')->isoFormat('MMM-YY'),
            'bulan_a_label' => (clone $bulanA)->locale('id')->isoFormat('MMM-YY'),
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
            'sections' => $sections,
            'calculations' => [
                [
                    'label' => 'LABA (RUGI) KOTOR',
                    'amount_b' => $labaKotorB,
                    'amount_a' => $labaKotorA,
                    'rasio_b' => 0,
                    'rasio_a' => 0,
                    'selisih' => $this->computeSelisih($labaKotorB, $labaKotorA),
                ],
                [
                    'label' => 'LABA (RUGI) USAHA / OPERASIONAL',
                    'amount_b' => $labaUsahaB,
                    'amount_a' => $labaUsahaA,
                    'rasio_b' => 0,
                    'rasio_a' => 0,
                    'selisih' => $this->computeSelisih($labaUsahaB, $labaUsahaA),
                ],
                [
                    'label' => 'LABA (RUGI) BERSIH SEBELUM PAJAK',
                    'amount_b' => $labaBersihB,
                    'amount_a' => $labaBersihA,
                    'rasio_b' => 0,
                    'rasio_a' => 0,
                    'selisih' => $this->computeSelisih($labaBersihB, $labaBersihA),
                ],
                [
                    'label' => 'LABA (RUGI) BERSIH SETELAH PAJAK',
                    'amount_b' => $labaBersihB,
                    'amount_a' => $labaBersihA,
                    'rasio_b' => 0,
                    'rasio_a' => 0,
                    'selisih' => $this->computeSelisih($labaBersihB, $labaBersihA),
                ],
            ],
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'invoices') {
                continue;
            }

            $recordXml = $reader->readOuterXml();
            if (! is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $cleanKey = $this->cleanXmlKey((string) $key);
                $row[$cleanKey] = trim((string) $value);
            }

            if (($row['Account Code'] ?? '') !== '') {
                $rows[] = $row;
            }
        }

        $reader->close();

        return $rows;
    }

    private function cleanXmlKey(string $key): string
    {
        $key = str_replace('_x0020_', ' ', $key);
        $key = str_replace('_x0028_', '(', $key);
        $key = str_replace('_x0029_', ')', $key);

        return str_replace('_x002F_', '/', $key);
    }

    private function applyFilters(array $rows, ?Carbon $lastDayOfStartMonth): array
    {
        $result = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');

            $akm = $this->resolveAkm($accountCode);

            if ($akm === 'A') {
                continue;
            }

            if ($accountCode === '721.000.171' && $lastDayOfStartMonth !== null) {
                $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
                if ($voucherDate !== '') {
                    try {
                        $vd = Carbon::parse($voucherDate);
                        if (! $vd->greaterThan($lastDayOfStartMonth)) {
                            continue;
                        }
                    } catch (Throwable) {
                        continue;
                    }
                }
            }

            $row['_akm'] = $akm;
            $result[] = $row;
        }

        return $result;
    }

    private function resolveAkm(string $accountCode): string
    {
        if ($accountCode === '711.000.091') {
            return 'BEBAN UMUM';
        }

        $prefix = substr($accountCode, 0, 7);

        return self::PREFIX_TO_AKM[$prefix] ?? 'A';
    }

    private function filterByMonth(array $rows, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return array_values(array_filter($rows, static function (array $row) use ($start, $end): bool {
            $dateStr = trim((string) ($row['Voucher Date'] ?? ''));
            if ($dateStr === '') {
                return false;
            }

            try {
                $date = Carbon::parse($dateStr);

                return $date->greaterThanOrEqualTo($start) && $date->lessThanOrEqualTo($end);
            } catch (Throwable) {
                return false;
            }
        }));
    }

    private function computeItems(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $akm = (string) ($row['_akm'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            $raw = $amountDb - $amountCr;

            $items[] = [
                'akm' => $akm,
                'account_name' => $accountName,
                'amount' => $raw,
            ];
        }

        return $items;
    }

    private function groupByAccountAndAkm(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $key = $item['akm'].'|||'.$item['account_name'];

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'akm' => $item['akm'],
                    'account_name' => $item['account_name'],
                    'amount' => 0,
                ];
            }

            $grouped[$key]['amount'] += $item['amount'];
        }

        return array_values($grouped);
    }

    private function mergeMonths(array $groupedB, array $groupedA): array
    {
        $merged = [];

        $allKeys = [];
        foreach ($groupedB as $item) {
            $key = $item['akm'].'|||'.$item['account_name'];
            $allKeys[$key] = true;
        }
        foreach ($groupedA as $item) {
            $key = $item['akm'].'|||'.$item['account_name'];
            $allKeys[$key] = true;
        }

        $byKey = [];
        foreach ($groupedB as $item) {
            $key = $item['akm'].'|||'.$item['account_name'];
            $byKey[$key] = [
                'akm' => $item['akm'],
                'account_name' => $item['account_name'],
                'amount_b' => $item['amount'],
                'amount_a' => 0,
            ];
        }
        foreach ($groupedA as $item) {
            $key = $item['akm'].'|||'.$item['account_name'];
            if (isset($byKey[$key])) {
                $byKey[$key]['amount_a'] = $item['amount'];
            } else {
                $byKey[$key] = [
                    'akm' => $item['akm'],
                    'account_name' => $item['account_name'],
                    'amount_b' => 0,
                    'amount_a' => $item['amount'],
                ];
            }
        }

        return array_values($byKey);
    }

    private function computeTotalPendapatan(array $merged): array
    {
        $totalB = 0;
        $totalA = 0;

        foreach ($merged as $item) {
            $akm = $item['akm'];
            $sign = self::RASIO_SIGN[$akm] ?? 1;

            $totalB += $sign * abs($item['amount_b']);
            $totalA += $sign * abs($item['amount_a']);
        }

        return [
            'signed_b' => $totalB,
            'signed_a' => $totalA,
            'abs_b' => abs($totalB),
            'abs_a' => abs($totalA),
        ];
    }

    private function buildSections(array $merged, array $totalPendapatanB, array $totalPendapatanA): array
    {
        $sections = [];

        foreach (self::AKL_ORDER as $akl) {
            $akmList = self::AKL_GROUPS[$akl] ?? [];

            $akmGroups = [];

            foreach ($akmList as $akm) {
                $akmItems = array_values(array_filter($merged, static fn (array $item): bool => $item['akm'] === $akm));

                if ($akmItems === []) {
                    continue;
                }

                $items = [];
                $subtotalB = 0;
                $subtotalA = 0;

                foreach ($akmItems as $item) {
                    $absB = abs($item['amount_b']);
                    $absA = abs($item['amount_a']);
                    $sign = self::RASIO_SIGN[$akm] ?? 1;

                    $subtotalB += $sign * $absB;
                    $subtotalA += $sign * $absA;

                    $items[] = [
                        'account_name' => $item['account_name'],
                        'amount_b' => $item['amount_b'],
                        'amount_a' => $item['amount_a'],
                        'rasio_b' => $totalPendapatanB['abs_b'] > 0 ? round($sign * $absB / $totalPendapatanB['abs_b'] * 100, 2) : 0,
                        'rasio_a' => $totalPendapatanA['abs_a'] > 0 ? round($sign * $absA / $totalPendapatanA['abs_a'] * 100, 2) : 0,
                        'selisih' => $this->computeSelisih($sign * $absB, $sign * $absA),
                    ];
                }

                $rasioB = $totalPendapatanB['abs_b'] > 0 ? round($subtotalB / $totalPendapatanB['abs_b'] * 100, 2) : 0;
                $rasioA = $totalPendapatanA['abs_a'] > 0 ? round($subtotalA / $totalPendapatanA['abs_a'] * 100, 2) : 0;

                $akmGroups[] = [
                    'akm' => $akm,
                    'items' => $items,
                    'subtotal_b' => $subtotalB,
                    'subtotal_a' => $subtotalA,
                    'rasio_b' => $rasioB,
                    'rasio_a' => $rasioA,
                    'selisih' => $this->computeSelisih($subtotalB, $subtotalA),
                ];
            }

            if ($akmGroups === []) {
                continue;
            }

            $sectionB = array_sum(array_map(static fn (array $g): float => $g['subtotal_b'], $akmGroups));
            $sectionA = array_sum(array_map(static fn (array $g): float => $g['subtotal_a'], $akmGroups));
            $rasioB = $totalPendapatanB['abs_b'] > 0 ? round($sectionB / $totalPendapatanB['abs_b'] * 100, 2) : 0;
            $rasioA = $totalPendapatanA['abs_a'] > 0 ? round($sectionA / $totalPendapatanA['abs_a'] * 100, 2) : 0;

            $sections[] = [
                'akl' => $akl,
                'akm_groups' => $akmGroups,
                'subtotal_b' => $sectionB,
                'subtotal_a' => $sectionA,
                'rasio_b' => $rasioB,
                'rasio_a' => $rasioA,
                'selisih' => $this->computeSelisih($sectionB, $sectionA),
            ];
        }

        return $sections;
    }

    private function computeSelisih(float $b, float $a): float
    {
        if (abs($a) < 0.01) {
            return $b >= 0 ? 100 : -100;
        }

        return round(($b - $a) / abs($a) * 100, 2);
    }

    private function findSectionTotal(array $sections, string $akl): array
    {
        foreach ($sections as $section) {
            if ($section['akl'] === $akl) {
                return [
                    'subtotal_b' => $section['subtotal_b'],
                    'subtotal_a' => $section['subtotal_a'],
                ];
            }
        }

        return ['subtotal_b' => 0, 'subtotal_a' => 0];
    }

    private function resolvePeriod(Carbon $bulanB): array
    {
        $startB = $bulanB->copy()->startOfMonth();
        $endB = $bulanB->copy()->endOfMonth();

        return [
            'start' => $startB->locale('id')->isoFormat('DD-MMM-YY'),
            'end' => $endB->locale('id')->isoFormat('DD-MMM-YY'),
            'label' => 'Dari '.$startB->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$endB->locale('id')->isoFormat('DD-MMM-YY'),
        ];
    }
}
