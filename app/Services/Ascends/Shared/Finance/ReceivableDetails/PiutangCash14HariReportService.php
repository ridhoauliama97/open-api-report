<?php

namespace App\Services\Ascends\Shared\Finance\ReceivableDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class PiutangCash14HariReportService
{
    private const TITLE = 'Laporan Umur Piutang Cash 14 Hari';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $perDate = trim((string) ($filters['PerDate'] ?? ''));
        $periodLabel = $perDate !== ''
            ? 'Per Tanggal : '.Carbon::parse($perDate)->locale('id')->isoFormat('DD-MMM-YY')
            : '';

        $filtered = $this->applySelectionFormula($allRows);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data dengan TOP 1 s/d 15.');
        }

        $perDateCarbon = $perDate !== '' ? Carbon::parse($perDate) : Carbon::today();

        $items = [];
        foreach ($filtered as $row) {
            $itemDate = $row['Item Date'] ?? '';
            $umur = $itemDate !== '' ? $perDateCarbon->diffInDays(Carbon::parse($itemDate)) : 0;

            $items[] = [
                'item_ref' => trim((string) ($row['Item Ref'] ?? '')),
                'customer_name' => trim((string) ($row['Customer Name'] ?? '')),
                'item_date' => $itemDate,
                'salesman_name' => trim((string) ($row['Sales Person Name'] ?? '')),
                'top' => (int) ($row['Invoice TOP'] ?? 0),
                'umur' => $umur,
                'balance' => (float) ($row['Balance'] ?? 0),
            ];
        }

        usort($items, function (array $a, array $b): int {
            $dateA = $a['item_date'];
            $dateB = $b['item_date'];
            if ($dateA === $dateB) {
                return strcmp($a['item_ref'], $b['item_ref']);
            }

            return strcmp($dateA, $dateB);
        });

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'items' => $items,
            'printed_by' => '',
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'ar') {
                continue;
            }

            $recordXml = $reader->readOuterXml();
            if (! is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $cleanKey = $this->cleanXmlKey((string) $key);
                $row[$cleanKey] = trim((string) $value);
            }

            if (($row['Customer Name'] ?? '') !== '') {
                $rows[] = $row;
            }
        }

        $reader->close();

        return $rows;
    }

    private function cleanXmlKey(string $key): string
    {
        $key = str_replace('_x0020_', ' ', $key);
        $key = str_replace('_x0028_', '(', $key);
        $key = str_replace('_x0029_', ')', $key);

        return str_replace('_x002F_', '/', $key);
    }

    private function applySelectionFormula(array $rows): array
    {
        return array_values(array_filter($rows, function (array $row): bool {
            $invoiceTop = (int) ($row['Invoice TOP'] ?? 0);

            return $invoiceTop >= 1 && $invoiceTop <= 15;
        }));
    }
}
