<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class HistoryHargaPoReportService
{
    private const TITLE = 'Laporan History Harga Purchase Order';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);
        $rows = $records['rows'];

        if ($rows === []) {
            throw new RuntimeException('Data purchase order tidak ditemukan di XML.');
        }

        $period = self::resolvePeriod($filters) ?? self::resolvePeriodFromRows($rows);

        if ($period !== null) {
            $p = $period;
            $rows = array_values(array_filter($rows, static function (array $row) use ($p): bool {
                $date = $row['order_date'] ?? null;

                return $date !== null && $date->betweenIncluded($p['start'], $p['end']);
            }));
        }

        if ($rows === []) {
            throw new RuntimeException('Data purchase order tidak ditemukan di periode tersebut.');
        }

        $rows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => ($row['sudah_ada'] ?? 'Tidak Ada') === 'Ada'
        ));

        if ($rows === []) {
            throw new RuntimeException('Data history harga purchase order tidak ditemukan sesuai selection formula.');
        }

        $rows = self::deduplicateRows($rows);

        $dateRangeText = '';
        if ($period !== null) {
            $dateRangeText = 'Dari '.$period['start']->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$period['end']->locale('id')->isoFormat('DD-MMM-YY');
        }

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $dateRangeText,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'groups' => self::buildGroups($rows),
            'total_rows' => count($rows),
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
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'table') {
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

            $row = $this->extractRecord($node);

            if ($row === null) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $rows[] = $row;
        }

        $reader->close();

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    private function extractRecord(\SimpleXMLElement $node): ?array
    {
        $orderDate = self::parseDate((string) ($node->Order_x0020_Date ?? ''));
        if ($orderDate === null) {
            return null;
        }

        $itemCategory = trim((string) ($node->Item_x0020_Category ?? ''));
        $itemName = trim((string) ($node->Item_x0020_Name ?? ''));
        $unitCost = (float) ($node->Item_x0020_Unit_x0020_Cost ?? 0);
        $lastPrice1 = (float) ($node->{'Last_x0020_Price-1'} ?? 0);
        $lastPrice2 = (float) ($node->{'Last_x0020_Price-2'} ?? 0);
        $lastPrice3 = (float) ($node->{'Last_x0020_Price-3'} ?? 0);

        $sudahAda = self::hasHigherCurrentOrRecentPrice($unitCost, $lastPrice1, $lastPrice2, $lastPrice3)
            ? 'Ada'
            : 'Tidak Ada';

        $newStatus = $lastPrice1 == 0.0 && $lastPrice2 == 0.0 && $lastPrice3 == 0.0
            ? 'New Item'
            : 'Existing Item';

        $category = str_contains($itemCategory, 'PERS. LAINNYA')
            ? 'Persediaan lainnya'
            : 'Non Persediaan Lain';

        $grp33 = $orderDate->toDateString().$itemName.(string) $unitCost.(string) $lastPrice1.(string) $lastPrice2.(string) $lastPrice3;

        return [
            'order_date' => $orderDate,
            'order_date_sort' => $orderDate->format('Y-m-d'),
            'order_date_display' => self::formatDate($orderDate),
            'order_number' => trim((string) ($node->Order_x0020_Number ?? '')),
            'supplier_name' => trim((string) ($node->Supplier_x0020_Name ?? '')),
            'item_name' => $itemName,
            'item_category' => $itemCategory,
            'category' => $category,
            'new_status' => $newStatus,
            'qty_ordered' => (float) ($node->{'Qty._x0020_Ordered'} ?? 0),
            'unit_cost' => $unitCost,
            'last_price_1' => $lastPrice1,
            'last_price_1_date' => self::parseDate((string) ($node->{'Last_x0020_Price-1_x0020_Date'} ?? '')),
            'last_price_2' => $lastPrice2,
            'last_price_2_date' => self::parseDate((string) ($node->{'Last_x0020_Price-2_x0020_Date'} ?? '')),
            'last_price_3' => $lastPrice3,
            'last_price_3_date' => self::parseDate((string) ($node->{'Last_x0020_Price-3_x0020_Date'} ?? '')),
            'sudah_ada' => $sudahAda,
            'grp33' => $grp33,
        ];
    }

    private static function hasHigherCurrentOrRecentPrice(float $unitCost, float $lastPrice1, float $lastPrice2, float $lastPrice3): bool
    {
        return $lastPrice3 < $lastPrice2
            || $lastPrice3 < $lastPrice1
            || $lastPrice3 < $unitCost
            || $lastPrice2 < $lastPrice1
            || $lastPrice2 < $unitCost
            || $lastPrice1 < $unitCost;
    }

    private static function deduplicateRows(array $rows): array
    {
        $seen = [];
        $result = [];

        foreach ($rows as $row) {
            $key = (string) ($row['grp33'] ?? '');
            if ($key !== '' && isset($seen[$key])) {
                continue;
            }

            if ($key !== '') {
                $seen[$key] = true;
            }

            $result[] = $row;
        }

        return $result;
    }

    private static function buildGroups(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            return [$a['category'], $a['new_status'], $a['order_date_sort'], $a['item_name']]
                <=> [$b['category'], $b['new_status'], $b['order_date_sort'], $b['item_name']];
        });

        $groups = [];

        foreach ($rows as $row) {
            $category = (string) ($row['category'] ?? 'Non Persediaan Lain');
            $newStatus = (string) ($row['new_status'] ?? 'Existing Item');

            if (! isset($groups[$category])) {
                $groups[$category] = [
                    'category' => $category,
                    'statuses' => [],
                    'total_rows' => 0,
                ];
            }

            if (! isset($groups[$category]['statuses'][$newStatus])) {
                $groups[$category]['statuses'][$newStatus] = [
                    'status' => $newStatus,
                    'rows' => [],
                ];
            }

            $groups[$category]['statuses'][$newStatus]['rows'][] = [
                'no' => count($groups[$category]['statuses'][$newStatus]['rows']) + 1,
                'order_date' => (string) ($row['order_date_display'] ?? ''),
                'order_number' => (string) ($row['order_number'] ?? ''),
                'item_name' => (string) ($row['item_name'] ?? ''),
                'supplier_name' => (string) ($row['supplier_name'] ?? ''),
                'qty_ordered' => self::formatQty((float) ($row['qty_ordered'] ?? 0)),
                'unit_cost' => self::formatAmount((float) ($row['unit_cost'] ?? 0)),
                'last_price_1' => self::formatAmount((float) ($row['last_price_1'] ?? 0)),
                'last_price_1_date' => self::formatDate($row['last_price_1_date'] ?? null),
                'last_price_2' => self::formatAmount((float) ($row['last_price_2'] ?? 0)),
                'last_price_2_date' => self::formatDate($row['last_price_2_date'] ?? null),
                'last_price_3' => self::formatAmount((float) ($row['last_price_3'] ?? 0)),
                'last_price_3_date' => self::formatDate($row['last_price_3_date'] ?? null),
            ];
            $groups[$category]['total_rows']++;
        }

        foreach ($groups as &$group) {
            $group['statuses'] = array_values($group['statuses']);
        }
        unset($group);

        return array_values($groups);
    }

    private static function formatQty(float $value): string
    {
        if (abs($value) < 0.001) {
            return '-';
        }

        return number_format($value, 0, '.', ',');
    }

    private static function formatAmount(float $value): string
    {
        if (abs($value) < 0.001) {
            return '-';
        }

        return number_format($value, 2, ',', '.');
    }

    private static function formatDate(?Carbon $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->locale('id')->isoFormat('DD-MMM-YY');
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
        $start = self::parseDate((string) ($filters['start_date'] ?? $filters['PurchaseOrderDate.StartDate'] ?? ''));
        $end = self::parseDate((string) ($filters['end_date'] ?? $filters['PurchaseOrderDate.EndDate'] ?? ''));

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
            static fn (array $row): ?Carbon => $row['order_date'] ?? null,
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
