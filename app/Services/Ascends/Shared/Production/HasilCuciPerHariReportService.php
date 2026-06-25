<?php

namespace App\Services\Ascends\Shared\Production;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class HasilCuciPerHariReportService
{
    private const TITLE = 'Laporan Harian Hasil Cuci';

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

        $grouped = $this->groupByDate($allRows);
        $dates = $this->buildDateRows($grouped);
        $grandTotals = $this->computeGrandTotals($dates);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $period !== null
                ? 'Periode: '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y')
                : '',
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'sub_total' => count($grouped),
            'dates' => $dates,
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

            $rows[] = [
                'production_date' => $productionDate,
                'date_key' => $productionDate->format('Y-m-d'),
                'action_type' => $actionType,
                'item_code' => trim((string) ($node->Item_x0020_Code ?? '')),
                'item_name' => trim((string) ($node->Item_x0020_Name ?? '')),
                'quantity' => (float) ($node->Quantity ?? 0),
            ];
        }

        $reader->close();

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    private function groupByDate(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = $row['date_key'];

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'production_date' => $row['production_date'],
                    'date_key' => $key,
                    'date_display' => $row['production_date']->locale('id')->translatedFormat('d-M-y'),
                    'items_material' => [],
                    'items_output' => [],
                    'total_material' => 0,
                    'total_output' => 0,
                ];
            }

            $itemName = $row['item_name'];
            $itemCode = $row['item_code'];
            $qty = $row['quantity'];

            switch ($row['action_type']) {
                case '1-Material':
                    if (! isset($groups[$key]['items_material'][$itemName])) {
                        $groups[$key]['items_material'][$itemName] = 0;
                    }
                    $groups[$key]['items_material'][$itemName] += $qty;
                    $groups[$key]['total_material'] += $qty;
                    break;
                case '2-Output':
                    if (! isset($groups[$key]['items_output'][$itemName])) {
                        $groups[$key]['items_output'][$itemName] = ['code' => $itemCode, 'qty' => 0];
                    }
                    $groups[$key]['items_output'][$itemName]['qty'] += $qty;
                    $groups[$key]['total_output'] += $qty;
                    break;
            }
        }

        ksort($groups);

        return $groups;
    }

    private function buildDateRows(array $groups): array
    {
        $dates = [];

        foreach ($groups as $key => $group) {
            $materialItems = [];
            foreach ($group['items_material'] as $name => $qty) {
                $materialItems[] = ['name' => $name, 'qty' => $qty];
            }

            $outputItems = [];
            foreach ($group['items_output'] as $name => $data) {
                $outputItems[] = ['name' => $name, 'code' => $data['code'], 'qty' => $data['qty']];
            }

            $limbah = $group['total_material'] - $group['total_output'];

            $dates[] = [
                'production_date' => $group['production_date'],
                'date_key' => $key,
                'date_display' => $group['date_display'],
                'time_display' => $group['production_date']->format('H:i:s'),
                'material_items' => $materialItems,
                'output_items' => $outputItems,
                'total_material' => $group['total_material'],
                'total_output' => $group['total_output'],
                'limbah' => $limbah,
                'item_count' => max(count($materialItems), count($outputItems)),
            ];
        }

        return $dates;
    }

    private function computeGrandTotals(array $dates): array
    {
        $totalMaterial = 0;
        $totalOutput = 0;
        $totalLimbah = 0;

        foreach ($dates as $date) {
            $totalMaterial += $date['total_material'];
            $totalOutput += $date['total_output'];
            $totalLimbah += $date['limbah'];
        }

        return [
            'total_material' => $totalMaterial,
            'total_output' => $totalOutput,
            'total_limbah' => $totalLimbah,
        ];
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
