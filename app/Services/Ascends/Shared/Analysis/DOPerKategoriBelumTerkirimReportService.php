<?php

namespace App\Services\Ascends\Shared\Analysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class DOPerKategoriBelumTerkirimReportService
{
    private const TITLE = 'Laporan DO Per Kategori Belum Terkirim';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel, $filters);
        $allRows = $records['rows'];

        if ($allRows === []) {
            throw new RuntimeException('Data DO Per Kategori tidak ditemukan setelah filter.');
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
            $itemCode = trim((string) ($node->Item_x0020_Code ?? ''));
            $customerName = trim((string) ($node->Customer_x0020_Name ?? ''));
            $salesPerson = trim((string) ($node->SalesPerson_x0020_Name ?? ''));

            if ($invoiceDate === null) {
                continue;
            }

            if ($perDate !== null && $invoiceDate->greaterThan($perDate)) {
                continue;
            }

            $promo = $this->resolvePromo($itemName);
            if ($promo !== 'TAMPIL') {
                continue;
            }

            $familiname = $this->resolveFamiliname($itemCode);
            if ($familiname === 'NULL') {
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
                'item_name' => $itemName,
                'invoice_date' => $invoiceDate,
                'qty_purchased' => $qtyPurchased,
                'qty_outstanding' => $qtyOutstanding,
                'qty_delivered' => $qtyDelivered,
                'uom' => $uom,
                'days' => $days,
            ];
        }

        $reader->close();

        if ($rawRows === []) {
            throw new RuntimeException('Data DO Per Kategori tidak ditemukan di XML.');
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

    private function resolveFamiliname(string $itemCode): string
    {
        if (
            str_contains($itemCode, '2.1.3.1.01')
            || str_contains($itemCode, '2.1.3.1.02')
            || str_contains($itemCode, '2.1.3.1.04')
        ) {
            return 'PF1';
        }

        if (
            str_contains($itemCode, '2.1.3.1.03')
            || str_contains($itemCode, '2.1.3.2.03')
            || str_contains($itemCode, '2.1.3.2.02')
            || str_contains($itemCode, '2.1.1.3.12')
        ) {
            return 'PF2';
        }

        if (str_contains($itemCode, '2.1.5.1.')) {
            return 'ENAMEL';
        }

        if (str_contains($itemCode, '2.1.5.9.11')) {
            return 'FL';
        }

        if (str_contains($itemCode, '2.1.3.4.')) {
            return 'PKAB';
        }

        return 'NULL';
    }

    private function groupRows(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $itemKey = $row['item_name'] !== '' ? $row['item_name'] : '(tanpa nama)';

            if (! isset($groups[$itemKey])) {
                $groups[$itemKey] = [
                    'item_name' => $row['item_name'],
                    'rows' => [],
                    'total_purchased' => 0,
                    'total_outstanding' => 0,
                    'total_delivered' => 0,
                ];
            }

            $groups[$itemKey]['rows'][] = $row;
            $groups[$itemKey]['total_purchased'] += $row['qty_purchased'];
            $groups[$itemKey]['total_outstanding'] += $row['qty_outstanding'];
            $groups[$itemKey]['total_delivered'] += $row['qty_delivered'];
        }

        uksort($groups, static fn (string $a, string $b): int => strcasecmp($a, $b));

        foreach ($groups as &$group) {
            $this->sortDetailRows($group['rows']);
        }

        return array_values($groups);
    }

    private function sortDetailRows(array &$rows): void
    {
        usort($rows, static fn (array $a, array $b): int => $b['days'] <=> $a['days']);
    }

    private function computeGrandTotals(array $groups): array
    {
        $grandPurchased = 0;
        $grandOutstanding = 0;
        $grandDelivered = 0;

        foreach ($groups as $group) {
            $grandPurchased += $group['total_purchased'];
            $grandOutstanding += $group['total_outstanding'];
            $grandDelivered += $group['total_delivered'];
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
