<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class SaldoStokBarangPerGudangGsuReportService
{
    private const TITLE = 'Laporan Pendukung Stock Opname';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);
        $allRows = $records['rows'];

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan di XML.');
        }

        $grouped = $this->groupByWarehouseAndCategory($allRows);

        $perDate = $this->formatPerDate($filters['per_date'] ?? '');

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'per_date' => $perDate,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'warehouses' => $grouped['groups'],
            'warehouse_totals' => $grouped['totals'],
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

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $itemName = trim((string) ($node->Item_x0020_Name ?? ''));
            if ($itemName === '') {
                continue;
            }

            $onHand = (float) ($node->On_x0020_Hand ?? 0);
            if ($onHand <= 0) {
                continue;
            }

            $warehouse = trim((string) ($node->Warehouse_x0020_Name ?? ''));
            if ($warehouse === '') {
                continue;
            }

            $category = trim((string) ($node->Item_x0020_Category ?? ''));
            if ($category === '') {
                $category = '(tanpa kategori)';
            }

            $uom = trim((string) ($node->UOM ?? ''));

            $rawRows[] = [
                'item_name' => $itemName,
                'category' => $category,
                'warehouse' => $warehouse,
                'uom' => $uom,
                'on_hand' => $onHand,
            ];
        }

        $reader->close();

        if ($rawRows === []) {
            throw new RuntimeException('Data tidak ditemukan di XML.');
        }

        return [
            'rows' => $rawRows,
            'printed_by' => $printedBy,
        ];
    }

    private function groupByWarehouseAndCategory(array $rows): array
    {
        $warehouseGroups = [];

        foreach ($rows as $row) {
            $wh = $row['warehouse'];
            $cat = $row['category'];

            if (! isset($warehouseGroups[$wh])) {
                $warehouseGroups[$wh] = [];
            }

            if (! isset($warehouseGroups[$wh][$cat])) {
                $warehouseGroups[$wh][$cat] = [];
            }

            $warehouseGroups[$wh][$cat][] = $row;
        }

        foreach ($warehouseGroups as $whKey => &$categories) {
            foreach ($categories as $catKey => &$items) {
                usort($items, static fn (array $a, array $b): int => strcasecmp($a['item_name'], $b['item_name']));
            }
            unset($items);

            uksort($categories, static fn (string $a, string $b): int => strcasecmp($a, $b));
        }
        unset($categories);

        uksort($warehouseGroups, static fn (string $a, string $b): int => strcasecmp($a, $b));

        $warehouseTotals = [];
        foreach ($warehouseGroups as $whKey => $categories) {
            $warehouseTotals[$whKey] = ['on_hand' => 0];
            foreach ($categories as $items) {
                foreach ($items as $item) {
                    $warehouseTotals[$whKey]['on_hand'] += $item['on_hand'];
                }
            }
        }

        return [
            'groups' => $warehouseGroups,
            'totals' => $warehouseTotals,
        ];
    }

    private function formatPerDate(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->locale('id')->translatedFormat('d-M-y');
        } catch (Throwable) {
            try {
                return Carbon::createFromFormat('Y-d-m', $value)->locale('id')->translatedFormat('d-M-y');
            } catch (Throwable) {
                return $value;
            }
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
