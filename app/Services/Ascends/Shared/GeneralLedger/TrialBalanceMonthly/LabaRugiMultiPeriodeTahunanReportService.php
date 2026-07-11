<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalanceMonthly;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LabaRugiMultiPeriodeTahunanReportService
{
    private const TITLE = 'Laporan Laba Rugi Multi Periode (Tahunan)';

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

        [$minDate, $maxDate] = $this->resolveDateRange($filtered);
        $periodLabel = 'Periode '.$minDate->locale('id')->isoFormat('MMM-YY').' s/d '.$maxDate->locale('id')->isoFormat('MMM-YY');

        $grouped = $this->groupByAccount($filtered);
        $sections = $this->buildSections($grouped);

        $grandTotal = 0;
        foreach ($sections as $section) {
            $grandTotal += $section['subtotal'];
        }

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'sections' => $sections,
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

    private function resolveDateRange(array $rows): array
    {
        $min = null;
        $max = null;

        foreach ($rows as $row) {
            $dateStr = (string) ($row['PeriodDate'] ?? '');
            if ($dateStr === '') {
                continue;
            }
            try {
                $d = Carbon::parse($dateStr);
                if ($min === null || $d->lessThan($min)) {
                    $min = $d;
                }
                if ($max === null || $d->greaterThan($max)) {
                    $max = $d;
                }
            } catch (Throwable) {
            }
        }

        return [$min ?? now(), $max ?? now()];
    }

    private function groupByAccount(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $ac1 = (string) ($row['AccountCode1'] ?? '');
            $an1 = (string) ($row['AccountName1'] ?? '');
            $an2 = (string) ($row['AccountName2'] ?? '');
            $an5 = (string) ($row['AccountName5'] ?? '');
            $sign = self::SECTION_SIGN[$ac1] ?? 1;
            $balance = (float) ($row['Balance'] ?? 0) * $sign;

            $displayName = $this->resolveDisplayName($ac1, $an1, $an2, $an5);

            $key = "{$ac1}|||{$displayName}";
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'section_code' => $ac1,
                    'section_name' => $an1,
                    'display_name' => $displayName,
                    'total' => 0.0,
                ];
            }

            $groups[$key]['total'] += $balance;
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

    private function buildSections(array $grouped): array
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

            $items = [];
            $subtotal = 0.0;

            usort($sectionItems, fn ($a, $b) => strcmp($a['display_name'], $b['display_name']));

            foreach ($sectionItems as $item) {
                $items[] = [
                    'account_name' => $item['display_name'],
                    'amount' => $item['total'],
                ];
                $subtotal += $item['total'];
            }

            $sections[] = [
                'section_code' => $code,
                'section_name' => $sectionName,
                'items' => $items,
                'subtotal' => $subtotal,
            ];
        }

        return $sections;
    }
}
