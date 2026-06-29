<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LaporanHppDanStockReportService
{
    private const TITLE = 'Laporan HPP Dan Stock';

    private const AA_FAMILIES = ['ENAMEL', 'STAINLESS', 'SAPU'];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);
        $allRows = $records['rows'];

        if ($allRows === []) {
            throw new RuntimeException('Data stock tidak ditemukan di XML.');
        }

        $familyGroups = $this->groupRows($allRows);
        $grandStockValue = 0;

        foreach ($familyGroups as &$group) {
            $group['subtotal_stock_value'] = array_sum(array_map(
                static fn (array $item): float => (float) ($item['stock_value'] ?? 0),
                $group['items']
            ));
            $grandStockValue += $group['subtotal_stock_value'];
        }
        unset($group);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $this->formatPeriodLabel($filters),
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'family_groups' => $familyGroups,
            'total_families' => count($familyGroups),
            'grand_stock_value' => $grandStockValue,
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
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'test') {
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

            $itemCode = trim((string) ($node->Item_x0020_Code ?? ''));
            $itemName = trim((string) ($node->Item_x0020_Name ?? ''));
            $familyName = trim((string) ($node->Item_x0020_Family_x0020_Name ?? ''));

            if ($itemCode === '' || $itemName === '') {
                continue;
            }

            $ending = (float) ($node->Ending ?? 0);
            $endingValue = (float) ($node->Ending_x0020_Value ?? 0);
            $uom = trim((string) ($node->UOM ?? 'BH'));
            $uomFactors = trim((string) ($node->UOM_x0020_Factors ?? ''));

            $cog = ($endingValue > 0 && $ending > 0) ? $endingValue / $ending : 0;

            [$ratio1, $ratio2] = $this->parseUomFactors($uomFactors);
            $kaliQty = $ratio1 * $ratio2;
            $nameGrp = $this->resolveNameGrp($familyName);

            $displayUom = $uom;
            if ($nameGrp === 'AA' && $ending > $kaliQty) {
                $displayUom = 'DUS';
            }

            $rawRows[] = [
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'family_name' => $familyName,
                'cog' => $cog,
                'ending' => $ending,
                'stock_value' => $endingValue,
                'uom' => $displayUom,
            ];
        }

        $reader->close();

        if ($rawRows === []) {
            throw new RuntimeException('Data stock tidak ditemukan di XML.');
        }

        return [
            'rows' => $rawRows,
            'printed_by' => $printedBy,
        ];
    }

    private function parseUomFactors(string $factors): array
    {
        if ($factors === '') {
            return [1, 1];
        }

        $parts = explode('/', $factors);
        $ratio1 = isset($parts[0]) ? (float) trim($parts[0]) : 1;
        $ratio2 = isset($parts[1]) ? (float) trim($parts[1]) : 1;

        return [$ratio1, $ratio2];
    }

    private function resolveNameGrp(string $familyName): string
    {
        return in_array($familyName, self::AA_FAMILIES, true) ? 'AA' : ' ';
    }

    private function groupRows(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = $row['family_name'] !== '' ? $row['family_name'] : '(tanpa kategori)';

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'family_name' => $key,
                    'items' => [],
                ];
            }

            $groups[$key]['items'][] = [
                'item_code' => $row['item_code'],
                'item_name' => $row['item_name'],
                'cog' => $row['cog'],
                'ending' => $row['ending'],
                'stock_value' => $row['stock_value'],
                'uom' => $row['uom'],
            ];
        }

        ksort($groups);

        $sorted = [];

        foreach ($groups as $group) {
            usort($group['items'], static fn (array $a, array $b): int => strcasecmp($a['item_code'], $b['item_code']));
            $group['items'] = array_values($group['items']);
            $sorted[] = $group;
        }

        return $sorted;
    }

    private function formatPeriodLabel(array $filters): string
    {
        $start = self::parseDate($filters['start_date'] ?? '');
        $end = self::parseDate($filters['end_date'] ?? '');

        if ($start === null && $end === null) {
            return '';
        }

        $start ??= $end?->copy();
        $end ??= $start?->copy();

        if ($start === null || $end === null) {
            return '';
        }

        return 'Dari '.$start->locale('id')->translatedFormat('d-M-y').' Sampai '.$end->locale('id')->translatedFormat('d-M-y');
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
