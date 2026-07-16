<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class LabaRugiUCReportService
{
    private const TITLE = 'Laporan Laba Rugi';

    private const AKM_MAP = [
        '411' => 'PENJUALAN',
        '412' => 'PENJUALAN',
        '451' => 'RETUR PENJUALAN',
        '431' => 'POTONGAN PENJUALAN',
        '516' => 'HPP PENJUALAN',
        '621' => 'PEMBELIAN BARANG DAGANG',
        '641' => 'BEBAN PEMBELIAN',
        '642' => 'BEBAN PEMBELIAN',
        '711' => 'BEBAN PENJUALAN',
        '800' => 'PENDAPATAN LAINNYA (PL)',
        '900' => 'BEBAN LAINNYA (BL)',
        '421' => 'PENDAPATAN JASA TENAGA AHLI',
    ];

    private const AKL_MAP = [
        'PENJUALAN' => 'PENDAPATAN',
        'POTONGAN PENJUALAN' => 'PENDAPATAN',
        'RETUR PENJUALAN' => 'PENDAPATAN',
        'PENDAPATAN JASA TENAGA AHLI' => 'PENDAPATAN',
        'PENDAPATAN JASA SEWA' => 'PENDAPATAN',
        'PENDAPATAN JASA PRODUKSI' => 'PENDAPATAN',
        'PENDAPATAN JASA PEMBELIAN' => 'PENDAPATAN',
        'PEMBELIAN BARANG DAGANG' => 'HARGA POKOK PENJUALAN',
        'HPP PENJUALAN' => 'HARGA POKOK PENJUALAN',
        'BEBAN PEMBELIAN' => 'HARGA POKOK PENJUALAN',
        'BEBAN PENJUALAN' => 'BEBAN USAHA',
        'BEBAN UMUM' => 'BEBAN USAHA',
        'PENDAPATAN LAINNYA (PL)' => 'PENDAPATAN DAN BEBAN LAINNYA',
        'BEBAN LAINNYA (BL)' => 'PENDAPATAN DAN BEBAN LAINNYA',
        'BEBAN DIREKSI' => 'PENDAPATAN DAN BEBAN LAINNYA',
    ];

    private const BEBAN_DIREKSI_ACCOUNTS = [
        '721.000.251', '721.000.252', '721.000.253', '721.000.254', '721.000.255',
        '721.000.256', '721.000.257', '721.000.258', '721.000.259', '721.000.260',
        '721.000.261', '721.000.262', '721.000.263', '721.000.264', '721.000.265',
        '721.000.266', '721.000.267', '721.000.268', '721.000.269', '721.000.270',
        '721.000.271', '721.000.272', '721.000.031',
    ];

    private const BEBAN_UMUM_11_PREFIXES = [
        '721.000.01', '721.000.02', '721.000.04', '721.000.05', '721.000.06',
        '721.000.07', '721.000.08', '721.000.09', '721.000.11', '721.000.12',
        '721.000.13', '721.000.15', '721.000.17', '721.000.18', '721.000.19',
    ];

    private const BEBAN_UMUM_FULL_ACCOUNTS = [
        '721.000.201', '721.000.211', '721.000.212', '721.000.213', '721.000.991',
        '721.000.037', '721.000.034', '721.000.161', '721.000.036', '721.000.039',
        '721.000.072', '721.000.033',
    ];

    private const SECTION_ORDER = [
        'PENDAPATAN',
        'HARGA POKOK PENJUALAN',
        'BEBAN USAHA',
        'PENDAPATAN DAN BEBAN LAINNYA',
    ];

    private const CATEGORY_ORDER_PENDAPATAN = [
        'PENDAPATAN JASA PEMBELIAN',
        'PENDAPATAN JASA PRODUKSI',
        'PENDAPATAN JASA SEWA',
        'PENDAPATAN JASA TENAGA AHLI',
        'PENJUALAN',
        'POTONGAN PENJUALAN',
        'RETUR PENJUALAN',
    ];

    private const CATEGORY_ORDER_BEBAN_USAHA = [
        'BEBAN PENJUALAN',
        'BEBAN UMUM',
    ];

    private const CATEGORY_ORDER_LAINNYA = [
        'BEBAN DIREKSI',
        'BEBAN LAINNYA (BL)',
        'PENDAPATAN LAINNYA (PL)',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $dateStart = trim((string) ($filters['Date.StartDate'] ?? $filters['StartDate'] ?? ''));
        $dateEnd = trim((string) ($filters['Date.EndDate'] ?? $filters['EndDate'] ?? ''));

        $periodLabel = $this->buildPeriodLabel($dateStart, $dateEnd);

        $classified = $this->classifyRecords($allRows);

        $startCarbon = $dateStart !== '' ? $this->parseDateSafe($dateStart) : null;
        $endCarbon = $dateEnd !== '' ? $this->parseDateSafe($dateEnd) : null;

        $startMonth = $startCarbon?->month ?? 0;
        $startYear = $startCarbon?->year ?? 0;
        $endMonth = $endCarbon?->month ?? 0;
        $endYear = $endCarbon?->year ?? 0;

        $bulanBLabel = $startCarbon !== null
            ? $startCarbon->copy()->locale('id')->isoFormat('MMM - YYYY')
            : 'Jun - 2026';
        $bulanALabel = $endCarbon !== null
            ? $endCarbon->copy()->subMonth()->locale('id')->isoFormat('MMM - YYYY')
            : 'May - 2026';

        $sections = $this->buildSections($classified, $startMonth, $startYear, $endMonth, $endYear);

        $totalPendapatanB = 0;
        $totalPendapatanA = 0;
        foreach ($sections as $section) {
            if ($section['section'] === 'PENDAPATAN') {
                $totalPendapatanB = $section['subtotal_b'];
                $totalPendapatanA = $section['subtotal_a'];
                break;
            }
        }

        $sections = $this->applyRatios($sections, $totalPendapatanB, $totalPendapatanA);

        $calculations = $this->buildCalculations($sections, $totalPendapatanB, $totalPendapatanA);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'bulan_b_label' => $bulanBLabel,
            'bulan_a_label' => $bulanALabel,
            'total_pendapatan_b' => $totalPendapatanB,
            'total_pendapatan_a' => $totalPendapatanA,
            'sections' => $sections,
            'calculations' => $calculations,
            'printed_by' => '',
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
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'invoices') {
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

            $rows[] = $row;
        }

        $reader->close();

        return $rows;
    }

    private function cleanXmlKey(string $key): string
    {
        $key = str_replace('_x0020_', ' ', $key);
        $key = str_replace('_x0028_', '(', $key);
        $key = str_replace('_x0029_', ')', $key);
        $key = str_replace('_x0027_', "'", $key);
        $key = str_replace('_x002F_', '/', $key);
        $key = str_replace('_x0023_', '#', $key);
        $key = str_replace('_x002D_', '-', $key);

        return $key;
    }

    private function classifyRecords(array $rows): array
    {
        $classified = [];

        foreach ($rows as $row) {
            $accountCode = trim((string) ($row['Account Code'] ?? ''));
            $voucherDateRaw = trim((string) ($row['Voucher Date'] ?? ''));
            $amount = (float) ($row['Amount'] ?? 0);

            $akm = $this->getAkm($accountCode);
            if ($akm === null) {
                continue;
            }

            $akl = self::AKL_MAP[$akm] ?? null;
            if ($akl === null) {
                continue;
            }

            $voucherDate = null;
            try {
                $voucherDate = Carbon::parse($voucherDateRaw);
            } catch (\Throwable) {
                continue;
            }

            $accountName = trim((string) ($row['Account Name'] ?? ''));
            $accountGroupKey = $this->getAccountGroupKey($accountCode, $accountName);

            $classified[] = [
                'akm' => $akm,
                'akl' => $akl,
                'account_code' => $accountCode,
                'account_name' => $accountName,
                'account_group_key' => $accountGroupKey,
                'voucher_date' => $voucherDate,
                'amount' => $amount,
            ];
        }

        return $classified;
    }

    private function getAkm(string $accountCode): ?string
    {
        if ($accountCode === '') {
            return null;
        }

        if (in_array($accountCode, self::BEBAN_DIREKSI_ACCOUNTS, true)) {
            return 'BEBAN DIREKSI';
        }

        foreach (self::BEBAN_UMUM_11_PREFIXES as $prefix) {
            if (str_starts_with($accountCode, $prefix)) {
                return 'BEBAN UMUM';
            }
        }

        foreach (self::BEBAN_UMUM_FULL_ACCOUNTS as $full) {
            if (str_starts_with($accountCode, $full)) {
                return 'BEBAN UMUM';
            }
        }

        if (str_starts_with($accountCode, '721.000')) {
            return 'BEBAN UMUM';
        }

        $prefix3 = substr($accountCode, 0, 3);
        if (isset(self::AKM_MAP[$prefix3])) {
            $mapped = self::AKM_MAP[$prefix3];

            if ($prefix3 === '421') {
                $prefix7 = substr($accountCode, 0, 7);
                $map421 = [
                    '421.001' => 'PENDAPATAN JASA TENAGA AHLI',
                    '421.002' => 'PENDAPATAN JASA SEWA',
                    '421.003' => 'PENDAPATAN JASA PRODUKSI',
                    '421.004' => 'PENDAPATAN JASA PEMBELIAN',
                ];

                return $map421[$prefix7] ?? 'PENDAPATAN JASA TENAGA AHLI';
            }

            return $mapped;
        }

        return null;
    }

    private function getAccountGroupKey(string $accountCode, string $accountName): string
    {
        if ($accountName !== '') {
            return $accountName;
        }

        return $accountCode;
    }

    private function buildSections(array $classified, int $startMonth, int $startYear, int $endMonth, int $endYear): array
    {
        $itemsByCategory = [];

        foreach ($classified as $rec) {
            $akl = $rec['akl'];
            $akm = $rec['akm'];
            $groupKey = $rec['account_group_key'];

            $month = (int) $rec['voucher_date']->month;
            $year = (int) $rec['voucher_date']->year;
            $amount = -$rec['amount'];

            $inB = ($month >= $startMonth && $year >= $startYear) || $year > $startYear;
            $inA = ($month < $endMonth && $year <= $endYear) || $year < $endYear;

            $key = "{$akl}||{$akm}||{$groupKey}||{$rec['account_code']}";
            if (! isset($itemsByCategory[$key])) {
                $itemsByCategory[$key] = [
                    'akl' => $akl,
                    'akm' => $akm,
                    'account_code' => $rec['account_code'],
                    'account_name' => $rec['account_name'],
                    'group_key' => $groupKey,
                    'amount_b' => 0.0,
                    'amount_a' => 0.0,
                ];
            }

            if ($inB) {
                $itemsByCategory[$key]['amount_b'] += $amount;
            }
            if ($inA) {
                $itemsByCategory[$key]['amount_a'] += $amount;
            }
        }

        $sectionMap = [];
        foreach ($itemsByCategory as $item) {
            $sectionMap[$item['akl']][$item['akm']][$item['group_key']][] = $item;
        }

        $sections = [];
        foreach (self::SECTION_ORDER as $sectionName) {
            if (! isset($sectionMap[$sectionName])) {
                continue;
            }

            $categories = $sectionMap[$sectionName];
            $sectionSubtotalsB = 0.0;
            $sectionSubtotalsA = 0.0;
            $categoryGroups = [];

            $categoryOrder = $this->getCategoryOrder($sectionName);
            uksort($categories, function (string $a, string $b) use ($categoryOrder): int {
                $posA = array_search($a, $categoryOrder, true);
                $posB = array_search($b, $categoryOrder, true);
                if ($posA !== false && $posB !== false) {
                    return $posA <=> $posB;
                }
                if ($posA !== false) {
                    return -1;
                }
                if ($posB !== false) {
                    return 1;
                }

                return strcmp($a, $b);
            });

            foreach ($categories as $categoryName => $groupItems) {
                $categorySubtotalB = 0.0;
                $categorySubtotalA = 0.0;
                $items = [];

                uksort($groupItems, function (string $a, string $b): int {
                    $itemA = $groupItems[$a][0] ?? null;
                    $itemB = $groupItems[$b][0] ?? null;
                    $codeA = $itemA['account_code'] ?? $a;
                    $codeB = $itemB['account_code'] ?? $b;

                    return strcmp($codeA, $codeB);
                });

                foreach ($groupItems as $itemGroup) {
                    $merged = [
                        'account_name' => $itemGroup[0]['account_name'] ?: $itemGroup[0]['account_code'],
                        'amount_b' => 0.0,
                        'amount_a' => 0.0,
                    ];

                    foreach ($itemGroup as $subItem) {
                        $merged['amount_b'] += $subItem['amount_b'];
                        $merged['amount_a'] += $subItem['amount_a'];
                    }

                    $categorySubtotalB += $merged['amount_b'];
                    $categorySubtotalA += $merged['amount_a'];
                    $items[] = $merged;
                }

                $sectionSubtotalsB += $categorySubtotalB;
                $sectionSubtotalsA += $categorySubtotalA;

                $categoryGroups[] = [
                    'category' => $categoryName,
                    'subtotal_b' => $categorySubtotalB,
                    'subtotal_a' => $categorySubtotalA,
                    'items' => $items,
                ];
            }

            $sections[] = [
                'section' => $sectionName,
                'subtotal_b' => $sectionSubtotalsB,
                'subtotal_a' => $sectionSubtotalsA,
                'category_groups' => $categoryGroups,
            ];
        }

        return $sections;
    }

    private function getCategoryOrder(string $sectionName): array
    {
        return match ($sectionName) {
            'PENDAPATAN' => self::CATEGORY_ORDER_PENDAPATAN,
            'BEBAN USAHA' => self::CATEGORY_ORDER_BEBAN_USAHA,
            'PENDAPATAN DAN BEBAN LAINNYA' => self::CATEGORY_ORDER_LAINNYA,
            default => [],
        };
    }

    private function applyRatios(array $sections, float $totalB, float $totalA): array
    {
        foreach ($sections as &$section) {
            $section['rasio_b'] = $totalB != 0 ? ($section['subtotal_b'] / $totalB) * 100 : 0;
            $section['rasio_a'] = $totalA != 0 ? ($section['subtotal_a'] / $totalA) * 100 : 0;
            $section['selisih'] = $section['subtotal_a'] != 0
                ? (($section['subtotal_b'] - $section['subtotal_a']) / abs($section['subtotal_a'])) * 100
                : 0;

            foreach ($section['category_groups'] as &$category) {
                $category['rasio_b'] = $totalB != 0 ? ($category['subtotal_b'] / $totalB) * 100 : 0;
                $category['rasio_a'] = $totalA != 0 ? ($category['subtotal_a'] / $totalA) * 100 : 0;
                $category['selisih'] = $category['subtotal_a'] != 0
                    ? (($category['subtotal_b'] - $category['subtotal_a']) / abs($category['subtotal_a'])) * 100
                    : 0;

                foreach ($category['items'] as &$item) {
                    $item['rasio_b'] = $totalB != 0 ? ($item['amount_b'] / $totalB) * 100 : 0;
                    $item['rasio_a'] = $totalA != 0 ? ($item['amount_a'] / $totalA) * 100 : 0;
                    $item['selisih'] = $item['amount_a'] != 0
                        ? (($item['amount_b'] - $item['amount_a']) / abs($item['amount_a'])) * 100
                        : 0;
                }
                unset($item);
            }
            unset($category);
        }
        unset($section);

        foreach ($sections as &$section) {
            $isBebanUsaha = $section['section'] === 'BEBAN USAHA';

            $section['display_subtotal_b'] = $isBebanUsaha ? abs($section['subtotal_b']) : $section['subtotal_b'];
            $section['display_subtotal_a'] = $isBebanUsaha ? abs($section['subtotal_a']) : $section['subtotal_a'];

            foreach ($section['category_groups'] as &$category) {
                $category['display_subtotal_b'] = $isBebanUsaha ? abs($category['subtotal_b']) : $category['subtotal_b'];
                $category['display_subtotal_a'] = $isBebanUsaha ? abs($category['subtotal_a']) : $category['subtotal_a'];

                foreach ($category['items'] as &$item) {
                    $item['display_amount_b'] = $isBebanUsaha ? abs($item['amount_b']) : $item['amount_b'];
                    $item['display_amount_a'] = $isBebanUsaha ? abs($item['amount_a']) : $item['amount_a'];
                }
                unset($item);
            }
            unset($category);
        }
        unset($section);

        return $sections;
    }

    private function buildCalculations(array $sections, float $totalPendapatanB, float $totalPendapatanA): array
    {
        $sectionTotals = [];
        foreach ($sections as $s) {
            $sectionTotals[$s['section']] = [
                'b' => $s['subtotal_b'],
                'a' => $s['subtotal_a'],
            ];
        }

        $calculations = [];

        $pendapatanB = $sectionTotals['PENDAPATAN']['b'] ?? 0;
        $pendapatanA = $sectionTotals['PENDAPATAN']['a'] ?? 0;

        $hppB = $sectionTotals['HARGA POKOK PENJUALAN']['b'] ?? 0;
        $hppA = $sectionTotals['HARGA POKOK PENJUALAN']['a'] ?? 0;

        $bebanUsahaB = $sectionTotals['BEBAN USAHA']['b'] ?? 0;
        $bebanUsahaA = $sectionTotals['BEBAN USAHA']['a'] ?? 0;

        $lainnyaB = $sectionTotals['PENDAPATAN DAN BEBAN LAINNYA']['b'] ?? 0;
        $lainnyaA = $sectionTotals['PENDAPATAN DAN BEBAN LAINNYA']['a'] ?? 0;

        $labaKotorB = $pendapatanB + $hppB;
        $labaKotorA = $pendapatanA + $hppA;
        $calculations[] = [
            'label' => 'LABA (RUGI) KOTOR',
            'amount_b' => $labaKotorB,
            'rasio_b' => $totalPendapatanB != 0 ? ($labaKotorB / $totalPendapatanB) * 100 : 0,
            'amount_a' => $labaKotorA,
            'rasio_a' => $totalPendapatanA != 0 ? ($labaKotorA / $totalPendapatanA) * 100 : 0,
            'selisih' => $labaKotorA != 0
                ? (($labaKotorB - $labaKotorA) / abs($labaKotorA)) * 100
                : 0,
        ];

        $labaUsahaB = $labaKotorB + $bebanUsahaB;
        $labaUsahaA = $labaKotorA + $bebanUsahaA;
        $calculations[] = [
            'label' => 'LABA (RUGI) USAHA / OPERASIONAL',
            'amount_b' => $labaUsahaB,
            'rasio_b' => $totalPendapatanB != 0 ? ($labaUsahaB / $totalPendapatanB) * 100 : 0,
            'amount_a' => $labaUsahaA,
            'rasio_a' => $totalPendapatanA != 0 ? ($labaUsahaA / $totalPendapatanA) * 100 : 0,
            'selisih' => $labaUsahaA != 0
                ? (($labaUsahaB - $labaUsahaA) / abs($labaUsahaA)) * 100
                : 0,
        ];

        $labaSebelumPajakB = $labaUsahaB + $lainnyaB;
        $labaSebelumPajakA = $labaUsahaA + $lainnyaA;
        $calculations[] = [
            'label' => 'LABA (RUGI) BERSIH SEBELUM PAJAK',
            'amount_b' => $labaSebelumPajakB,
            'rasio_b' => $totalPendapatanB != 0 ? ($labaSebelumPajakB / $totalPendapatanB) * 100 : 0,
            'amount_a' => $labaSebelumPajakA,
            'rasio_a' => $totalPendapatanA != 0 ? ($labaSebelumPajakA / $totalPendapatanA) * 100 : 0,
            'selisih' => $labaSebelumPajakA != 0
                ? (($labaSebelumPajakB - $labaSebelumPajakA) / abs($labaSebelumPajakA)) * 100
                : 0,
        ];

        $pajakB = 0;
        $pajakA = 0;
        $calculations[] = [
            'label' => 'PAJAK',
            'amount_b' => $pajakB,
            'rasio_b' => 0,
            'amount_a' => $pajakA,
            'rasio_a' => 0,
            'selisih' => 0,
        ];

        $labaBersihB = $labaSebelumPajakB - $pajakB;
        $labaBersihA = $labaSebelumPajakA - $pajakA;
        $calculations[] = [
            'label' => 'LABA (RUGI) BERSIH SETELAH PAJAK',
            'amount_b' => $labaBersihB,
            'rasio_b' => $totalPendapatanB != 0 ? ($labaBersihB / $totalPendapatanB) * 100 : 0,
            'amount_a' => $labaBersihA,
            'rasio_a' => $totalPendapatanA != 0 ? ($labaBersihA / $totalPendapatanA) * 100 : 0,
            'selisih' => $labaBersihA != 0
                ? (($labaBersihB - $labaBersihA) / abs($labaBersihA)) * 100
                : 0,
        ];

        return $calculations;
    }

    private function buildPeriodLabel(string $dateStart, string $dateEnd): string
    {
        if ($dateStart !== '' && $dateEnd !== '') {
            $start = $this->formatDateSafe($dateStart);
            $end = $this->formatDateSafe($dateEnd);

            return "Dari {$start} s/d {$end}";
        }

        return '';
    }

    private function parseDateSafe(string $date): ?Carbon
    {
        if ($date === '') {
            return null;
        }

        try {
            $carbon = Carbon::createFromFormat('d/m/Y', $date);
            if ($carbon !== false) {
                return $carbon;
            }
        } catch (\Throwable) {
            // try next
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDateSafe(string $date): string
    {
        if ($date === '') {
            return '';
        }
        $c = $this->parseDateSafe($date);
        if ($c !== null) {
            return $c->locale('id')->isoFormat('DD-MMM-YY');
        }

        return $date;
    }
}
