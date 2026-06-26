<?php

namespace App\Services\Ascends\Shared\ProductionByItem;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class ProduksiPerMingguReportService
{
    private const TITLE = 'Laporan Produksi Per Minggu';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);

        $period = self::resolvePeriod($filters);

        $allRows = $records['rows'];

        $p = $period;
        $allRows = array_values(array_filter($allRows, static function (array $row) use ($p): bool {
            $date = $row['production_date'] ?? null;

            return $date !== null && $date->betweenIncluded($p['start'], $p['end']);
        }));

        if ($allRows === []) {
            throw new RuntimeException('Data produksi tidak ditemukan di XML.');
        }

        $groups = $this->groupByWeekAndFamily($allRows);
        $grandTotal = $this->computeGrandTotal($groups);
        $grandTotalCost = $this->computeGrandTotalCost($groups);

        $periodLabel = 'Date Range : '.$period['start']->format('d/m/Y').' Until : '.$period['end']->format('d/m/Y');

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $periodLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'groups' => $groups,
            'grand_total' => $grandTotal,
            'grand_total_cost' => $grandTotalCost,
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
            $totalCostOfGood = (float) ($node->Total_x0020_Cost_x0020_of_x0020_Good ?? 0);

            $rows[] = [
                'production_date' => $productionDate,
                'item_name' => $itemName,
                'uom' => $uom,
                'quantity' => $quantity,
                'total_cost_of_good' => $totalCostOfGood,
                'item_family_name' => $itemFamilyName,
            ];
        }

        $reader->close();

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    private function groupByWeekAndFamily(array $rows): array
    {
        $grouped = [];

        $dataStart = null;
        foreach ($rows as $row) {
            $date = $row['production_date'];
            if ($dataStart === null || $date->lessThan($dataStart)) {
                $dataStart = $date->copy()->startOfDay();
            }
        }

        foreach ($rows as $row) {
            $productionDate = $row['production_date'];
            $weekNumber = 1;

            if ($dataStart !== null) {
                $daysDiff = (int) $dataStart->diffInDays($productionDate, false);
                $weekNumber = (int) floor($daysDiff / 7) + 1;
            }

            $familyName = $row['item_family_name'] !== '' ? $row['item_family_name'] : '-';
            $groupKey = 'Minggu Ke-'.$weekNumber;

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'group_label' => $groupKey,
                    'week_number' => $weekNumber,
                    'family_name' => ucfirst(strtolower($familyName)),
                    'items' => [],
                    'total_quantity' => 0,
                    'total_cost_of_good' => 0.0,
                    'uom' => '',
                ];
            }

            $itemKey = $row['item_name'];

            if (! isset($grouped[$groupKey]['items'][$itemKey])) {
                $grouped[$groupKey]['items'][$itemKey] = [
                    'date' => $productionDate,
                    'item_name' => $row['item_name'],
                    'quantity' => 0,
                    'uom' => $row['uom'],
                    'total_cost_of_good' => 0.0,
                ];

                if ($grouped[$groupKey]['uom'] === '') {
                    $grouped[$groupKey]['uom'] = $row['uom'];
                }
            }

            $grouped[$groupKey]['items'][$itemKey]['quantity'] += $row['quantity'];
            $grouped[$groupKey]['items'][$itemKey]['total_cost_of_good'] += $row['total_cost_of_good'];
            $grouped[$groupKey]['total_quantity'] += $row['quantity'];
            $grouped[$groupKey]['total_cost_of_good'] += $row['total_cost_of_good'];
        }

        uasort($grouped, static function (array $a, array $b): int {
            return $a['week_number'] <=> $b['week_number'];
        });

        $groups = [];
        foreach ($grouped as $group) {
            $items = [];
            foreach ($group['items'] as $item) {
                $items[] = $item;
            }
            usort($items, static fn (array $a, array $b) => strcasecmp($a['item_name'], $b['item_name']));

            $groups[] = [
                'group_label' => $group['group_label'],
                'week_number' => $group['week_number'],
                'family_name' => $group['family_name'],
                'items' => $items,
                'total_quantity' => $group['total_quantity'],
                'total_cost_of_good' => $group['total_cost_of_good'],
                'uom' => $group['uom'],
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

    private function computeGrandTotalCost(array $groups): float
    {
        $total = 0;

        foreach ($groups as $group) {
            $total += $group['total_cost_of_good'];
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

    private static function resolvePeriod(array $filters): array
    {
        $start = self::parseDate((string) ($filters['start_date'] ?? ''));
        $end = self::parseDate((string) ($filters['end_date'] ?? ''));

        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
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
