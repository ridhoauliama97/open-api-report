<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PengirimanPerKategoriTahunanReportService
{
    private const TITLE = 'Laporan Pengiriman Per Kategori (Tahunan)';

    private const CATEGORY_ORDER = [
        'enamel',
        'plastik_furniture_1',
        'furniture_lipat',
        'plastik_furniture_2',
        'plastik_kabinet_1',
        'plastik_kabinet_2',
    ];

    private const CATEGORY_LABELS = [
        'enamel' => 'Enamel',
        'plastik_furniture_1' => 'Plastik Furniture 1',
        'furniture_lipat' => 'Furniture Lipat',
        'plastik_furniture_2' => 'Plastik Furniture 2',
        'plastik_kabinet_1' => 'Plastik Kabinet 1',
        'plastik_kabinet_2' => 'Plastik Kabinet 2',
    ];

    private const FAMILY_MAP = [
        875 => 'enamel',
        867 => 'plastik_furniture_1',
        2893 => 'furniture_lipat',
        2878 => 'plastik_furniture_2',
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

        $categories = $this->buildCategories($rows, $year);

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
            'period_label' => $year !== '' ? 'Periode :'.$year : '',
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
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA)) {
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
                $row[$key] = trim((string) $value);
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

        // Fallback to year from first row DateID
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

    private function determineGab(string $itemName): string
    {
        $upper = strtoupper($itemName);
        if (str_contains($upper, 'PROMO LEM')) {
            return 'NOT';
        }

        return 'TAMPIL';
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
            $itemName = (string) ($row['ItemName'] ?? '');
            $gab = $this->determineGab($itemName);
            if ($gab !== 'TAMPIL') {
                continue;
            }

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

            if ($key === 'plastik_kabinet_1') {
                $categories[] = $this->buildKabinet1Category($categorized[$key], $year);
            } else {
                $categories[] = $this->buildSimpleCategory($categorized[$key], $year, $key);
            }
        }

        return $categories;
    }

    private function buildSimpleCategory(array $rows, string $year, string $key): array
    {
        $items = $this->groupItems($rows);

        $totalQty = 0.0;
        $categoryMonthly = array_fill_keys(array_keys(self::MONTH_NAMES), 0.0);

        foreach ($items as &$item) {
            $totalQty += $item['total_qty'];
            foreach (self::MONTH_NAMES as $mNum => $mLabel) {
                $categoryMonthly[$mNum] += $item['monthly'][$mNum];
            }
        }
        unset($item);

        return [
            'key' => $key,
            'label' => self::CATEGORY_LABELS[$key],
            'items' => $items,
            'total_qty' => $totalQty,
            'monthly' => $categoryMonthly,
            'grp_groups' => null,
        ];
    }

    private function buildKabinet1Category(array $rows, string $year): array
    {
        $grpGroups = [];
        foreach ($rows as $row) {
            $itemName = (string) ($row['ItemName'] ?? '');
            $grp = $this->determineGrp($itemName);
            $grpGroups[$grp][] = $row;
        }

        // Order: PINTU 8, PINTU 6, -
        $grpOrder = ['PINTU 8' => 0, 'PINTU 6' => 1, '-' => 2];
        uksort($grpGroups, function ($a, $b) use ($grpOrder) {
            $orderA = $grpOrder[$a] ?? 99;
            $orderB = $grpOrder[$b] ?? 99;

            return $orderA <=> $orderB;
        });

        $groups = [];
        $categoryTotalQty = 0.0;
        $categoryMonthly = array_fill_keys(array_keys(self::MONTH_NAMES), 0.0);

        foreach ($grpGroups as $grpName => $grpRows) {
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

            $groups[] = [
                'grp' => $grpName,
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
            'key' => 'plastik_kabinet_1',
            'label' => self::CATEGORY_LABELS['plastik_kabinet_1'],
            'items' => null,
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

        // Sort items by total_qty descending (or item name ascending? Reference PDF shows items sorted by total quantity descending or frequency, let's sort by total_qty desc then item name asc)
        uasort($itemMap, function ($a, $b) {
            if ($b['total_qty'] <=> $a['total_qty']) {
                return $b['total_qty'] <=> $a['total_qty'];
            }

            return strcasecmp($a['item_name'], $b['item_name']);
        });

        return array_values($itemMap);
    }
}
