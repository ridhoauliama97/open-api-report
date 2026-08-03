<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PengirimanLemariHarianReportService
{
    private const TITLE = 'Laporan Pengiriman Lemari (Harian)';

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

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');

        $filteredRows = $this->filterByDateRange($rows, $startDate, $endDate);
        $filteredRows = $this->filterByKeterangan($filteredRows);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data dalam rentang tanggal yang dipilih.');
        }

        $dayNumbers = $this->collectDayNumbers($filteredRows);
        $categories = $this->buildCategories($filteredRows, $dayNumbers);

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        $startDateFormatted = $startDate ? $startDate->locale('id')->isoFormat('DD-MMM-YY') : '';
        $endDateFormatted = $endDate ? $endDate->locale('id')->isoFormat('DD-MMM-YY') : '';

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'start_date' => $startDateFormatted,
            'end_date' => $endDateFormatted,
            'period_label' => ($startDateFormatted && $endDateFormatted)
                ? 'Dari ' . $startDateFormatted . ' s/d ' . $endDateFormatted
                : '',
            'day_numbers' => $dayNumbers,
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
                $cleanKey = trim(str_replace('_x0020_', ' ', $key));
                $row[$cleanKey] = trim((string) $value);
            }

            $rows[] = $row;
        }

        $reader->close();

        return $rows;
    }

    private function resolveDateFilter(array $filters, string $key): ?Carbon
    {
        $value = trim((string) (
            $filters[$key]
            ?? $filters[strtolower($key)]
            ?? $filters['DateRange.' . $key]
            ?? $filters['DateRange_' . $key]
            ?? ''
        ));

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function filterByDateRange(array $rows, ?Carbon $startDate, ?Carbon $endDate): array
    {
        if ($startDate === null && $endDate === null) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn(array $row): bool => $this->isWithinDateRange($row, $startDate, $endDate)
        ));
    }

    private function isWithinDateRange(array $row, ?Carbon $startDate, ?Carbon $endDate): bool
    {
        $rowDate = $this->parseDate((string) ($row['DateID'] ?? ''));
        if ($rowDate === null) {
            return false;
        }

        if ($startDate !== null && $rowDate->lessThan($startDate->startOfDay())) {
            return false;
        }

        if ($endDate !== null && $rowDate->greaterThan($endDate->endOfDay())) {
            return false;
        }

        return true;
    }

    private function parseDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function collectDayNumbers(array $rows): array
    {
        $days = [];
        foreach ($rows as $row) {
            $date = $this->parseDate((string) ($row['DateID'] ?? ''));
            if ($date === null) {
                continue;
            }
            $dayNum = (int) $date->format('j');
            $days[$dayNum] = true;
        }

        $dayNumbers = array_keys($days);
        sort($dayNumbers);

        return $dayNumbers;
    }

    private function determineKeterangan(string $itemName): string
    {
        $upper = strtoupper($itemName);
        if (str_contains($upper, 'PROMO LEM')) {
            return 'NOT';
        }

        return 'TAMPIL';
    }

    private function filterByKeterangan(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            fn(array $row): bool => $this->determineKeterangan((string) ($row['ItemName'] ?? '')) === 'TAMPIL'
        ));
    }

    private function determineGrp(string $itemName): string
    {
        $upper = strtoupper($itemName);
        if (str_contains($upper, 'PLASTIK KABINET PK')) {
            if (str_contains($upper, '3TX6P')) {
                return '3TX6P';
            }
            if (str_contains($upper, '4TX8P')) {
                return '4TX8P';
            }
            if (str_contains($upper, '4TX4P')) {
                return '4TX4P';
            }
        }

        return '-';
    }

    private function formatItemName(string $itemName): string
    {
        return ' ' . $itemName;
    }

    private function buildCategories(array $rows, array $dayNumbers): array
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

            $categories[] = $this->buildCategoryWithGrpGroups($categorized[$key], $dayNumbers, $key);
        }

        return $categories;
    }

    private function buildCategoryWithGrpGroups(array $rows, array $dayNumbers, string $key): array
    {
        $grpGroups = [];
        foreach ($rows as $row) {
            $itemName = (string) ($row['ItemName'] ?? '');
            $grp = $this->determineGrp($itemName);
            $grpKey = $grp === '-' ? 'other' : $grp;
            $grpGroups[$grpKey][] = $row;
        }

        $grpOrder = ['3TX6P' => 0, '4TX8P' => 1, '4TX4P' => 2];
        uksort($grpGroups, function ($a, $b) use ($grpOrder) {
            $orderA = $grpOrder[$a] ?? 99;
            $orderB = $grpOrder[$b] ?? 99;

            return $orderA <=> $orderB;
        });

        $groups = [];
        $categoryTotalQty = 0;
        $categoryDaily = array_fill_keys($dayNumbers, 0.0);

        foreach ($grpGroups as $grpKey => $grpRows) {
            $items = $this->groupItems($grpRows, $dayNumbers);

            $grpTotalQty = 0;
            $grpDaily = array_fill_keys($dayNumbers, 0.0);
            foreach ($items as &$item) {
                $grpTotalQty += $item['total_qty'];
                foreach ($dayNumbers as $day) {
                    $grpDaily[$day] += $item['daily'][$day];
                }
            }
            unset($item);

            $displayGrp = $grpKey === 'other' ? '-' : $grpKey;
            $groups[] = [
                'grp' => $displayGrp,
                'items' => $items,
                'total_qty' => $grpTotalQty,
                'daily' => $grpDaily,
            ];

            $categoryTotalQty += $grpTotalQty;
            foreach ($dayNumbers as $day) {
                $categoryDaily[$day] += $grpDaily[$day];
            }
        }

        return [
            'key' => $key,
            'label' => self::CATEGORY_LABELS[$key],
            'total_qty' => $categoryTotalQty,
            'daily' => $categoryDaily,
            'grp_groups' => $groups,
        ];
    }

    private function groupItems(array $rows, array $dayNumbers): array
    {
        $itemMap = [];
        foreach ($rows as $row) {
            $itemName = (string) ($row['ItemName'] ?? '');
            $itemCode = (string) ($row['ItemCode'] ?? '');
            $key = $itemCode !== '' ? $itemCode : $itemName;

            $date = $this->parseDate((string) ($row['DateID'] ?? ''));
            if ($date === null) {
                continue;
            }
            $dayNum = (int) $date->format('j');
            $qty = (float) ($row['Qty'] ?? 0);

            if (! isset($itemMap[$key])) {
                $itemMap[$key] = [
                    'item_code' => $itemCode,
                    'item_name' => $this->formatItemName($itemName),
                    'total_qty' => 0.0,
                    'daily' => array_fill_keys($dayNumbers, 0.0),
                ];
            }

            $itemMap[$key]['daily'][$dayNum] += $qty;
            $itemMap[$key]['total_qty'] += $qty;
        }

        usort($itemMap, fn($a, $b) => strcasecmp($a['item_name'], $b['item_name']));

        return $itemMap;
    }
}
