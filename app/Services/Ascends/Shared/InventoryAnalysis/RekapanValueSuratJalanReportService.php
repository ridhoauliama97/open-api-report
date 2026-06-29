<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class RekapanValueSuratJalanReportService
{
    private const TITLE = 'Goods Delivery Note - Laporan Rekapan Value Surat Jalan';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);
        $allRows = $records['rows'];

        $period = self::resolvePeriod($filters)
            ?? self::resolvePeriodFromRows($allRows);

        if ($period !== null) {
            $p = $period;
            $allRows = array_values(array_filter($allRows, static function (array $row) use ($p): bool {
                $date = $row['gdn_date'] ?? null;

                return $date !== null && $date->betweenIncluded($p['start'], $p['end']);
            }));
        }

        $customerGroups = $this->groupRows($allRows);
        $grandQty = 0;
        $grandTotal = 0;
        $grandUomBreakdown = [];

        foreach ($customerGroups as &$group) {
            $group['subtotal_qty'] = array_sum(array_map(static fn (array $item): float => (float) ($item['qty'] ?? 0), $group['items']));
            $group['subtotal_total'] = array_sum(array_map(static fn (array $item): float => (float) ($item['total'] ?? 0), $group['items']));

            $uomBreakdown = [];
            foreach ($group['items'] as $item) {
                $uom = ! empty($item['uom']) ? strtoupper($item['uom']) : 'UNIT';
                $uomBreakdown[$uom] = ($uomBreakdown[$uom] ?? 0) + (float) ($item['qty'] ?? 0);
            }
            $group['subtotal_uoms'] = $uomBreakdown;

            foreach ($uomBreakdown as $uom => $qty) {
                $grandUomBreakdown[$uom] = ($grandUomBreakdown[$uom] ?? 0) + $qty;
            }

            $grandQty += $group['subtotal_qty'];
            $grandTotal += $group['subtotal_total'];
        }
        unset($group);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $period !== null
                ? 'Periode: '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y')
                : '',
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'customer_groups' => $customerGroups,
            'total_customers' => count($customerGroups),
            'grand_total_qty' => $grandQty,
            'grand_total_total' => $grandTotal,
            'grand_total_uoms' => $grandUomBreakdown,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML tidak valid: {$sourceLabel}");
        }

        $rawRows = [];
        $printedBy = '';

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'gdnote') {
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

            $gdnDate = self::parseDate((string) ($node->GDN_x0020_Date ?? ''));

            if ($gdnDate === null) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $gdnNumber = trim((string) ($node->GDN_x0020_Number ?? ''));
            $customerName = trim((string) ($node->Customer_x0020_Name ?? ''));
            $quantity = (float) ($node->Quantity ?? 0);
            $lineTotal = (float) ($node->Line_x0020_Total ?? 0);
            $uom = trim((string) ($node->UOM ?? ''));

            if ($gdnNumber === '' || $customerName === '') {
                continue;
            }

            $rawRows[] = [
                'gdn_date' => $gdnDate,
                'gdn_date_sort' => $gdnDate->format('Y-m-d'),
                'gdn_date_display' => self::formatDate($gdnDate),
                'gdn_number' => $gdnNumber,
                'customer_name' => $customerName,
                'qty' => $quantity,
                'total' => $lineTotal,
                'uom' => $uom,
            ];
        }

        $reader->close();

        if ($rawRows === []) {
            throw new RuntimeException('Data Goods Delivery Note tidak ditemukan di XML.');
        }

        return [
            'rows' => $rawRows,
            'printed_by' => $printedBy,
        ];
    }

    private function groupRows(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = $row['customer_name'];

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'customer_name' => $key,
                    'items' => [],
                ];
            }

            $groups[$key]['items'][] = [
                'gdn_number' => $row['gdn_number'],
                'gdn_date_display' => $row['gdn_date_display'],
                'gdn_date_sort' => $row['gdn_date_sort'],
                'qty' => $row['qty'],
                'total' => $row['total'],
                'uom' => $row['uom'] ?? '',
            ];
        }

        ksort($groups);

        $sorted = [];

        foreach ($groups as $group) {
            usort($group['items'], static fn (array $a, array $b): int => ($a['gdn_date_sort'] ?? '') <=> ($b['gdn_date_sort'] ?? '')
                ?: ($a['gdn_number'] ?? '') <=> ($b['gdn_number'] ?? ''));

            $group['items'] = array_values($group['items']);
            $sorted[] = $group;
        }

        return $sorted;
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
        $start = self::parseDate((string) ($filters['start_date'] ?? $filters['AdjustmentDate.StartDate'] ?? ''));
        $end = self::parseDate((string) ($filters['end_date'] ?? $filters['AdjustmentDate.EndDate'] ?? ''));

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
            static fn (array $row): ?Carbon => $row['gdn_date'] ?? null,
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
