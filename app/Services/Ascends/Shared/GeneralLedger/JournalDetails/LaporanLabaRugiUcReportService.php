<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LaporanLabaRugiUcReportService
{
    private const TITLE = 'Laporan Laba Rugi';

    private const ACCOUNT_CATEGORIES = [
        '411' => 'PENDAPATAN JASA PEMBELIAN',
        '412' => 'PENDAPATAN JASA PRODUKSI',
        '413' => 'PENDAPATAN JASA SEWA',
        '421' => 'PENDAPATAN JASA TENAGA AHLI',
        '421.001' => 'PENDAPATAN JASA TENAGA AHLI',
        '421.002' => 'PENDAPATAN JASA SEWA',
        '421.003' => 'PENDAPATAN JASA PRODUKSI',
        '421.004' => 'PENDAPATAN JASA PEMBELIAN',
        '711' => 'BEBAN UMUM',
        '721' => 'BEBAN_721',
        '800' => 'PENDAPATAN LAINNYA (PL)',
        '900' => 'BEBAN LAINNYA (BL)',
    ];

    private const BEBAN_DIREKSI_CODES = [
        '721.000.260', '721.000.261', '721.000.262', '721.000.263', '721.000.264',
        '721.000.265', '721.000.266', '721.000.267', '721.000.268', '721.000.269',
        '721.000.270', '721.000.251', '721.000.252', '721.000.253', '721.000.254',
        '721.000.255', '721.000.256', '721.000.257', '721.000.258', '721.000.259',
        '721.000.031', '721.000.271', '721.000.272',
    ];

    private const CATEGORY_ORDER = [
        'PENDAPATAN JASA PEMBELIAN',
        'PENDAPATAN JASA PRODUKSI',
        'PENDAPATAN JASA SEWA',
        'PENDAPATAN JASA TENAGA AHLI',
        'BEBAN UMUM',
        'BEBAN DIREKSI',
        'BEBAN LAINNYA (BL)',
        'PENDAPATAN LAINNYA (PL)',
    ];

    private const SECTION_GROUPS = [
        'PENDAPATAN' => [
            'PENDAPATAN JASA PEMBELIAN',
            'PENDAPATAN JASA PRODUKSI',
            'PENDAPATAN JASA SEWA',
            'PENDAPATAN JASA TENAGA AHLI',
        ],
        'BEBAN USAHA' => [
            'BEBAN UMUM',
        ],
        'PENDAPATAN DAN BEBAN LAINNYA' => [
            'BEBAN DIREKSI',
            'BEBAN LAINNYA (BL)',
            'PENDAPATAN LAINNYA (PL)',
        ],
    ];

    private const SECTION_ORDER = ['PENDAPATAN', 'BEBAN USAHA', 'PENDAPATAN DAN BEBAN LAINNYA'];

    private const SECTION_RASIO_SIGN = [
        'PENDAPATAN' => 1,
        'BEBAN USAHA' => -1,
        'PENDAPATAN DAN BEBAN LAINNYA' => null,
    ];

    private const CATEGORY_SIGN = [
        'PENDAPATAN JASA PEMBELIAN' => 1,
        'PENDAPATAN JASA PRODUKSI' => 1,
        'PENDAPATAN JASA SEWA' => 1,
        'PENDAPATAN JASA TENAGA AHLI' => 1,
        'BEBAN UMUM' => -1,
        'BEBAN DIREKSI' => -1,
        'BEBAN LAINNYA (BL)' => -1,
        'PENDAPATAN LAINNYA (PL)' => 1,
    ];

    private const AMOUNT_FLIP_CATEGORIES = ['BEBAN DIREKSI', 'BEBAN LAINNYA (BL)'];

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

        $filtered = $this->applyFilters($allRows, $bulanA);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $dataB = $this->filterByMonth($filtered, $bulanB);
        $dataA = $this->filterByMonth($filtered, $bulanA);

        if ($dataB === []) {
            throw new RuntimeException('Tidak ada data untuk periode Bulan B ('.(clone $bulanB)->locale('id')->isoFormat('MMM-YY').').');
        }

        $groupedB = $this->groupByCategoryAndAccount($dataB);
        $groupedA = $this->groupByCategoryAndAccount($dataA);

        $merged = $this->mergeMonths($groupedB, $groupedA);

        $totalPendapatanB = $this->computeTotalPendapatan($merged);
        $totalPendapatanA = $this->computeTotalPendapatan($merged);

        $sections = $this->buildSections($merged, $totalPendapatanB, $totalPendapatanA);

        foreach ($sections as &$sec) {
            if ($sec['section'] === 'PENDAPATAN DAN BEBAN LAINNYA') {
                $sec['rasio_b'] = $totalPendapatanB['b'] > 0 ? round($sec['subtotal_b'] / $totalPendapatanB['b'] * 100, 2) : 0;
                $sec['rasio_a'] = $totalPendapatanA['a'] > 0 ? -round(abs($sec['subtotal_a']) / $totalPendapatanA['a'] * 100, 2) : 0;
            }
            if ($sec['section'] === 'BEBAN USAHA') {
                $sec['rasio_b'] = $totalPendapatanB['b'] > 0 ? -round(abs($sec['subtotal_b']) / $totalPendapatanB['b'] * 100, 2) : 0;
                $sec['rasio_a'] = $totalPendapatanA['a'] > 0 ? -round(abs($sec['subtotal_a']) / $totalPendapatanA['a'] * 100, 2) : 0;
            }
        }
        unset($sec);

        $pendapatan = $this->findSectionTotal($sections, 'PENDAPATAN');
        $bebanUsaha = $this->findSectionTotal($sections, 'BEBAN USAHA');
        $lainnya = $this->findSectionTotal($sections, 'PENDAPATAN DAN BEBAN LAINNYA');

        $labaKotorB = $pendapatan['subtotal_b'];
        $labaKotorA = $pendapatan['subtotal_a'];
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
                    'rasio_b' => $totalPendapatanB['b'] > 0 ? 100 : 0,
                    'rasio_a' => $totalPendapatanA['a'] > 0 ? -100 : 0,
                    'selisih' => $this->computeSelisih($labaKotorB, $labaKotorA),
                ],
                [
                    'label' => 'LABA (RUGI) USAHA / OPERASIONAL',
                    'amount_b' => $labaUsahaB,
                    'amount_a' => $labaUsahaA,
                    'selisih' => $this->computeSelisih($labaUsahaB, $labaUsahaA),
                ],
                [
                    'label' => 'LABA (RUGI) BERSIH SEBELUM PAJAK',
                    'amount_b' => $labaBersihB,
                    'amount_a' => $labaBersihA,
                    'rasio_b' => $totalPendapatanB['b'] > 0 ? round($labaBersihB / $totalPendapatanB['b'] * 100, 2) : 0,
                    'rasio_a' => $totalPendapatanA['a'] > 0 ? -round(abs($labaBersihA) / $totalPendapatanA['a'] * 100, 2) : 0,
                    'selisih' => $this->computeSelisih($labaBersihB, $labaBersihA),
                ],
                [
                    'label' => 'PAJAK',
                    'amount_b' => 0,
                    'amount_a' => 0,
                    'selisih' => 0,
                ],
                [
                    'label' => 'LABA (RUGI) BERSIH SETELAH PAJAK',
                    'amount_b' => $labaBersihB,
                    'amount_a' => $labaBersihA,
                    'rasio_b' => $totalPendapatanB['b'] > 0 ? round($labaBersihB / $totalPendapatanB['b'] * 100, 2) : 0,
                    'rasio_a' => $totalPendapatanA['a'] > 0 ? -round(abs($labaBersihA) / $totalPendapatanA['a'] * 100, 2) : 0,
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

    private function applyFilters(array $rows, ?Carbon $bulanAwal): array
    {
        $result = [];
        $startYm = $bulanAwal !== null ? $bulanAwal->format('Y-m') : '';

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');
            $category = $this->resolveCategory($accountCode);

            if ($category === null) {
                continue;
            }

            if ($accountCode === '721.000.171' && $startYm !== '') {
                $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
                if ($voucherDate !== '') {
                    try {
                        $voucherYm = Carbon::parse($voucherDate)->format('Y-m');
                        if ($voucherYm < $startYm) {
                            continue;
                        }
                    } catch (Throwable) {
                        continue;
                    }
                }
            }

            $row['_category'] = $category;
            $result[] = $row;
        }

        return $result;
    }

    private function resolveCategory(string $accountCode): ?string
    {
        if (in_array($accountCode, self::BEBAN_DIREKSI_CODES, true)) {
            return 'BEBAN DIREKSI';
        }

        $prefix7 = substr($accountCode, 0, 7);
        if (isset(self::ACCOUNT_CATEGORIES[$prefix7])) {
            return self::ACCOUNT_CATEGORIES[$prefix7];
        }

        $prefix3 = substr($accountCode, 0, 3);

        if (! isset(self::ACCOUNT_CATEGORIES[$prefix3])) {
            return null;
        }

        $category = self::ACCOUNT_CATEGORIES[$prefix3];

        if ($category === 'BEBAN_721') {
            return 'BEBAN UMUM';
        }

        return $category;
    }

    private function filterByMonth(array $rows, Carbon $month): array
    {
        $targetYm = $month->format('Y-m');

        return array_values(array_filter($rows, static function (array $row) use ($targetYm): bool {
            $dateStr = trim((string) ($row['Voucher Date'] ?? ''));
            if ($dateStr === '') {
                return false;
            }

            try {
                $dateYm = Carbon::parse($dateStr)->format('Y-m');

                return $dateYm === $targetYm;
            } catch (Throwable) {
                return false;
            }
        }));
    }

    private function groupByCategoryAndAccount(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $category = (string) ($row['_category'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $accountCode = (string) ($row['Account Code'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            $prefix3 = substr($accountCode, 0, 3);
            $isRevenue = in_array($prefix3, ['411', '412', '413', '421', '800'], true);
            $amount = $isRevenue ? $amountCr - $amountDb : $amountDb - $amountCr;

            $key = $category.'|||'.$accountName;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'category' => $category,
                    'account_name' => $accountName,
                    'amount' => 0,
                ];
            }

            $grouped[$key]['amount'] += $amount;
        }

        return array_values($grouped);
    }

    private function mergeMonths(array $groupedB, array $groupedA): array
    {
        $byKey = [];

        foreach ($groupedB as $item) {
            $key = $item['category'].'|||'.$item['account_name'];
            $byKey[$key] = [
                'category' => $item['category'],
                'account_name' => $item['account_name'],
                'amount_b' => $item['amount'],
                'amount_a' => 0,
            ];
        }

        foreach ($groupedA as $item) {
            $key = $item['category'].'|||'.$item['account_name'];
            if (isset($byKey[$key])) {
                $byKey[$key]['amount_a'] = $item['amount'];
            } else {
                $byKey[$key] = [
                    'category' => $item['category'],
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

        $pendapatanCategories = self::SECTION_GROUPS['PENDAPATAN'];

        foreach ($merged as $item) {
            if (in_array($item['category'], $pendapatanCategories, true)) {
                $totalB += abs($item['amount_b']);
                $totalA += abs($item['amount_a']);
            }
        }

        return [
            'b' => $totalB,
            'a' => $totalA,
        ];
    }

    private function buildSections(array $merged, array $totalPendapatanB, array $totalPendapatanA): array
    {
        $sections = [];

        foreach (self::SECTION_ORDER as $sectionName) {
            $categoryList = self::SECTION_GROUPS[$sectionName] ?? [];

            $categoryGroups = [];

            foreach ($categoryList as $category) {
                $categoryItems = array_values(array_filter($merged, static fn (array $item): bool => $item['category'] === $category));

                if ($categoryItems === []) {
                    continue;
                }

                $sign = self::CATEGORY_SIGN[$category] ?? 1;
                $flipAmount = in_array($category, self::AMOUNT_FLIP_CATEGORIES, true);
                $items = [];
                $subtotalB = 0;
                $subtotalA = 0;

                foreach ($categoryItems as $item) {
                    $amountB = $item['amount_b'] * ($flipAmount ? -1 : 1);
                    $amountA = $item['amount_a'] * ($flipAmount ? -1 : 1);

                    $subtotalB += $amountB;
                    $subtotalA += $amountA;

                    $rasioB = $totalPendapatanB['b'] > 0 ? $sign * round(abs($amountB) / $totalPendapatanB['b'] * 100, 2) : 0;
                    $rasioA = $totalPendapatanA['a'] > 0 ? $sign * round(abs($amountA) / $totalPendapatanA['a'] * 100, 2) : 0;

                    $items[] = [
                        'account_name' => $item['account_name'],
                        'amount_b' => $amountB,
                        'amount_a' => $amountA,
                        'rasio_b' => $rasioB,
                        'rasio_a' => $rasioA,
                        'selisih' => $this->computeSelisih($amountB, $amountA),
                    ];
                }

                $rasioB = $totalPendapatanB['b'] > 0 ? $sign * round(abs($subtotalB) / $totalPendapatanB['b'] * 100, 2) : 0;
                $rasioA = $totalPendapatanA['a'] > 0 ? $sign * round(abs($subtotalA) / $totalPendapatanA['a'] * 100, 2) : 0;

                $categoryGroups[] = [
                    'category' => $category,
                    'items' => $items,
                    'subtotal_b' => $subtotalB,
                    'subtotal_a' => $subtotalA,
                    'rasio_b' => $rasioB,
                    'rasio_a' => $rasioA,
                    'selisih' => $this->computeSelisih($subtotalB, $subtotalA),
                ];
            }

            if ($categoryGroups === []) {
                continue;
            }

            $sectionB = array_sum(array_map(static fn (array $g): float => $g['subtotal_b'], $categoryGroups));
            $sectionA = array_sum(array_map(static fn (array $g): float => $g['subtotal_a'], $categoryGroups));
            $sectionSign = self::SECTION_RASIO_SIGN[$sectionName] ?? 1;
            if ($sectionSign === null) {
                $sectionSign = $sectionA >= 0 ? 1 : -1;
            }
            $absB = round(abs($sectionB) / $totalPendapatanB['b'] * 100, 2);
            $absA = round(abs($sectionA) / $totalPendapatanA['a'] * 100, 2);
            $rasioB = $totalPendapatanB['b'] > 0 ? ($sectionSign >= 0 ? $absB : 0.0 - $absB) : 0;
            $rasioA = $totalPendapatanA['a'] > 0 ? ($sectionSign >= 0 ? $absA : 0.0 - $absA) : 0;

            $sections[] = [
                'section' => $sectionName,
                'category_groups' => $categoryGroups,
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

    private function findSectionTotal(array $sections, string $sectionName): array
    {
        foreach ($sections as $section) {
            if ($section['section'] === $sectionName) {
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
