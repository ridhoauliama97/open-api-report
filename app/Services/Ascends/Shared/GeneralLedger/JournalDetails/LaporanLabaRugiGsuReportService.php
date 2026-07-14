<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LaporanLabaRugiGsuReportService
{
    private const TITLE = 'Laporan Laba Rugi';

    private const PREFIX_TO_AKM = [
        '411.000' => 'PENJUALAN',
        '412.000' => 'PENJUALAN',
        '451.000' => 'RETUR PENJUALAN',
        '431.000' => 'POTONGAN PENJUALAN',
        '516.000' => 'HPP PENJUALAN',
        '611.000' => 'PEMBELIAN BARANG DAGANG',
        '621.000' => 'PEMBELIAN BARANG DAGANG',
        '622.000' => 'PEMBELIAN BARANG DAGANG',
        '641.000' => 'BEBAN PEMBELIAN',
        '642.000' => 'BEBAN PEMBELIAN',
        '711.000' => 'BEBAN PENJUALAN',
        '712.000' => 'BEBAN MARKETING',
        '721.000' => 'BEBAN UMUM',
        '800.000' => 'PENDAPATAN LAINNYA (PL)',
        '900.000' => 'BEBAN LAINNYA (BL)',
        '421.001' => 'PENDAPATAN JASA TENAGA AHLI',
        '421.002' => 'PENDAPATAN JASA SEWA',
        '421.003' => 'PENDAPATAN JASA PRODUKSI',
        '421.004' => 'PENDAPATAN JASA PEMBELIAN',
    ];

    private const FORCE_POSITIVE_AMOUNT = [
        'PENJUALAN',
        'PENDAPATAN JASA TENAGA AHLI',
        'PENDAPATAN JASA SEWA',
        'PENDAPATAN JASA PRODUKSI',
        'PENDAPATAN JASA PEMBELIAN',
        'HPP PENJUALAN',
        'PEMBELIAN BARANG DAGANG',
        'BEBAN PEMBELIAN',
        'BEBAN PENJUALAN',
        'BEBAN MARKETING',
        'BEBAN UMUM',
    ];

    private const FORCE_NEGATIVE_AMOUNT = [
        'POTONGAN PENJUALAN',
        'RETUR PENJUALAN',
    ];

    private const FORCE_POSITIVE_RASIO = [
        'PENJUALAN',
        'PENDAPATAN JASA TENAGA AHLI',
        'PENDAPATAN JASA SEWA',
        'PENDAPATAN JASA PRODUKSI',
        'PENDAPATAN JASA PEMBELIAN',
    ];

    private const FORCE_NEGATIVE_RASIO = [
        'POTONGAN PENJUALAN',
        'RETUR PENJUALAN',
        'HPP PENJUALAN',
        'PEMBELIAN BARANG DAGANG',
        'BEBAN PEMBELIAN',
        'BEBAN PENJUALAN',
        'BEBAN MARKETING',
        'BEBAN UMUM',
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
        ],
        'BEBAN USAHA' => [
            'BEBAN MARKETING',
            'BEBAN PEMBELIAN',
            'BEBAN PENJUALAN',
            'BEBAN UMUM',
        ],
        'PENDAPATAN DAN BEBAN LAINNYA' => [
            'BEBAN LAINNYA (BL)',
            'PENDAPATAN LAINNYA (PL)',
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

        $labaKotorB = $pendapatan['subtotal_b'] + $hpp['subtotal_b'];
        $labaKotorA = $pendapatan['subtotal_a'] + $hpp['subtotal_a'];
        $labaUsahaB = $labaKotorB + $bebanUsaha['subtotal_b'];
        $labaUsahaA = $labaKotorA + $bebanUsaha['subtotal_a'];
        $labaSebelumPajakB = $labaUsahaB + $lainnya['subtotal_b'];
        $labaSebelumPajakA = $labaUsahaA + $lainnya['subtotal_a'];

        $pajakB = $this->sumAccountTotal($dataB, '721.000.171');
        $pajakA = $this->sumAccountTotal($dataA, '721.000.171');
        $signedPajakB = -1 * $pajakB;
        $signedPajakA = -1 * $pajakA;
        $labaBersihB = $labaSebelumPajakB + $signedPajakB;
        $labaBersihA = $labaSebelumPajakA + $signedPajakA;

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
                    'amount_b' => $labaSebelumPajakB,
                    'amount_a' => $labaSebelumPajakA,
                    'rasio_b' => 0,
                    'rasio_a' => 0,
                    'selisih' => $this->computeSelisih($labaSebelumPajakB, $labaSebelumPajakA),
                ],
                [
                    'label' => 'PAJAK',
                    'amount_b' => $signedPajakB,
                    'amount_a' => $signedPajakA,
                    'rasio_b' => $totalPendapatanB['abs_b'] > 0 ? round(-1 * abs($pajakB) / $totalPendapatanB['abs_b'] * 100, 2) : 0,
                    'rasio_a' => $totalPendapatanA['abs_a'] > 0 ? round(-1 * abs($pajakA) / $totalPendapatanA['abs_a'] * 100, 2) : 0,
                    'selisih' => $this->computeSelisih($signedPajakB, $signedPajakA),
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

            $row['_akm'] = $akm;
            $result[] = $row;
        }

        return $result;
    }

    private function resolveAkm(string $accountCode): string
    {
        if ($accountCode === '900.000.006') {
            return 'POTONGAN PENJUALAN';
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
            $accountCode = (string) ($row['Account Code'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            $raw = $amountCr - $amountDb;

            $items[] = [
                'akm' => $akm,
                'account_code' => $accountCode,
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
            $key = $item['akm'].'|||'.$item['account_code'].'|||'.$item['account_name'];

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'akm' => $item['akm'],
                    'account_code' => $item['account_code'],
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
            $key = $item['akm'].'|||'.$item['account_code'].'|||'.$item['account_name'];
            $allKeys[$key] = true;
        }
        foreach ($groupedA as $item) {
            $key = $item['akm'].'|||'.$item['account_code'].'|||'.$item['account_name'];
            $allKeys[$key] = true;
        }

        $byKey = [];
        foreach ($groupedB as $item) {
            $key = $item['akm'].'|||'.$item['account_code'].'|||'.$item['account_name'];
            $byKey[$key] = [
                'akm' => $item['akm'],
                'account_code' => $item['account_code'],
                'account_name' => $item['account_name'],
                'amount_b' => $item['amount'],
                'amount_a' => 0,
            ];
        }
        foreach ($groupedA as $item) {
            $key = $item['akm'].'|||'.$item['account_code'].'|||'.$item['account_name'];
            if (isset($byKey[$key])) {
                $byKey[$key]['amount_a'] = $item['amount'];
            } else {
                $byKey[$key] = [
                    'akm' => $item['akm'],
                    'account_code' => $item['account_code'],
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
            if ($item['akm'] !== 'PENJUALAN') {
                continue;
            }

            $totalB += abs($item['amount_b']);
            $totalA += abs($item['amount_a']);
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

                usort($akmItems, static fn (array $a, array $b): int => strcasecmp($a['account_name'], $b['account_name']));

                $items = [];
                $subtotalB = 0;
                $subtotalA = 0;

                $isPendapatan = $akl === 'PENDAPATAN';
                $forcePosAmt = in_array($akm, self::FORCE_POSITIVE_AMOUNT);
                $forceNegAmt = in_array($akm, self::FORCE_NEGATIVE_AMOUNT) && $isPendapatan;
                $forcePosRas = in_array($akm, self::FORCE_POSITIVE_RASIO);
                $forceNegRas = in_array($akm, self::FORCE_NEGATIVE_RASIO);

                foreach ($akmItems as $item) {
                    $rawB = (float) $item['amount_b'];
                    $rawA = (float) $item['amount_a'];

                    $amountB = $forcePosAmt ? abs($rawB) : ($forceNegAmt ? -1 * abs($rawB) : $rawB);
                    $amountA = $forcePosAmt ? abs($rawA) : ($forceNegAmt ? -1 * abs($rawA) : $rawA);

                    $rasioBaseB = $forcePosRas ? abs($rawB) : ($forceNegRas ? -1 * abs($rawB) : $rawB);
                    $rasioBaseA = $forcePosRas ? abs($rawA) : ($forceNegRas ? -1 * abs($rawA) : $rawA);

                    $isPajakAccount = $akm === 'BEBAN UMUM' && ($item['account_code'] ?? '') === '721.000.171';

                    if (! $isPajakAccount) {
                        $subtotalB += $rasioBaseB;
                        $subtotalA += $rasioBaseA;
                    }

                    $items[] = [
                        'account_name' => $item['account_name'],
                        'amount_b' => $amountB,
                        'amount_a' => $amountA,
                        'rasio_b' => $totalPendapatanB['abs_b'] > 0 ? round($rasioBaseB / $totalPendapatanB['abs_b'] * 100, 2) : 0,
                        'rasio_a' => $totalPendapatanA['abs_a'] > 0 ? round($rasioBaseA / $totalPendapatanA['abs_a'] * 100, 2) : 0,
                        'selisih' => $this->computeSelisih($rasioBaseB, $rasioBaseA),
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

    private function sumAccountTotal(array $rows, string $targetCode): float
    {
        $total = 0;

        foreach ($rows as $row) {
            $code = (string) ($row['Account Code'] ?? '');
            if ($code !== $targetCode) {
                continue;
            }

            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);
            $total += $amountCr - $amountDb;
        }

        return abs($total);
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
