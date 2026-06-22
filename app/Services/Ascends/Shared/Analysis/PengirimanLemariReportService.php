<?php

namespace App\Services\Ascends\Shared\Analysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PengirimanLemariReportService
{
    private const TITLE = 'Laporan Pengiriman Lemari';

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

        $itemGroups = $this->groupRows($allRows);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $period !== null
                ? 'Periode : ' . $period['start']->locale('id')->translatedFormat('d-M-y') . ' s/d ' . $period['end']->locale('id')->translatedFormat('d-M-y')
                : '',
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'item_groups' => $itemGroups,
            'total_groups' => count($itemGroups),
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        $reader = new XMLReader;

        if (!@$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML tidak valid: {$sourceLabel}");
        }

        $rawRows = [];
        $printedBy = '';

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'gdnote') {
                continue;
            }

            $recordXml = $reader->readOuterXML();

            if (!is_string($recordXml) || trim($recordXml) === '') {
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

            $condName = trim((string) ($node->Cond_x0020_Item_x0020_Name ?? ''));
            $condCode = trim((string) ($node->Cond_x0020_Item_x0020_Code ?? ''));
            $itemCode = trim((string) ($node->Item_x0020_Code ?? ''));
            $itemName = trim((string) ($node->Item_x0020_Name ?? ''));

            if (
                !str_contains($condName, 'GRANDE PLASTIK KABINET PK')
                || $condCode === ''
                || $condCode === $itemCode
            ) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $rawRows[] = [
                'gdn_date' => $gdnDate,
                'gdn_date_sort' => $gdnDate->format('Y-m-d'),
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'cond_code' => $condCode,
                'cond_name' => $condName,
                'qty' => (float) ($node->Quantity ?? 0),
            ];
        }

        $reader->close();

        if ($rawRows === []) {
            throw new RuntimeException('Data pengiriman lemari tidak ditemukan di XML.');
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
            $parentKey = $row['item_code'];

            if (!isset($groups[$parentKey])) {
                $groups[$parentKey] = [
                    'item_code' => $row['item_code'],
                    'item_name' => $row['item_name'],
                    'children' => [],
                ];
            }

            $childKey = $row['cond_code'];

            if (!isset($groups[$parentKey]['children'][$childKey])) {
                $groups[$parentKey]['children'][$childKey] = [
                    'cond_code' => $row['cond_code'],
                    'cond_name' => $row['cond_name'],
                    'qty' => 0,
                ];
            }

            $groups[$parentKey]['children'][$childKey]['qty'] += $row['qty'];
        }

        $groups = array_values($groups);

        usort($groups, static fn(array $a, array $b): int => strcasecmp($a['item_name'], $b['item_name']));

        foreach ($groups as &$group) {
            $group['children'] = array_values($group['children']);

            usort($group['children'], static fn(array $a, array $b): int => strcasecmp($a['cond_name'], $b['cond_name']));

            $group['subtotal_qty'] = array_sum(array_map(static fn(array $child): float => $child['qty'], $group['children']));
        }
        unset($group);

        return $groups;
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
            static fn(array $row): ?Carbon => $row['gdn_date'] ?? null,
            $rows,
        )));

        if ($dates === []) {
            return null;
        }

        usort($dates, static fn(Carbon $left, Carbon $right): int => $left <=> $right);

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
