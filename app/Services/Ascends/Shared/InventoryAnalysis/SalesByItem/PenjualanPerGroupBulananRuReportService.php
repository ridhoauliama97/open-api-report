<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis\SalesByItem;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PenjualanPerGroupBulananRuReportService
{
    private const TITLE = 'Laporan Penjualan Per Item Family';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel, $filters);

        if ($records === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        usort($records, static fn (array $a, array $b): int => ($a['sort_group'] ?? '') <=> ($b['sort_group'] ?? '') ?: ($a['sort_item'] ?? '') <=> ($b['sort_item'] ?? ''));

        $familyGroups = $this->buildFamilyGroups($records);

        $familySubtotals = $this->calculateFamilySubtotals($records);

        $grandTotal = $this->calculateGrandTotal($records);
        $grandQty = array_sum(array_column($records, 'qty'));

        $grandPenjualan = $grandTotal['penjualan'];
        $grandHpp = $grandTotal['hpp'];
        $grandLaba = $grandTotal['laba'];
        $grandPersen = $grandPenjualan != 0 ? ($grandLaba / $grandPenjualan * 100) : 0;

        return [
            'title' => self::TITLE,
            'period_label' => $this->formatPeriodLabel($filters),
            'printed_by' => '',
            'family_groups' => $familyGroups,
            'family_subtotals' => $familySubtotals,
            'grand_qty' => $grandQty,
            'grand_penjualan' => $grandPenjualan,
            'grand_hpp' => $grandHpp,
            'grand_laba' => $grandLaba,
            'grand_persen' => $grandPersen,
            'records' => $records,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel, array $filters): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid: {$sourceLabel}");
        }

        $startDate = isset($filters['start_date']) ? $this->parseDateValue($filters['start_date']) : null;
        $endDate = isset($filters['end_date']) ? $this->parseDateValue($filters['end_date']) : null;

        $rows = [];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'Invoices') {
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

            $type = trim((string) ($node->Type ?? ''));
            if ($type !== 'Sales Invoice') {
                continue;
            }

            $itemCategory = trim((string) ($node->Item_x0020_Category ?? ''));
            if ($itemCategory === '') {
                continue;
            }

            if (str_starts_with($itemCategory, 'BAHAN PEMBANTU') || str_starts_with($itemCategory, 'PERS. LAINNYA')) {
                continue;
            }

            $itemFamilyName = trim((string) ($node->Item_x0020_Family_x0020_Name ?? ''));
            if ($itemFamilyName === '') {
                continue;
            }

            if (str_starts_with($itemFamilyName, 'KAYU SISA')) {
                continue;
            }

            $itemName = trim((string) ($node->Item_x0020_Name ?? ''));
            if ($itemName === '') {
                continue;
            }

            $invoiceDate = $this->parseDateValue((string) ($node->Invoice_x0020_Date ?? ''));
            if ($invoiceDate === null) {
                continue;
            }

            if ($startDate !== null && $invoiceDate->lt($startDate)) {
                continue;
            }

            if ($endDate !== null && $invoiceDate->gt($endDate)) {
                continue;
            }

            $qty = (float) ($node->Smallest_x0020_Quantity ?? $node->Quantity ?? 0);
            $lineCOGS = (float) ($node->Line_x0020_COGS ?? 0);
            $lineGrossProfit = (float) ($node->Line_x0020_Gross_x0020_Profit ?? 0);
            $penjualan = $lineCOGS + $lineGrossProfit;

            $uom = trim((string) ($node->UOM ?? ''));

            $nameGroup = $this->resolveNameGroup($itemFamilyName);

            $rows[] = [
                'name_group' => $nameGroup,
                'family_name' => $itemFamilyName,
                'item_name' => $itemName,
                'qty' => $qty,
                'penjualan' => $penjualan,
                'hpp' => $lineCOGS,
                'laba' => $lineGrossProfit,
                'persen' => $penjualan != 0 ? ($lineGrossProfit / $penjualan * 100) : 0,
                'uom' => $uom,
                'invoice_date' => $invoiceDate,
                'sort_group' => $nameGroup,
                'sort_item' => $itemName,
            ];
        }

        $reader->close();

        return $rows;
    }

    private function resolveNameGroup(string $familyName): string
    {
        if (str_contains($familyName, 'KOMP. PL KAB')) {
            return 'KOMPONEN PL KABINET';
        }

        return $familyName;
    }

    private function buildFamilyGroups(array $records): array
    {
        $groups = [];

        foreach ($records as $row) {
            $group = $row['name_group'];
            $item = $row['item_name'];

            if (! isset($groups[$group])) {
                $groups[$group] = [];
            }

            if (! isset($groups[$group][$item])) {
                $groups[$group][$item] = [
                    'item_name' => $item,
                    'uom' => $row['uom'],
                    'qty' => 0.0,
                    'penjualan' => 0.0,
                    'hpp' => 0.0,
                    'laba' => 0.0,
                ];
            }

            $groups[$group][$item]['qty'] += $row['qty'];
            $groups[$group][$item]['penjualan'] += $row['penjualan'];
            $groups[$group][$item]['hpp'] += $row['hpp'];
            $groups[$group][$item]['laba'] += $row['laba'];
        }

        foreach ($groups as $group => $items) {
            foreach ($items as $item => $data) {
                $penjualan = $data['penjualan'];
                $groups[$group][$item]['persen'] = $penjualan != 0 ? ($data['laba'] / $penjualan * 100) : 0;
            }

            uksort($groups[$group], static fn (string $a, string $b): int => $a <=> $b);
        }

        uksort($groups, static fn (string $a, string $b): int => $a <=> $b);

        return $groups;
    }

    private function calculateFamilySubtotals(array $records): array
    {
        $totals = [];

        foreach ($records as $row) {
            $group = $row['name_group'];

            if (! isset($totals[$group])) {
                $totals[$group] = [
                    'qty' => 0.0,
                    'penjualan' => 0.0,
                    'hpp' => 0.0,
                    'laba' => 0.0,
                    'uom' => $row['uom'],
                ];
            }

            $totals[$group]['qty'] += $row['qty'];
            $totals[$group]['penjualan'] += $row['penjualan'];
            $totals[$group]['hpp'] += $row['hpp'];
            $totals[$group]['laba'] += $row['laba'];
        }

        foreach ($totals as $group => $data) {
            $penjualan = $data['penjualan'];
            $totals[$group]['persen'] = $penjualan != 0 ? ($data['laba'] / $penjualan * 100) : 0;
        }

        return $totals;
    }

    private function calculateGrandTotal(array $records): array
    {
        $total = ['penjualan' => 0.0, 'hpp' => 0.0, 'laba' => 0.0];

        foreach ($records as $row) {
            $total['penjualan'] += $row['penjualan'];
            $total['hpp'] += $row['hpp'];
            $total['laba'] += $row['laba'];
        }

        return $total;
    }

    private function parseDateValue(string $value): ?Carbon
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

        return 'Dari '.$start->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$end->locale('id')->isoFormat('DD-MMM-YY');
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
}
