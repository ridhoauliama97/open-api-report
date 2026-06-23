<?php

namespace App\Services\Ascends\Shared\Analysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class ListDOBelumTerkirimReportService
{
    private const TITLE = 'Laporan List DO Belum Terkirim';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel, $filters);
        $allRows = $records['rows'];

        if ($allRows === []) {
            throw new RuntimeException('Data DO tidak ditemukan setelah filter.');
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

            $invoiceNumber = trim((string) ($node->Invoice_x0020_Number ?? ''));
            $invoiceType = trim((string) ($node->Invoice_x0020_Type ?? ''));
            $invoiceDate = self::parseDate((string) ($node->Invoice_x0020_Date ?? ''));

            if ($invoiceDate === null) {
                continue;
            }

            if ($perDate !== null && $invoiceDate->greaterThan($perDate)) {
                continue;
            }

            $doName = $this->resolveDoName($invoiceNumber, $invoiceType);

            if (! str_starts_with($doName, 'DO SS') && ! str_starts_with($doName, 'DO')) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $qtyPurchased = (float) ($node->{'Qty._x0020_Purchased_x0020__x0028_Smallest_x0029_'} ?? 0);
            $qtyDelivered = (float) ($node->{'Qty._x0020_Delivered_x0020__x0028_Smallest_x0029_'} ?? 0);
            $qtyOutstanding = (float) ($node->{'Qty._x0020_Outstanding_x0020__x0028_Smallest_x0029_'} ?? 0);
            $uom = trim((string) ($node->Smallest_x0020_UOM ?? 'BH'));

            $rawRows[] = [
                'invoice_date' => $invoiceDate,
                'item_code' => trim((string) ($node->Item_x0020_Code ?? '')),
                'item_name' => trim((string) ($node->Item_x0020_Name ?? '')),
                'type' => $doName,
                'qty_purchased' => $qtyPurchased,
                'qty_outstanding' => $qtyOutstanding,
                'qty_delivered' => $qtyDelivered,
                'uom' => $uom,
            ];
        }

        $reader->close();

        if ($rawRows === []) {
            throw new RuntimeException('Data DO tidak ditemukan di XML.');
        }

        return [
            'rows' => $rawRows,
            'printed_by' => $printedBy,
        ];
    }

    private function resolveDoName(string $invoiceNumber, string $invoiceType): string
    {
        $overrides = ['SI/05/18/0101', 'SI/10/18/0116'];

        if (in_array($invoiceNumber, $overrides, true)) {
            return 'DO';
        }

        return $invoiceType;
    }

    private function groupRows(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = $row['item_code'];

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'item_code' => $row['item_code'],
                    'item_name' => $row['item_name'],
                    'type' => $row['type'],
                    'qty_purchased' => 0,
                    'qty_outstanding' => 0,
                    'qty_delivered' => 0,
                    'uom' => $row['uom'],
                ];
            }

            $groups[$key]['qty_purchased'] += $row['qty_purchased'];
            $groups[$key]['qty_outstanding'] += $row['qty_outstanding'];
            $groups[$key]['qty_delivered'] += $row['qty_delivered'];
        }

        $sorted = array_values($groups);

        usort($sorted, static fn (array $a, array $b): int => strcasecmp($a['item_code'], $b['item_code']));

        return $sorted;
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
