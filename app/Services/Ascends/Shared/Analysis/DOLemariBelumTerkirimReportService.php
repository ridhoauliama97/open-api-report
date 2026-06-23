<?php

namespace App\Services\Ascends\Shared\Analysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class DOLemariBelumTerkirimReportService
{
    private const TITLE = 'Laporan DO Lemari Belum Terkirim';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel, $filters);
        $allRows = $records['rows'];

        if ($allRows === []) {
            throw new RuntimeException('Data DO Lemari tidak ditemukan setelah filter.');
        }

        $groupedRows = $this->groupRows($allRows);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $this->formatPeriodLabel($filters),
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'rows' => $groupedRows,
            'total_rows' => count($groupedRows),
            'grand_totals' => $this->computeGrandTotals($groupedRows),
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel, array $filters): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML tidak valid: {$sourceLabel}");
        }

        $rawRows = [];
        $printedBy = '';
        $perDate = self::parseDate($filters['per_date'] ?? '');

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

            $invoiceDate = self::parseDate((string) ($node->Invoice_x0020_Date ?? ''));
            $itemName = trim((string) ($node->Item_x0020_Name ?? ''));
            $customerName = trim((string) ($node->Customer_x0020_Name ?? ''));
            $salesPerson = trim((string) ($node->SalesPerson_x0020_Name ?? ''));
            $family = trim((string) ($node->Item_x0020_Family ?? ''));

            if ($invoiceDate === null) {
                continue;
            }

            if ($perDate !== null && $invoiceDate->greaterThan($perDate)) {
                continue;
            }

            if (! str_starts_with($family, 'PLASTIK KABINET 1') && ! str_starts_with($family, 'PLASTIK KABINET 2')) {
                continue;
            }

            $promo = $this->resolvePromo($itemName);
            if ($promo !== 'TAMPIL') {
                continue;
            }

            if (str_contains($itemName, 'HANGER')) {
                continue;
            }

            $invoiceType = trim((string) ($node->Invoice_x0020_Type ?? ''));
            if (! str_starts_with($invoiceType, 'DO')) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $qtyPurchased = round((float) ($node->{'Qty._x0020_Purchased'} ?? 0));
            $qtyDelivered = round((float) ($node->{'Qty._x0020_Delivered'} ?? 0));
            $qtyOutstanding = round((float) ($node->{'Qty._x0020_Outstanding'} ?? 0));
            $uom = trim((string) ($node->UOM ?? ''));
            $days = (int) ($node->Days ?? 0);

            $rawRows[] = [
                'sales_person' => $salesPerson,
                'customer' => $customerName,
                'invoice_date' => $invoiceDate,
                'item_name' => $itemName,
                'qty_purchased' => $qtyPurchased,
                'qty_outstanding' => $qtyOutstanding,
                'qty_delivered' => $qtyDelivered,
                'uom' => $uom,
                'days' => $days,
            ];
        }

        $reader->close();

        if ($rawRows === []) {
            throw new RuntimeException('Data DO Lemari tidak ditemukan di XML.');
        }

        return [
            'rows' => $rawRows,
            'printed_by' => $printedBy,
        ];
    }

    private function resolvePromo(string $itemName): string
    {
        if (str_contains($itemName, 'PROMO KUR') || str_contains($itemName, 'PROMO LEM')) {
            return 'NOT';
        }

        return 'TAMPIL';
    }

    private function groupRows(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $salesKey = $row['sales_person'] !== '' ? $row['sales_person'] : '(tanpa sales)';
            $customerKey = $row['customer'] !== '' ? $row['customer'] : '(tanpa customer)';
            $detailKey = $row['item_name'];

            if (! isset($groups[$salesKey])) {
                $groups[$salesKey] = [
                    'sales_person' => $row['sales_person'],
                    'customers' => [],
                    'sales_total_purchased' => 0,
                    'sales_total_outstanding' => 0,
                    'sales_total_delivered' => 0,
                ];
            }

            if (! isset($groups[$salesKey]['customers'][$customerKey])) {
                $groups[$salesKey]['customers'][$customerKey] = [
                    'customer' => $row['customer'],
                    'rows' => [],
                    'customer_total_purchased' => 0,
                    'customer_total_outstanding' => 0,
                    'customer_total_delivered' => 0,
                ];
            }

            if (! isset($groups[$salesKey]['customers'][$customerKey]['rows'][$detailKey])) {
                $groups[$salesKey]['customers'][$customerKey]['rows'][$detailKey] = [
                    'item_name' => $row['item_name'],
                    'invoice_date' => $row['invoice_date'],
                    'qty_purchased' => 0,
                    'qty_outstanding' => 0,
                    'qty_delivered' => 0,
                    'uom' => $row['uom'],
                    'days' => $row['days'],
                ];
            }

            $groups[$salesKey]['customers'][$customerKey]['rows'][$detailKey]['qty_purchased'] += $row['qty_purchased'];
            $groups[$salesKey]['customers'][$customerKey]['rows'][$detailKey]['qty_outstanding'] += $row['qty_outstanding'];
            $groups[$salesKey]['customers'][$customerKey]['rows'][$detailKey]['qty_delivered'] += $row['qty_delivered'];

            $groups[$salesKey]['customers'][$customerKey]['customer_total_purchased'] += $row['qty_purchased'];
            $groups[$salesKey]['customers'][$customerKey]['customer_total_outstanding'] += $row['qty_outstanding'];
            $groups[$salesKey]['customers'][$customerKey]['customer_total_delivered'] += $row['qty_delivered'];

            $groups[$salesKey]['sales_total_purchased'] += $row['qty_purchased'];
            $groups[$salesKey]['sales_total_outstanding'] += $row['qty_outstanding'];
            $groups[$salesKey]['sales_total_delivered'] += $row['qty_delivered'];
        }

        ksort($groups);

        foreach ($groups as $salesKey => &$salesGroup) {
            ksort($salesGroup['customers']);

            foreach ($salesGroup['customers'] as $customerKey => &$customerGroup) {
                uasort($customerGroup['rows'], static fn (array $a, array $b): int => strcasecmp($a['item_name'], $b['item_name']));
                $customerGroup['rows'] = array_values($customerGroup['rows']);
            }

            $salesGroup['customers'] = array_values($salesGroup['customers']);
        }

        return array_values($groups);
    }

    private function computeGrandTotals(array $groups): array
    {
        $grandPurchased = 0;
        $grandOutstanding = 0;
        $grandDelivered = 0;

        foreach ($groups as $group) {
            $grandPurchased += $group['sales_total_purchased'];
            $grandOutstanding += $group['sales_total_outstanding'];
            $grandDelivered += $group['sales_total_delivered'];
        }

        return [
            'qty_purchased' => $grandPurchased,
            'qty_outstanding' => $grandOutstanding,
            'qty_delivered' => $grandDelivered,
        ];
    }

    private function formatPeriodLabel(array $filters): string
    {
        $perDate = $filters['per_date'] ?? '';

        if ($perDate === '') {
            return '';
        }

        $date = self::parseDate($perDate);

        if ($date === null) {
            return '';
        }

        return 'Per Tanggal : '.$date->locale('id')->translatedFormat('d-M-y');
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
