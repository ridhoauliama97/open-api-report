<?php

namespace App\Services\Ascends\Shared\Production;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class HasilBrokerPerMesinReportService
{
    private const TITLE = 'Laporan Hasil Broker Per Mesin';

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
            throw new RuntimeException('Data broker tidak ditemukan di XML.');
        }

        $groupedByLine = $this->groupByLineName($allRows);
        $machines = $this->buildMachines($groupedByLine);
        $grandTotals = $this->computeGrandTotals($machines);
        $machines = $this->applyPercentages($machines, $grandTotals);
        $grandTotals['total_downtime'] = 0;

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $period !== null
                ? 'Periode: '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y')
                : '',
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'machines' => $machines,
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

            if (! str_contains($lineName, 'broker')) {
                continue;
            }

            $actionType = trim((string) ($node->Action_x0020_Type ?? ''));

            if (! in_array($actionType, ['1-Material', '2-Output', '3-Extra Output'], true)) {
                continue;
            }

            $productionDate = self::parseDate((string) ($node->Production_x0020_Date ?? ''));

            if ($productionDate === null) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $machineName = trim((string) ($node->Line_x0020_Name ?? ''));

            if ($machineName === '') {
                continue;
            }

            $rows[] = [
                'production_date' => $productionDate,
                'date_key' => $productionDate->format('Y-m-d'),
                'action_type' => $actionType,
                'item_name' => trim((string) ($node->Item_x0020_Name ?? '')),
                'quantity' => (float) ($node->Quantity ?? 0),
                'line_name' => $machineName,
            ];
        }

        $reader->close();

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    private function groupByLineName(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $machineKey = $row['line_name'];
            $dateKey = $row['date_key'];

            if (! isset($groups[$machineKey])) {
                $groups[$machineKey] = [
                    'line_name' => $machineKey,
                    'dates' => [],
                ];
            }

            if (! isset($groups[$machineKey]['dates'][$dateKey])) {
                $groups[$machineKey]['dates'][$dateKey] = [
                    'production_date' => $row['production_date'],
                    'date_key' => $dateKey,
                    'date_display' => $row['production_date']->locale('id')->translatedFormat('d-M-y'),
                    'items_material' => [],
                    'items_output' => [],
                    'items_extra_output' => [],
                    'total_material' => 0,
                    'total_output' => 0,
                    'total_extra_output' => 0,
                ];
            }

            $d = &$groups[$machineKey]['dates'][$dateKey];
            $itemName = $row['item_name'];
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
                        $d['items_output'][$itemName] = 0;
                    }
                    $d['items_output'][$itemName] += $qty;
                    $d['total_output'] += $qty;
                    break;
                case '3-Extra Output':
                    if (! isset($d['items_extra_output'][$itemName])) {
                        $d['items_extra_output'][$itemName] = 0;
                    }
                    $d['items_extra_output'][$itemName] += $qty;
                    $d['total_extra_output'] += $qty;
                    break;
            }
        }

        ksort($groups);

        foreach ($groups as &$machine) {
            ksort($machine['dates']);
        }

        return $groups;
    }

    private function buildMachines(array $groupedByLine): array
    {
        $machines = [];

        foreach ($groupedByLine as $machineKey => $machineGroup) {
            $machineTotalMaterial = 0;
            $machineTotalOutput = 0;
            $machineTotalExtraOutput = 0;
            $machineTotalReproses = 0;
            $dateRows = [];

            foreach ($machineGroup['dates'] as $dateKey => $dateGroup) {
                $materialItems = [];
                foreach ($dateGroup['items_material'] as $name => $qty) {
                    $materialItems[] = ['name' => $name, 'qty' => $qty];
                }

                $outputItems = [];
                foreach ($dateGroup['items_output'] as $name => $qty) {
                    $outputItems[] = ['name' => $name, 'qty' => $qty];
                }

                $extraOutputItems = [];
                foreach ($dateGroup['items_extra_output'] as $name => $qty) {
                    $extraOutputItems[] = ['name' => $name, 'qty' => $qty];
                }

                $sumBinggolan = $dateGroup['total_output'] + $dateGroup['total_extra_output'];
                $limbah = $dateGroup['total_material'] - $sumBinggolan;

                $dateRows[] = [
                    'production_date' => $dateGroup['production_date'],
                    'date_key' => $dateKey,
                    'date_display' => $dateGroup['date_display'],
                    'time_display' => $dateGroup['production_date']->format('H:i:s'),
                    'material_items' => $materialItems,
                    'output_items' => $outputItems,
                    'extra_output_items' => $extraOutputItems,
                    'total_material' => $dateGroup['total_material'],
                    'total_output' => $dateGroup['total_output'],
                    'total_extra_output' => $dateGroup['total_extra_output'],
                    'sum_binggolan' => $sumBinggolan,
                    'reproses' => $limbah,
                    'item_count' => max(count($materialItems), count($outputItems)),
                ];

                $machineTotalMaterial += $dateGroup['total_material'];
                $machineTotalOutput += $dateGroup['total_output'];
                $machineTotalExtraOutput += $dateGroup['total_extra_output'];
                $machineTotalReproses += $limbah;
            }

            $machines[] = [
                'line_name' => $machineKey,
                'dates' => $dateRows,
                'totals' => [
                    'total_material' => $machineTotalMaterial,
                    'total_output' => $machineTotalOutput,
                    'total_extra_output' => $machineTotalExtraOutput,
                    'total_reproses' => $machineTotalReproses,
                    'total_bonggolan' => $machineTotalExtraOutput,
                ],
                'sub_total' => count($dateRows),
            ];
        }

        return $machines;
    }

    private function computeGrandTotals(array $machines): array
    {
        $totalMaterial = 0;
        $totalOutput = 0;
        $totalExtraOutput = 0;
        $totalReproses = 0;

        foreach ($machines as $machine) {
            $totalMaterial += $machine['totals']['total_material'];
            $totalOutput += $machine['totals']['total_output'];
            $totalExtraOutput += $machine['totals']['total_extra_output'];
            $totalReproses += $machine['totals']['total_reproses'];
        }

        return [
            'total_material' => $totalMaterial,
            'total_output' => $totalOutput,
            'total_extra_output' => $totalExtraOutput,
            'total_reproses' => $totalReproses,
            'total_bonggolan' => $totalExtraOutput,
            'total_downtime' => 0,
        ];
    }

    private function applyPercentages(array $machines, array $grandTotals): array
    {
        foreach ($machines as &$machine) {
            $machine['percentages'] = [
                'input_pct' => $grandTotals['total_material'] > 0
                    ? round($machine['totals']['total_material'] / $grandTotals['total_material'] * 100)
                    : 0,
                'output_pct' => $grandTotals['total_output'] > 0
                    ? round($machine['totals']['total_output'] / $grandTotals['total_output'] * 100)
                    : 0,
                'reproses_pct' => $grandTotals['total_reproses'] > 0
                    ? round($machine['totals']['total_reproses'] / $grandTotals['total_reproses'] * 100)
                    : 0,
                'bonggolan_pct' => $grandTotals['total_extra_output'] > 0
                    ? round($machine['totals']['total_extra_output'] / $grandTotals['total_extra_output'] * 100)
                    : 0,
                'downtime_pct' => 0,
            ];
        }

        return $machines;
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
