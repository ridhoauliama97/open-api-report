<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis\SalesByItem;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PersentaseHppPenjualanPerItemFamilyRuReportService
{
    private const TITLE = 'Laporan Persentase HPP Penjualan Per Item Family';

    private const RU_FAMILIES = ['PULAI BJ', 'RAMBUNG BJ', 'JABON BJ', 'Jabon BJ'];

    private const NON_RU_FAMILIES = ['PLASTIK KABINET', 'PLASTIK HOUSEWARE', 'PLASTIK FURNITURE 1', 'PLASTIK FURNITURE 2'];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $company = $filters['company'] ?? 'RU';
        $records = $this->parseXml($xmlContents, $sourceLabel, $company);

        if ($records === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $startDate = isset($filters['start_date']) ? $this->parseDateValue($filters['start_date']) : null;
        if ($startDate === null) {
            $startDate = $this->determineStartDateFromRecords($records);
        }

        $month2Start = $startDate->copy()->startOfMonth();
        $month3Start = $month2Start->copy()->addMonth()->startOfMonth();
        $month4Start = $month3Start->copy()->addMonth()->startOfMonth();
        $month4End = $month4Start->copy()->endOfMonth();

        $dateRange = [
            'month2' => ['start' => $month2Start, 'end' => $month2Start->copy()->endOfMonth()],
            'month3' => ['start' => $month3Start, 'end' => $month3Start->copy()->endOfMonth()],
            'month4' => ['start' => $month4Start, 'end' => $month4Start->copy()->endOfMonth()],
        ];

        $monthLabels = [
            'month2' => $month2Start->locale('id')->isoFormat('MMM-YY'),
            'month3' => $month3Start->locale('id')->isoFormat('MMM-YY'),
            'month4' => $month4Start->locale('id')->isoFormat('MMM-YY'),
        ];

        $assigned = $this->assignMonths($records, $dateRange);

        $familyGroups = $this->buildFamilyGroups($assigned);

        $familyTotals = $this->calculateFamilyTotals($familyGroups);

        $grandTotal = $this->calculateGrandTotal($familyTotals);

        $periodLabel = $this->formatPeriodLabel($filters, $month2Start, $month4End);

        return [
            'title' => self::TITLE,
            'period_label' => $periodLabel,
            'month2_label' => $monthLabels['month2'],
            'month3_label' => $monthLabels['month3'],
            'month4_label' => $monthLabels['month4'],
            'printed_by' => '',
            'family_groups' => $familyGroups,
            'family_totals' => $familyTotals,
            'grand_total' => $grandTotal,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel, string $company): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid: {$sourceLabel}");
        }

        $allowedFamilies = $company === 'RU' ? self::RU_FAMILIES : self::NON_RU_FAMILIES;

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

            if ($company === 'RU') {
                if (str_starts_with($itemCategory, 'BAHAN PEMBANTU') || str_starts_with($itemCategory, 'PERS. LAINNYA')) {
                    continue;
                }
            }

            $itemFamilyName = trim((string) ($node->Item_x0020_Family_x0020_Name ?? ''));
            if ($itemFamilyName === '') {
                continue;
            }

            $familyMatched = false;
            foreach ($allowedFamilies as $af) {
                if (str_starts_with($itemFamilyName, $af)) {
                    $familyMatched = true;
                    break;
                }
            }

            if (! $familyMatched) {
                continue;
            }

            $itemCode = trim((string) ($node->Item_x0020_Code ?? ''));
            $itemName = trim((string) ($node->Item_x0020_Name ?? ''));
            if ($itemName === '' || $itemCode === '') {
                continue;
            }

            $invoiceDate = $this->parseDateValue((string) ($node->Invoice_x0020_Date ?? ''));
            if ($invoiceDate === null) {
                continue;
            }

            $qty = (float) ($node->Smallest_x0020_Quantity ?? $node->Quantity ?? 0);
            $lineCOGS = (float) ($node->Line_x0020_COGS ?? 0);
            $lineGrossProfit = (float) ($node->Line_x0020_Gross_x0020_Profit ?? 0);
            $penjualan = $lineCOGS + $lineGrossProfit;

            $nameItem = $itemName . ' (' . $itemCode . ')';

            $rows[] = [
                'family_name' => $itemFamilyName,
                'item_name' => $itemName,
                'item_code' => $itemCode,
                'name_item' => $nameItem,
                'qty' => $qty,
                'penjualan' => $penjualan,
                'hpp' => $lineCOGS,
                'laba' => $lineGrossProfit,
                'invoice_date' => $invoiceDate,
            ];
        }

        $reader->close();

        return $rows;
    }

    private function determineStartDateFromRecords(array $records): Carbon
    {
        $earliest = null;

        foreach ($records as $row) {
            $date = $row['invoice_date'];
            if ($earliest === null || $date->lt($earliest)) {
                $earliest = $date;
            }
        }

        return $earliest ?? now();
    }

    private function assignMonths(array $records, array $dateRange): array
    {
        $assigned = [];

        foreach ($records as $row) {
            $date = $row['invoice_date'];
            $monthKey = null;

            foreach ($dateRange as $key => $range) {
                if ($date->between($range['start'], $range['end'])) {
                    $monthKey = $key;
                    break;
                }
            }

            if ($monthKey === null) {
                continue;
            }

            $family = $row['family_name'];
            $nameItem = $row['name_item'];

            if (! isset($assigned[$family])) {
                $assigned[$family] = [];
            }

            if (! isset($assigned[$family][$nameItem])) {
                $assigned[$family][$nameItem] = [
                    'name_item' => $nameItem,
                    'item_name' => $row['item_name'],
                    'item_code' => $row['item_code'],
                    'month2' => ['qty' => 0.0, 'penjualan' => 0.0, 'hpp' => 0.0, 'laba' => 0.0],
                    'month3' => ['qty' => 0.0, 'penjualan' => 0.0, 'hpp' => 0.0, 'laba' => 0.0],
                    'month4' => ['qty' => 0.0, 'penjualan' => 0.0, 'hpp' => 0.0, 'laba' => 0.0],
                ];
            }

            $assigned[$family][$nameItem][$monthKey]['qty'] += $row['qty'];
            $assigned[$family][$nameItem][$monthKey]['penjualan'] += $row['penjualan'];
            $assigned[$family][$nameItem][$monthKey]['hpp'] += $row['hpp'];
            $assigned[$family][$nameItem][$monthKey]['laba'] += $row['laba'];
        }

        foreach ($assigned as $family => $items) {
            foreach ($items as $nameItem => $data) {
                $months = ['month2', 'month3', 'month4'];
                foreach ($months as $m) {
                    $p = $data[$m]['penjualan'];
                    $assigned[$family][$nameItem][$m]['persen'] = $p != 0 ? ($data[$m]['laba'] / $p * 100) : 0;
                }

                $persenValues = [];
                foreach ($months as $m) {
                    $v = $assigned[$family][$nameItem][$m]['persen'];
                    if ($v != 0) {
                        $persenValues[] = $v;
                    }
                }

                $assigned[$family][$nameItem]['fr_avg'] = count($persenValues) > 0 ? array_sum($persenValues) / count($persenValues) : 0;
                $assigned[$family][$nameItem]['fr_min'] = count($persenValues) > 0 ? min($persenValues) : 0;
                $assigned[$family][$nameItem]['fr_max'] = count($persenValues) > 0 ? max($persenValues) : 0;
            }

            uksort($assigned[$family], static fn (string $a, string $b): int => $a <=> $b);
        }

        uksort($assigned, static fn (string $a, string $b): int => $a <=> $b);

        return $assigned;
    }

    private function buildFamilyGroups(array $assigned): array
    {
        $groups = [];

        foreach ($assigned as $family => $items) {
            $groups[$family] = array_values($items);
        }

        return $groups;
    }

    private function calculateFamilyTotals(array $familyGroups): array
    {
        $totals = [];

        foreach ($familyGroups as $family => $items) {
            $ft = [
                'month2' => ['qty' => 0.0, 'penjualan' => 0.0, 'hpp' => 0.0, 'laba' => 0.0],
                'month3' => ['qty' => 0.0, 'penjualan' => 0.0, 'hpp' => 0.0, 'laba' => 0.0],
                'month4' => ['qty' => 0.0, 'penjualan' => 0.0, 'hpp' => 0.0, 'laba' => 0.0],
            ];

            foreach ($items as $item) {
                foreach (['month2', 'month3', 'month4'] as $m) {
                    $ft[$m]['qty'] += $item[$m]['qty'];
                    $ft[$m]['penjualan'] += $item[$m]['penjualan'];
                    $ft[$m]['hpp'] += $item[$m]['hpp'];
                    $ft[$m]['laba'] += $item[$m]['laba'];
                }
            }

            foreach (['month2', 'month3', 'month4'] as $m) {
                $p = $ft[$m]['penjualan'];
                $ft[$m]['persen'] = $p != 0 ? ($ft[$m]['laba'] / $p * 100) : 0;
            }

            $persenValues = [];
            foreach (['month2', 'month3', 'month4'] as $m) {
                $v = $ft[$m]['persen'];
                if ($v != 0) {
                    $persenValues[] = $v;
                }
            }

            $ft['fr_avg'] = count($persenValues) > 0 ? array_sum($persenValues) / count($persenValues) : 0;
            $ft['fr_min'] = count($persenValues) > 0 ? min($persenValues) : 0;
            $ft['fr_max'] = count($persenValues) > 0 ? max($persenValues) : 0;

            $totals[$family] = $ft;
        }

        return $totals;
    }

    private function calculateGrandTotal(array $familyTotals): array
    {
        $gt = [
            'month2' => ['qty' => 0.0, 'penjualan' => 0.0, 'hpp' => 0.0, 'laba' => 0.0],
            'month3' => ['qty' => 0.0, 'penjualan' => 0.0, 'hpp' => 0.0, 'laba' => 0.0],
            'month4' => ['qty' => 0.0, 'penjualan' => 0.0, 'hpp' => 0.0, 'laba' => 0.0],
        ];

        foreach ($familyTotals as $ft) {
            foreach (['month2', 'month3', 'month4'] as $m) {
                $gt[$m]['qty'] += $ft[$m]['qty'];
                $gt[$m]['penjualan'] += $ft[$m]['penjualan'];
                $gt[$m]['hpp'] += $ft[$m]['hpp'];
                $gt[$m]['laba'] += $ft[$m]['laba'];
            }
        }

        foreach (['month2', 'month3', 'month4'] as $m) {
            $p = $gt[$m]['penjualan'];
            $gt[$m]['persen'] = $p != 0 ? ($gt[$m]['laba'] / $p * 100) : 0;
        }

        $persenValues = [];
        foreach (['month2', 'month3', 'month4'] as $m) {
            $v = $gt[$m]['persen'];
            if ($v != 0) {
                $persenValues[] = $v;
            }
        }

        $gt['fr_avg'] = count($persenValues) > 0 ? array_sum($persenValues) / count($persenValues) : 0;
        $gt['fr_min'] = count($persenValues) > 0 ? min($persenValues) : 0;
        $gt['fr_max'] = count($persenValues) > 0 ? max($persenValues) : 0;

        return $gt;
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

    private function formatPeriodLabel(array $filters, Carbon $defaultStart, Carbon $defaultEnd): string
    {
        $start = isset($filters['start_date']) ? $this->parseDateValue($filters['start_date']) : null;
        $end = isset($filters['end_date']) ? $this->parseDateValue($filters['end_date']) : null;

        $start ??= $defaultStart;
        $end ??= $defaultEnd;

        return 'Dari '.$start->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$end->locale('id')->isoFormat('DD-MMM-YY');
    }
}
