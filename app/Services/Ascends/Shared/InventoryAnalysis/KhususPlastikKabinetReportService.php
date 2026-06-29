<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class KhususPlastikKabinetReportService
{
    private const TITLE = 'Laporan Khusus Plastik Kabinet';

    private const TARGET_FAMILIES = [
        'PLASTIK KABINET',
        'KOMP. PL KABINET',
        'PERLENGKAPAN LEMARI',
    ];

    private const EXCLUDED_ITEM_CODE_PREFIXES = [
        '2.1.2.8.01.02',
        '2.1.2.8.01.09',
        '2.1.2.8.01.20',
        '2.1.2.8.01.23',
        '2.1.2.8.01.32',
        '2.1.3.4.01.02',
        '2.1.3.4.01.04',
        '2.1.3.4.1.15',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);
        $allRows = $records['rows'];

        if ($allRows === []) {
            throw new RuntimeException('Data Plastik Kabinet tidak ditemukan setelah filter.');
        }

        $sortedRows = $this->sortRows($allRows);
        $totals = $this->calculateTotals($sortedRows);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $this->formatPeriodLabel($filters),
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'rows' => $sortedRows,
            'total_rows' => count($sortedRows),
            'totals' => $totals,
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

            $familyName = trim((string) ($node->Item_x0020_Family_x0020_Name ?? ''));
            $itemCode = trim((string) ($node->Item_x0020_Code ?? ''));
            $itemName = trim((string) ($node->Item_x0020_Name ?? ''));

            if ($itemCode === '' || $itemName === '' || ! $this->isTargetFamily($familyName)) {
                continue;
            }

            if ($this->isExcludedCode($itemCode)) {
                continue;
            }

            $targetFamilyKey = $this->resolveTargetFamilyKey($familyName);
            $isQtyFamily = $targetFamilyKey === 'PLASTIK KABINET';

            $beginning = (float) ($isQtyFamily ? ($node->Beginning ?? 0) : ($node->Beginning_x0020_Value ?? 0));
            $debit = (float) ($isQtyFamily ? ($node->Debit ?? 0) : ($node->Debit_x0020_Value ?? 0));
            $credit = (float) ($isQtyFamily ? ($node->Credit ?? 0) : ($node->Credit_x0020_Value ?? 0));
            $ending = (float) ($isQtyFamily ? ($node->Ending ?? 0) : ($node->Ending_x0020_Value ?? 0));

            $rawRows[] = [
                'item_name' => $itemName,
                'family_key' => $targetFamilyKey,
                'beginning' => $beginning,
                'debit' => $debit,
                'credit' => $credit,
                'ending' => $ending,
            ];
        }

        $reader->close();

        if ($rawRows === []) {
            throw new RuntimeException('Data Plastik Kabinet tidak ditemukan di XML.');
        }

        return [
            'rows' => $rawRows,
            'printed_by' => $printedBy,
        ];
    }

    private function isTargetFamily(string $familyName): bool
    {
        foreach (self::TARGET_FAMILIES as $target) {
            if (str_starts_with($familyName, $target)) {
                return true;
            }
        }

        return false;
    }

    private function resolveTargetFamilyKey(string $familyName): string
    {
        if (str_starts_with($familyName, 'PLASTIK KABINET')) {
            return 'PLASTIK KABINET';
        }

        if (str_starts_with($familyName, 'KOMP. PL KABINET')) {
            return 'KOMP. PL KABINET';
        }

        if (str_starts_with($familyName, 'PERLENGKAPAN LEMARI')) {
            return 'PERLENGKAPAN LEMARI';
        }

        return $familyName;
    }

    private function isExcludedCode(string $itemCode): bool
    {
        foreach (self::EXCLUDED_ITEM_CODE_PREFIXES as $prefix) {
            if (str_starts_with($itemCode, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function sortRows(array $rows): array
    {
        usort($rows, static fn (array $a, array $b): int => strcasecmp($a['item_name'], $b['item_name']));

        return $rows;
    }

    private function calculateTotals(array $rows): array
    {
        $totals = [
            'PLASTIK KABINET' => ['beginning' => 0, 'debit' => 0, 'credit' => 0, 'ending' => 0],
            'KOMP. PL KABINET' => ['beginning' => 0, 'debit' => 0, 'credit' => 0, 'ending' => 0],
            'PERLENGKAPAN LEMARI' => ['beginning' => 0, 'debit' => 0, 'credit' => 0, 'ending' => 0],
        ];

        foreach ($rows as $row) {
            $key = $row['family_key'];

            if (! isset($totals[$key])) {
                continue;
            }

            $totals[$key]['beginning'] += $row['beginning'];
            $totals[$key]['debit'] += $row['debit'];
            $totals[$key]['credit'] += $row['credit'];
            $totals[$key]['ending'] += $row['ending'];
        }

        return $totals;
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
