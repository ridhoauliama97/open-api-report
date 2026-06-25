<?php

namespace App\Services\Ascends\Shared\Production;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class HasilCuciPerSupplierReportService
{
    private const TITLE = 'Laporan Hasil Cuci Per Supplier';

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
            throw new RuntimeException('Data cuci tidak ditemukan di XML.');
        }

        $groupedByRemarks = $this->groupByRemarks($allRows);
        $suppliers = $this->buildSuppliers($groupedByRemarks);
        $grandTotals = $this->computeGrandTotals($suppliers);
        $suppliers = $this->applyPercentages($suppliers, $grandTotals);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $period !== null
                ? 'Periode: '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y')
                : '',
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'suppliers' => $suppliers,
            'grand_totals' => $grandTotals,
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

            $lineName = strtolower(trim((string) ($node->Line_x0020_Name ?? '')));

            if (! str_contains($lineName, 'wash')) {
                continue;
            }

            $actionType = trim((string) ($node->Action_x0020_Type ?? ''));

            if (! in_array($actionType, ['1-Material', '2-Output'], true)) {
                continue;
            }

            $productionDate = self::parseDate((string) ($node->Production_x0020_Date ?? ''));

            if ($productionDate === null) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $remarks = trim((string) ($node->Remarks ?? ''));

            if ($remarks === '') {
                $remarks = '-';
            }

            $rows[] = [
                'production_date' => $productionDate,
                'date_key' => $productionDate->format('Y-m-d'),
                'action_type' => $actionType,
                'item_code' => trim((string) ($node->Item_x0020_Code ?? '')),
                'item_name' => trim((string) ($node->Item_x0020_Name ?? '')),
                'quantity' => (float) ($node->Quantity ?? 0),
                'remarks' => $remarks,
            ];
        }

        $reader->close();

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    private function groupByRemarks(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $supplierKey = $row['remarks'];
            $dateKey = $row['date_key'];

            if (! isset($groups[$supplierKey])) {
                $groups[$supplierKey] = [
                    'remarks' => $supplierKey,
                    'dates' => [],
                ];
            }

            if (! isset($groups[$supplierKey]['dates'][$dateKey])) {
                $groups[$supplierKey]['dates'][$dateKey] = [
                    'production_date' => $row['production_date'],
                    'date_key' => $dateKey,
                    'date_display' => $row['production_date']->locale('id')->translatedFormat('d-M-y'),
                    'items_material' => [],
                    'items_output' => [],
                    'total_material' => 0,
                    'total_output' => 0,
                ];
            }

            $d = &$groups[$supplierKey]['dates'][$dateKey];
            $itemName = $row['item_name'];
            $itemCode = $row['item_code'];
            $qty = $row['quantity'];

            switch ($row['action_type']) {
                case '1-Material':
                    if (! isset($d['items_material'][$itemName])) {
                        $d['items_material'][$itemName] = 0;
                    }
                    $d['items_material'][$itemName] += $qty;
                    $d['total_material'] += $qty;
                    break;
                case '2-Output':
                    if (! isset($d['items_output'][$itemName])) {
                        $d['items_output'][$itemName] = ['code' => $itemCode, 'qty' => 0];
                    }
                    $d['items_output'][$itemName]['qty'] += $qty;
                    $d['total_output'] += $qty;
                    break;
            }
        }

        ksort($groups);

        foreach ($groups as &$supplier) {
            ksort($supplier['dates']);
        }

        return $groups;
    }

    private function buildSuppliers(array $groupedByRemarks): array
    {
        $suppliers = [];

        foreach ($groupedByRemarks as $supplierKey => $supplierGroup) {
            $supplierTotalMaterial = 0;
            $supplierTotalOutput = 0;
            $supplierTotalLimbah = 0;
            $dateRows = [];

            foreach ($supplierGroup['dates'] as $dateKey => $dateGroup) {
                $materialItems = [];
                foreach ($dateGroup['items_material'] as $name => $qty) {
                    $materialItems[] = ['name' => $name, 'qty' => $qty];
                }

                $outputItems = [];
                foreach ($dateGroup['items_output'] as $name => $data) {
                    $outputItems[] = ['name' => $name, 'code' => $data['code'], 'qty' => $data['qty']];
                }

                $limbah = $dateGroup['total_material'] - $dateGroup['total_output'];

                $dateRows[] = [
                    'production_date' => $dateGroup['production_date'],
                    'date_key' => $dateKey,
                    'date_display' => $dateGroup['date_display'],
                    'time_display' => $dateGroup['production_date']->format('H:i:s'),
                    'material_items' => $materialItems,
                    'output_items' => $outputItems,
                    'total_material' => $dateGroup['total_material'],
                    'total_output' => $dateGroup['total_output'],
                    'limbah' => $limbah,
                    'item_count' => max(count($materialItems), count($outputItems)),
                ];

                $supplierTotalMaterial += $dateGroup['total_material'];
                $supplierTotalOutput += $dateGroup['total_output'];
                $supplierTotalLimbah += $limbah;
            }

            $suppliers[] = [
                'remarks' => $supplierKey,
                'dates' => $dateRows,
                'totals' => [
                    'total_material' => $supplierTotalMaterial,
                    'total_output' => $supplierTotalOutput,
                    'total_limbah' => $supplierTotalLimbah,
                ],
                'sub_total' => count($dateRows),
            ];
        }

        return $suppliers;
    }

    private function computeGrandTotals(array $suppliers): array
    {
        $totalMaterial = 0;
        $totalOutput = 0;
        $totalLimbah = 0;

        foreach ($suppliers as $supplier) {
            $totalMaterial += $supplier['totals']['total_material'];
            $totalOutput += $supplier['totals']['total_output'];
            $totalLimbah += $supplier['totals']['total_limbah'];
        }

        return [
            'total_material' => $totalMaterial,
            'total_output' => $totalOutput,
            'total_limbah' => $totalLimbah,
        ];
    }

    private function applyPercentages(array $suppliers, array $grandTotals): array
    {
        foreach ($suppliers as &$supplier) {
            $supplier['percentages'] = [
                'input_pct' => $grandTotals['total_material'] > 0
                    ? round($supplier['totals']['total_material'] / $grandTotals['total_material'] * 100)
                    : 0,
                'output_pct' => $grandTotals['total_output'] > 0
                    ? round($supplier['totals']['total_output'] / $grandTotals['total_output'] * 100)
                    : 0,
                'limbah_pct' => $grandTotals['total_limbah'] > 0
                    ? round($supplier['totals']['total_limbah'] / $grandTotals['total_limbah'] * 100)
                    : 0,
            ];
        }

        return $suppliers;
    }

    private static function formatDate(?Carbon $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->locale('id')->translatedFormat('d-M-y');
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
