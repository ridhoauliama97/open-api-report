<?php

namespace App\Services\Ascends\Shared\ProductionByItem;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class ProduksiReportService
{
    private const TITLE = 'Laporan Produksi';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);

        $period = self::resolvePeriod($filters)
            ?? self::resolvePeriodFromRows($records['rows']);

        $allRows = $records['rows'];

        if ($period !== null) {
            $p = $period;
            $allRows = array_values(array_filter($allRows, static function (array $row) use ($p): bool {
                $date = $row['production_date'] ?? null;

                return $date !== null && $date->betweenIncluded($p['start'], $p['end']);
            }));
        }

        if ($allRows === []) {
            throw new RuntimeException('Data produksi tidak ditemukan di XML.');
        }

        $grouped = $this->groupByGroupName($allRows);
        $groups = $this->buildGroups($grouped);
        $grandTotal = $this->computeGrandTotal($groups);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $period !== null
                ? 'Periode: '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y')
                : '',
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'groups' => $groups,
            'grand_total' => $grandTotal,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        $printedBy = '';

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'invoices') {
                continue;
            }

            $recordXml = $reader->readOuterXML();

            if (! is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($node === false) {
                continue;
            }

            $productionDate = self::parseDate((string) ($node->Production_x0020_Date ?? ''));

            if ($productionDate === null) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $itemName = trim((string) ($node->Item_x0020_Name ?? ''));
            $itemFamilyName = trim((string) ($node->Item_x0020_Family_x0020_Name ?? ''));
            $uom = trim((string) ($node->UOM ?? ''));
            $quantity = (float) ($node->Quantity ?? 0);

            $groupName = $this->resolveGroupName($itemName, $itemFamilyName);

            $rows[] = [
                'production_date' => $productionDate,
                'item_name' => $itemName,
                'uom' => $uom,
                'quantity' => $quantity,
                'group_name' => $groupName,
            ];
        }

        $reader->close();

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    private function resolveGroupName(string $itemName, string $itemFamilyName): string
    {
        $upperItemName = strtoupper($itemName);

        if (str_contains($upperItemName, 'CUCI')) {
            return 'PROD CUCI';
        }

        if (str_contains($upperItemName, 'BROKER')) {
            return 'PROD BROKER';
        }

        return $itemFamilyName !== '' ? $itemFamilyName : '-';
    }

    private function groupByGroupName(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $groupKey = $row['group_name'];

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'group_name' => $groupKey,
                    'items' => [],
                ];
            }

            $itemName = $row['item_name'];

            if (! isset($groups[$groupKey]['items'][$itemName])) {
                $groups[$groupKey]['items'][$itemName] = [
                    'item_name' => $itemName,
                    'uom' => $row['uom'],
                    'quantity' => 0,
                ];
            }

            $groups[$groupKey]['items'][$itemName]['quantity'] += $row['quantity'];
        }

        ksort($groups);

        return $groups;
    }

    private function buildGroups(array $grouped): array
    {
        $groups = [];

        foreach ($grouped as $groupKey => $groupData) {
            $items = [];
            $totalQuantity = 0;

            foreach ($groupData['items'] as $item) {
                $items[] = $item;
                $totalQuantity += $item['quantity'];
            }

            usort($items, static fn (array $a, array $b): int => strcmp($a['item_name'], $b['item_name']));

            $groups[] = [
                'group_name' => $groupData['group_name'],
                'items' => $items,
                'total_quantity' => $totalQuantity,
                'item_count' => count($items),
            ];
        }

        return $groups;
    }

    private function computeGrandTotal(array $groups): float
    {
        $total = 0;

        foreach ($groups as $group) {
            $total += $group['total_quantity'];
        }

        return $total;
    }

    private static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private static function resolvePeriod(array $filters): ?array
    {
        $start = self::parseDate((string) ($filters['start_date'] ?? $filters['ProductionDate.StartDate'] ?? ''));
        $end = self::parseDate((string) ($filters['end_date'] ?? $filters['ProductionDate.EndDate'] ?? ''));

        if ($start === null && $end === null) {
            return null;
        }

        $start ??= $end?->copy();
        $end ??= $start?->copy();

        if ($start === null || $end === null) {
            return null;
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
        ];
    }

    private static function resolvePeriodFromRows(array $rows): ?array
    {
        $dates = array_values(array_filter(array_map(
            static fn (array $row): ?Carbon => $row['production_date'] ?? null,
            $rows,
        )));

        if ($dates === []) {
            return null;
        }

        usort($dates, static fn (Carbon $left, Carbon $right): int => $left <=> $right);

        return [
            'start' => $dates[0]->copy()->startOfDay(),
            'end' => $dates[count($dates) - 1]->copy()->endOfDay(),
        ];
    }

    private static function resolvePrintedBy(\SimpleXMLElement $node): string
    {
        $candidateKeys = [
            'Nama_x0020_User',
            'User_x0020_Name',
            'Printed_x0020_By',
            'Created_x0020_By',
        ];

        foreach ($candidateKeys as $key) {
            $value = trim((string) ($node->$key ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
