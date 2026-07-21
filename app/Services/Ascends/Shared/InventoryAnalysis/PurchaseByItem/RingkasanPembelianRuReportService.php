<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis\PurchaseByItem;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class RingkasanPembelianRuReportService
{
    private const TITLE_REKAP = 'Laporan Rekap Pembelian Bahan Baku, Sawn Timber & Bahan Pembantu';

    private const TITLE_RINGKASAN = 'Laporan Ringkasan Pembelian';

    private const CATEGORY_KEYS = ['BAHAN BAKU', 'BAHAN PEMBANTU', 'WIP'];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $detailRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($detailRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        usort($detailRows, static fn (array $a, array $b): int => ($a['sort_date'] ?? '') <=> ($b['sort_date'] ?? ''));

        $summaryRows = $this->buildSummary($detailRows);
        $categoryTotals = $this->calculateCategoryTotals($detailRows);
        $grandTotal = array_sum($categoryTotals);
        $detailGroups = $this->buildDetailGroups($detailRows);

        return [
            'title_rekap' => self::TITLE_REKAP,
            'title_ringkasan' => self::TITLE_RINGKASAN,
            'period_label' => $this->formatPeriodLabel($filters),
            'printed_by' => '',
            'summary_rows' => $summaryRows,
            'detail_rows' => $detailRows,
            'detail_groups' => $detailGroups,
            'category_totals' => $categoryTotals,
            'grand_total' => $grandTotal,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid: {$sourceLabel}");
        }

        $rows = [];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'invoices') {
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

            $category = trim((string) ($node->Item_x0020_Category ?? ''));

            $matched = false;
            foreach (self::CATEGORY_KEYS as $key) {
                if (str_starts_with($category, $key) || $category === $key) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                continue;
            }

            $itemName = trim((string) ($node->Item_x0020_Name ?? ''));
            $supplierName = trim((string) ($node->Supplier_x0020_Name ?? ''));
            if ($itemName === '' || $supplierName === '') {
                continue;
            }

            $purchaseDate = $this->parseDateValue((string) ($node->Purchase_x0020_Date ?? ''));
            $uom = trim((string) ($node->UOM ?? ''));
            $quantity = (float) ($node->Quantity ?? 0);
            $lineTotalLocal = (float) ($node->Line_x0020_Total_x0020__x0028_Local_x0029_ ?? 0);
            $otherCostLocal = (float) ($node->Line_x0020_Total_x0020_Other_x0020_Cost_x0020__x0028_Local_x0029_ ?? 0);
            $ruTotal = $lineTotalLocal + $otherCostLocal;

            $isTon = strtoupper($uom) === 'TON';
            $qty = $isTon ? 0.0 : $quantity;
            $ton = $isTon ? $quantity : 0.0;

            $catKey = $this->resolveCategoryKey($category);

            $rows[] = [
                'category' => $category,
                'category_key' => $catKey,
                'item_name' => $itemName,
                'supplier_name' => $supplierName,
                'purchase_date' => $purchaseDate,
                'sort_date' => $purchaseDate?->format('Y-m-d') ?? '',
                'uom' => $uom,
                'qty' => $qty,
                'ton' => $ton,
                'quantity' => $quantity,
                'ru_total' => $ruTotal,
            ];
        }

        $reader->close();

        return $rows;
    }

    private function resolveCategoryKey(string $category): string
    {
        foreach (self::CATEGORY_KEYS as $key) {
            if (str_starts_with($category, $key) || $category === $key) {
                return $key;
            }
        }

        return 'OTHER';
    }

    private function buildSummary(array $detailRows): array
    {
        $groups = [];

        foreach ($detailRows as $row) {
            $date = $row['purchase_date'];
            $monthKey = $date?->format('Y-m') ?? 'unknown';
            $monthLabel = $date?->locale('id')->isoFormat('MMM-YYYY') ?? 'Unknown';
            $catKey = $row['category_key'];

            if (! isset($groups[$monthKey])) {
                $groups[$monthKey] = [
                    'month_label' => $monthLabel,
                    'totals' => [],
                ];
                foreach (self::CATEGORY_KEYS as $ck) {
                    $groups[$monthKey]['totals'][$ck] = 0.0;
                }
            }

            $groups[$monthKey]['totals'][$catKey] = ($groups[$monthKey]['totals'][$catKey] ?? 0) + $row['ru_total'];
        }

        ksort($groups);

        $grandTotals = [];
        foreach (self::CATEGORY_KEYS as $ck) {
            $grandTotals[$ck] = 0.0;
        }

        $result = [];
        foreach ($groups as $monthKey => $group) {
            $rowTotal = 0.0;
            foreach (self::CATEGORY_KEYS as $ck) {
                $val = $group['totals'][$ck] ?? 0;
                $grandTotals[$ck] += $val;
                $rowTotal += $val;
            }
            $group['row_total'] = $rowTotal;
            $result[] = $group;
        }

        return [
            'rows' => $result,
            'grand_totals' => $grandTotals,
            'grand_total_all' => array_sum($grandTotals),
        ];
    }

    private function buildDetailGroups(array $detailRows): array
    {
        $groups = [];

        foreach (self::CATEGORY_KEYS as $ck) {
            $groups[$ck] = [
                'category_key' => $ck,
                'category_name' => $ck,
                'rows' => [],
                'total' => 0.0,
                'total_ton' => 0.0,
                'total_qty' => 0.0,
            ];
        }

        foreach ($detailRows as $row) {
            $ck = $row['category_key'];
            $groups[$ck]['rows'][] = $row;
            $groups[$ck]['total'] += $row['ru_total'];
            $groups[$ck]['total_ton'] += (float) ($row['ton'] ?? 0);
            $groups[$ck]['total_qty'] += (float) ($row['qty'] ?? 0);
        }

        return array_values($groups);
    }

    private function calculateCategoryTotals(array $detailRows): array
    {
        $totals = [];
        foreach (self::CATEGORY_KEYS as $ck) {
            $totals[$ck] = 0.0;
        }

        foreach ($detailRows as $row) {
            $totals[$row['category_key']] += $row['ru_total'];
        }

        return $totals;
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
