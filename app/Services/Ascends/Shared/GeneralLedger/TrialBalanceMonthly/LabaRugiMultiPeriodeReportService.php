<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalanceMonthly;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LabaRugiMultiPeriodeReportService
{
    private const TITLE = 'Laporan Laba Rugi Multi Periode';

    private const SECTION_MAP = [
        '400.000.000' => 'PENJUALAN DAN PENDAPATAN',
        '500.000.000' => 'Harga Pokok Produksi',
        '516.000.000' => 'Harga Pokok Penjualan',
        '516.000.200' => 'HPP PL Furniture',
        '516.000.400' => 'HPP Enamel',
        '516.000.903' => 'HPP PL Lemari',
        '516.000.904' => 'HPP Furniture Lipat',
        '600.000.000' => 'Pembelian Barang Dagangan',
        '700.000.000' => 'BEBAN USAHA',
        '800.000.000' => 'PENDAPATAN LAINNYA',
        '900.000.000' => 'BEBAN LAINNYA',
    ];

    private const EXCLUDED_PREFIX = '399.999.999';

    private const SECTION_SIGN = [
        '400.000.000' => 1,
        '500.000.000' => -1,
        '516.000.000' => -1,
        '516.000.200' => -1,
        '516.000.400' => -1,
        '516.000.903' => -1,
        '516.000.904' => -1,
        '600.000.000' => 1,
        '700.000.000' => -1,
        '800.000.000' => 1,
        '900.000.000' => -1,
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload'): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $filtered = $this->applySelectionFormula($allRows);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $periods = $this->extractPeriods($filtered);
        $grouped = $this->groupByAccount($filtered, $periods);
        $sections = $this->buildSections($grouped, $periods);

        $grandTotals = [];
        $grandTotal = 0;
        foreach ($periods as $i => $period) {
            $sum = 0;
            foreach ($sections as $section) {
                $sum += $section['subtotals'][$i];
            }
            $grandTotals[] = $sum;
            $grandTotal += $sum;
        }

        $minDate = Carbon::parse($filtered[0]['PeriodDate']);
        $maxDate = Carbon::parse($filtered[0]['PeriodDate']);
        foreach ($filtered as $row) {
            $d = Carbon::parse($row['PeriodDate']);
            if ($d->lessThan($minDate)) {
                $minDate = $d;
            }
            if ($d->greaterThan($maxDate)) {
                $maxDate = $d;
            }
        }

        $periodLabels = array_map(fn ($p) => (clone $p)->locale('id')->isoFormat('MMM-YY'), $periods);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => 'Periode : '.$minDate->locale('id')->isoFormat('MMM-YY').' s/d '.$maxDate->locale('id')->isoFormat('MMM-YY'),
            'min_date' => $minDate->format('Y-m-d'),
            'max_date' => $maxDate->format('Y-m-d'),
            'periods' => $periodLabels,
            'period_count' => count($periods),
            'sections' => $sections,
            'grand_totals' => $grandTotals,
            'grand_total' => $grandTotal,
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
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'Table1') {
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

            if (($row['AccountCode1'] ?? '') !== '') {
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

    private function applySelectionFormula(array $rows): array
    {
        return array_values(array_filter($rows, function (array $row): bool {
            $ac1 = (string) ($row['AccountCode1'] ?? '');

            return ! str_starts_with($ac1, self::EXCLUDED_PREFIX);
        }));
    }

    private function extractPeriods(array $rows): array
    {
        $periodSet = [];
        foreach ($rows as $row) {
            $dateStr = (string) ($row['PeriodDate'] ?? '');
            if ($dateStr === '') {
                continue;
            }
            try {
                $date = Carbon::parse($dateStr)->startOfMonth();
                $key = $date->format('Y-m');
                $periodSet[$key] = $date;
            } catch (Throwable) {
            }
        }

        ksort($periodSet);

        return array_values($periodSet);
    }

    private function groupByAccount(array $rows, array $periods): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $ac1 = (string) ($row['AccountCode1'] ?? '');
            $an1 = (string) ($row['AccountName1'] ?? '');
            $ac4 = (string) ($row['AccountCode4'] ?? '');
            $ac5 = (string) ($row['AccountCode5'] ?? '');
            $an5 = (string) ($row['AccountName5'] ?? '');
            $an2 = (string) ($row['AccountName2'] ?? '');
            $sign = self::SECTION_SIGN[$ac1] ?? 1;
            $balance = (float) ($row['Balance'] ?? 0) * $sign;

            $displayName = $this->resolveDisplayName($ac1, $an1, $an2, $an5);
            $isBebanDireksi = $ac4 === '721.000.250';

            $periodStr = '';
            try {
                $periodStr = Carbon::parse((string) ($row['PeriodDate'] ?? ''))->startOfMonth()->format('Y-m');
            } catch (Throwable) {
                continue;
            }

            $key = "{$ac1}|||{$displayName}";
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'section_code' => $ac1,
                    'section_name' => $an1,
                    'display_name' => $displayName,
                    'is_beban_direksi' => $isBebanDireksi,
                    'periods' => [],
                ];
                foreach ($periods as $p) {
                    $groups[$key]['periods'][$p->format('Y-m')] = 0;
                }
            }

            if (isset($groups[$key]['periods'][$periodStr])) {
                $groups[$key]['periods'][$periodStr] += $balance;
            }
        }

        return array_values($groups);
    }

    private function resolveDisplayName(string $ac1, string $an1, string $an2, string $an5): string
    {
        if (in_array($ac1, ['800.000.000', '900.000.000'], true)) {
            return $an2 !== '' ? $an2 : ($an5 !== '' ? $an5 : $an1);
        }

        return $an5 !== '' ? $an5 : ($an2 !== '' ? $an2 : $an1);
    }

    private function buildSections(array $grouped, array $periods): array
    {
        $sections = [];

        $prefixOrder = ['400', '500', '516', '600', '700', '800', '900'];
        $allCodes = array_unique(array_map(fn ($g) => $g['section_code'], $grouped));
        sort($allCodes);

        $codeGroups = [];
        foreach ($allCodes as $code) {
            $prefix = substr($code, 0, 3);
            $codeGroups[$prefix][] = $code;
        }

        $sectionOrder = [];
        foreach ($prefixOrder as $prefix) {
            if (isset($codeGroups[$prefix])) {
                sort($codeGroups[$prefix]);
                $sectionOrder = array_merge($sectionOrder, $codeGroups[$prefix]);
            }
        }

        foreach ($sectionOrder as $code) {
            $sectionName = self::SECTION_MAP[$code] ?? $code;
            $sectionItems = array_values(array_filter($grouped, fn ($g) => $g['section_code'] === $code));

            if ($sectionItems === []) {
                continue;
            }

            if ($code === '700.000.000') {
                $subSections = $this->build700SubSections($sectionItems, $periods);
                $subtotals = [];
                $subtotalTotal = 0;
                foreach ($periods as $i => $p) {
                    $sum = 0;
                    foreach ($subSections as $ss) {
                        $sum += $ss['subtotals'][$i];
                    }
                    $subtotals[] = $sum;
                    $subtotalTotal += $sum;
                }

                $sections[] = [
                    'section_code' => $code,
                    'section_name' => $sectionName,
                    'sub_sections' => $subSections,
                    'subtotals' => $subtotals,
                    'subtotal_total' => $subtotalTotal,
                ];
            } else {
                $items = [];
                $subtotals = [];
                $subtotalTotal = 0;

                foreach ($periods as $i => $p) {
                    $subtotals[$i] = 0;
                }

                usort($sectionItems, fn ($a, $b) => strcmp($a['display_name'], $b['display_name']));

                foreach ($sectionItems as $item) {
                    $amounts = [];
                    $total = 0;
                    foreach ($periods as $p) {
                        $amt = $item['periods'][$p->format('Y-m')] ?? 0;
                        $amounts[] = $amt;
                        $total += $amt;
                    }
                    $items[] = [
                        'account_name' => $item['display_name'],
                        'amounts' => $amounts,
                        'total' => $total,
                    ];

                    foreach ($periods as $i => $p) {
                        $subtotals[$i] += $item['periods'][$p->format('Y-m')] ?? 0;
                    }
                    $subtotalTotal += $total;
                }

                $sections[] = [
                    'section_code' => $code,
                    'section_name' => $sectionName,
                    'sub_sections' => [
                        [
                            'sub_name' => $sectionName,
                            'items' => $items,
                            'subtotals' => $subtotals,
                            'subtotal_total' => $subtotalTotal,
                        ],
                    ],
                    'subtotals' => $subtotals,
                    'subtotal_total' => $subtotalTotal,
                ];
            }
        }

        return $sections;
    }

    private function build700SubSections(array $items, array $periods): array
    {
        $regular = [];
        $direksi = [];

        foreach ($items as $item) {
            if ($item['is_beban_direksi']) {
                $direksi[] = $item;
            } else {
                $regular[] = $item;
            }
        }

        $subSections = [];

        if ($regular !== []) {
            usort($regular, fn ($a, $b) => strcmp($a['display_name'], $b['display_name']));
            $regularItems = [];
            $subtotals = [];
            $subtotalTotal = 0;
            foreach ($periods as $i => $p) {
                $subtotals[$i] = 0;
            }

            foreach ($regular as $item) {
                $amounts = [];
                $total = 0;
                foreach ($periods as $p) {
                    $amt = $item['periods'][$p->format('Y-m')] ?? 0;
                    $amounts[] = $amt;
                    $total += $amt;
                }
                $regularItems[] = [
                    'account_name' => $item['display_name'],
                    'amounts' => $amounts,
                    'total' => $total,
                ];
                foreach ($periods as $i => $p) {
                    $subtotals[$i] += $item['periods'][$p->format('Y-m')] ?? 0;
                }
                $subtotalTotal += $total;
            }

            $subSections[] = [
                'sub_name' => 'Beban Usaha',
                'items' => $regularItems,
                'subtotals' => $subtotals,
                'subtotal_total' => $subtotalTotal,
            ];
        }

        if ($direksi !== []) {
            usort($direksi, fn ($a, $b) => strcmp($a['display_name'], $b['display_name']));
            $direksiItems = [];
            $subtotals = [];
            $subtotalTotal = 0;
            foreach ($periods as $i => $p) {
                $subtotals[$i] = 0;
            }

            foreach ($direksi as $item) {
                $amounts = [];
                $total = 0;
                foreach ($periods as $p) {
                    $amt = $item['periods'][$p->format('Y-m')] ?? 0;
                    $amounts[] = $amt;
                    $total += $amt;
                }
                $direksiItems[] = [
                    'account_name' => $item['display_name'],
                    'amounts' => $amounts,
                    'total' => $total,
                ];
                foreach ($periods as $i => $p) {
                    $subtotals[$i] += $item['periods'][$p->format('Y-m')] ?? 0;
                }
                $subtotalTotal += $total;
            }

            $subSections[] = [
                'sub_name' => 'Beban Direksi',
                'items' => $direksiItems,
                'subtotals' => $subtotals,
                'subtotal_total' => $subtotalTotal,
            ];
        }

        return $subSections;
    }
}
