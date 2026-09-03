<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PengirimanLemariTahunanReportService
{
    private const TITLE = 'Laporan Pengiriman Lemari (Tahunan)';

    private const CATEGORY_ORDER = [
        'plastik_kabinet_1',
        'plastik_kabinet_2',
    ];

    private const CATEGORY_LABELS = [
        'plastik_kabinet_1' => 'Plastik Kabinet 1',
        'plastik_kabinet_2' => 'Plastik Kabinet 2',
    ];

    private const FAMILY_MAP = [
        2879 => 'plastik_kabinet_1',
        2892 => 'plastik_kabinet_2',
    ];

    private const MONTH_NAMES = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $year = $this->resolveYear($filters, $rows);

        $filteredRows = $this->filterByYear($rows, $year);
        $filteredRows = $this->filterByGab($filteredRows);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data dalam tahun yang dipilih.');
        }

        $categories = $this->buildCategories($filteredRows, $year);

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'year' => (string) $year,
            'period_label' => $year !== '' ? 'Periode '.$year : '',
            'months' => self::MONTH_NAMES,
            'categories' => $categories,
        ];
    }

    private function parseRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML wajib dikirim.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid ({$sourceLabel}).");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'table') {
                continue;
            }

            $nodeXml = $reader->readOuterXml();
            if (! is_string($nodeXml) || trim($nodeXml) === '') {
                continue;
            }

            $node = simplexml_load_string($nodeXml);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $cleanKey = trim(str_replace('_x0020_', ' ', $key));
                $row[$cleanKey] = trim((string) $value);
            }

            $rows[] = $row;
        }

        $reader->close();

        return $rows;
    }

    private function resolveYear(array $filters, array $rows): string
    {
        $startDate = trim((string) (
            $filters['StartDate']
            ?? $filters['start_date']
            ?? $filters['DateRange.StartDate']
            ?? $filters['DateRange_StartDate']
            ?? ''
        ));

        if ($startDate !== '') {
            try {
                return (string) Carbon::parse($startDate)->year;
            } catch (Throwable) {
                // ignore
            }
        }

        foreach ($rows as $row) {
            $dateStr = (string) ($row['DateID'] ?? '');
            if ($dateStr !== '') {
                try {
                    return (string) Carbon::parse($dateStr)->year;
                } catch (Throwable) {
                    // ignore
                }
            }
        }

        return (string) date('Y');
    }

    private function filterByYear(array $rows, string $year): array
    {
        if ($year === '') {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            function (array $row) use ($year): bool {
                $dateStr = (string) ($row['DateID'] ?? '');
                if ($dateStr === '') {
                    return false;
                }
                try {
                    return (string) Carbon::parse($dateStr)->year === $year;
                } catch (Throwable) {
                    return false;
                }
            }
        ));
    }

    private function determineGab(string $itemName): string
    {
        $upper = strtoupper($itemName);
        if (str_contains($upper, 'PROMO LEM')) {
            return 'NOT';
        }

        return 'TAMPIL';
    }

    private function filterByGab(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->determineGab((string) ($row['ItemName'] ?? '')) === 'TAMPIL'
        ));
    }

    private function determineGrp(string $itemName): string
    {
        $upper = strtoupper($itemName);
        if (str_contains($upper, 'PK.53003') || str_contains($upper, 'PK 3003') || str_contains($upper, '3TX6P')) {
            return 'PINTU 6';
        }
        if (str_contains($upper, 'PK.53004') || str_contains($upper, 'PK 3004') || str_contains($upper, '4TX8P')) {
            return 'PINTU 8';
        }

        return '-';
    }

    private function formatItemName(string $itemName): string
    {
        return ' '.$itemName;
    }

    private function buildCategories(array $rows, string $year): array
    {
        $categorized = [];
        foreach ($rows as $row) {
            $familyId = (int) ($row['FamilyID'] ?? 0);
            $categoryKey = self::FAMILY_MAP[$familyId] ?? null;
            if ($categoryKey === null) {
                continue;
            }

            $categorized[$categoryKey][] = $row;
        }

        $categories = [];
        foreach (self::CATEGORY_ORDER as $key) {
            if (! isset($categorized[$key]) || $categorized[$key] === []) {
                continue;
            }

            $categories[] = $this->buildCategoryWithGrpGroups($categorized[$key], $year, $key);
        }

        return $categories;
    }

    private function buildCategoryWithGrpGroups(array $rows, string $year, string $key): array
    {
        $grpGroups = [];
        foreach ($rows as $row) {
            $itemName = (string) ($row['ItemName'] ?? '');
            $grp = $this->determineGrp($itemName);
            $grpKey = $grp === '-' ? 'other' : $grp;
            $grpGroups[$grpKey][] = $row;
        }

        $grpOrder = ['PINTU 8' => 0, 'PINTU 6' => 1, 'other' => 2];
        uksort($grpGroups, function ($a, $b) use ($grpOrder) {
            $orderA = $grpOrder[$a] ?? 99;
            $orderB = $grpOrder[$b] ?? 99;

            return $orderA <=> $orderB;
        });

        $groups = [];
        $categoryTotalQty = 0.0;
        $categoryMonthly = array_fill_keys(array_keys(self::MONTH_NAMES), 0.0);

        foreach ($grpGroups as $grpKey => $grpRows) {
            $items = $this->groupItems($grpRows);

            $grpTotalQty = 0.0;
            $grpMonthly = array_fill_keys(array_keys(self::MONTH_NAMES), 0.0);
            foreach ($items as &$item) {
                $grpTotalQty += $item['total_qty'];
                foreach (self::MONTH_NAMES as $mNum => $mLabel) {
                    $grpMonthly[$mNum] += $item['monthly'][$mNum];
                }
            }
            unset($item);

            $displayGrp = $grpKey === 'other' ? '-' : $grpKey;
            $groups[] = [
                'grp' => $displayGrp,
                'items' => $items,
                'total_qty' => $grpTotalQty,
                'monthly' => $grpMonthly,
            ];

            $categoryTotalQty += $grpTotalQty;
            foreach (self::MONTH_NAMES as $mNum => $mLabel) {
                $categoryMonthly[$mNum] += $grpMonthly[$mNum];
            }
        }

        return [
            'key' => $key,
            'label' => self::CATEGORY_LABELS[$key],
            'total_qty' => $categoryTotalQty,
            'monthly' => $categoryMonthly,
            'grp_groups' => $groups,
        ];
    }

    private function groupItems(array $rows): array
    {
        $itemMap = [];
        foreach ($rows as $row) {
            $itemName = (string) ($row['ItemName'] ?? '');
            $itemCode = (string) ($row['ItemCode'] ?? '');
            $key = $itemCode !== '' ? $itemCode : $itemName;

            $dateStr = (string) ($row['DateID'] ?? '');
            if ($dateStr === '') {
                continue;
            }

            try {
                $date = Carbon::parse($dateStr);
                $monthNum = (int) $date->month;
            } catch (Throwable) {
                continue;
            }

            $qty = (float) ($row['Qty'] ?? 0);

            if (! isset($itemMap[$key])) {
                $itemMap[$key] = [
                    'item_code' => $itemCode,
                    'item_name' => $this->formatItemName($itemName),
                    'total_qty' => 0.0,
                    'monthly' => array_fill_keys(array_keys(self::MONTH_NAMES), 0.0),
                ];
            }

            $itemMap[$key]['monthly'][$monthNum] += $qty;
            $itemMap[$key]['total_qty'] += $qty;
        }

        uasort($itemMap, function ($a, $b) {
            if ($b['total_qty'] <=> $a['total_qty']) {
                return $b['total_qty'] <=> $a['total_qty'];
            }

            return strcasecmp($a['item_name'], $b['item_name']);
        });

        return array_values($itemMap);
    }
}
