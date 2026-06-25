<?php

namespace App\Services\Ascends\Shared\Analysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class AktifitasStockGsuReportService
{
    private const TITLE = 'Ringkasan Valuasi Persediaan';

    private const CATEGORY_ORDER = [
        'BAHAN BAKU' => 1,
        'LIMBAH BAHAN BAKU' => 2,
        'SAWN TIMBER' => 3,
        'WORK IN PROGRESS' => 4,
        'WIP' => 4,
        'BARANG JADI' => 30,
    ];

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

        $grouped = $this->groupByCategoryAndFamily($allRows);
        $grandTotals = $this->calculateGrandTotals($grouped);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $this->formatPeriodLabel($filters),
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'categories' => $grouped,
            'grand_totals' => $grandTotals,
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

            $itemName = trim((string) ($node->Item_x0020_Name ?? ''));
            if ($itemName === '') {
                continue;
            }

            $category = trim((string) ($node->Item_x0020_Category ?? ''));
            $familyName = trim((string) ($node->Item_x0020_Family_x0020_Name ?? ''));

            $beginning = (float) ($node->Beginning ?? 0);
            $beginningValue = (float) ($node->Beginning_x0020_Value ?? 0);
            $purchase = (float) ($node->Purchase ?? 0);
            $purchaseValue = (float) ($node->Purchase_x0020_Value ?? 0);
            $purchaseReturn = (float) ($node->Purchase_x0020_Return ?? 0);
            $purchaseReturnValue = (float) ($node->Purchase_x0020_Return_x0020_Value ?? 0);
            $batchOutput = (float) ($node->{'Batch_x0020__x0028_Output_x0029_'} ?? 0);
            $batchOutputValue = (float) ($node->{'Batch_x0020__x0028_Output_x0029__x0020_Value'} ?? 0);
            $assmAddlOut = (float) ($node->{'Assm_x002F_Addl.Out'} ?? 0);
            $assmGood = (float) ($node->{'Assm_x002F_Good'} ?? 0);
            $assmWaste = (float) ($node->{'Assm_x002F_Waste'} ?? 0);
            $adjDb = (float) ($node->{'Adjustment_x0020__x0028_DB_x0029_'} ?? 0);
            $assmAddlOutValue = (float) ($node->{'Assm_x002F_Addl.Out_x0020_Value'} ?? 0);
            $assmGoodValue = (float) ($node->{'Assm_x002F_Good_x0020_Value'} ?? 0);
            $adjDbValue = (float) ($node->{'Adjustment_x0020__x0028_DB_x0029__x0020_Value'} ?? 0);
            $assmWasteValue = (float) ($node->{'Assm_x002F_Waste_x0020_Value'} ?? 0);
            $sales = (float) ($node->Sales ?? 0);
            $salesValue = (float) ($node->Sales_x0020_Value ?? 0);
            $salesReturn = (float) ($node->Sales_x0020_Return ?? 0);
            $salesReturnValue = (float) ($node->Sales_x0020_Return_x0020_Value ?? 0);
            $gdn = (float) ($node->Goods_x0020_Delivery_x0020_Note ?? 0);
            $gdnValue = (float) ($node->Goods_x0020_Delivery_x0020_Note_x0020_Value ?? 0);
            $assmMaterial = (float) ($node->{'Assm_x002F_Material'} ?? 0);
            $usage = (float) ($node->Usage ?? 0);
            $adjCr = (float) ($node->{'Adjustment_x0020__x0028_CR_x0029_'} ?? 0);
            $assmMaterialValue = (float) ($node->{'Assm_x002F_Material_x0020_Value'} ?? 0);
            $usageValue = (float) ($node->Usage_x0020_Value ?? 0);
            $adjCrValue = (float) ($node->{'Adjustment_x0020__x0028_CR_x0029__x0020_Value'} ?? 0);
            $ending = (float) ($node->Ending ?? 0);
            $endingValue = (float) ($node->Ending_x0020_Value ?? 0);

            $qtyPembelian = $purchase + ($purchaseReturn * -1) + $batchOutput;
            $valuePembelian = $purchaseValue + ($purchaseReturnValue * -1) + $batchOutputValue;
            $prdQt = $assmAddlOut + $assmGood + $assmWaste + $adjDb;
            $prdRp = $assmAddlOutValue + $assmGoodValue + $adjDbValue + $assmWasteValue;
            $qtyPenjualan = $sales + ($salesReturn * -1) + $gdn;
            $valuePenjualan = $salesValue + ($salesReturnValue * -1) + $gdnValue;
            $proOutQt = $assmMaterial + $usage + $adjCr;
            $proOutRp = $assmMaterialValue + $usageValue + $adjCrValue;

            $rawRows[] = [
                'item_name' => $itemName,
                'category' => $category,
                'family_name' => $familyName,
                'beginning' => $beginning,
                'beginning_value' => $beginningValue,
                'qty_pembelian' => $qtyPembelian,
                'value_pembelian' => $valuePembelian,
                'prd_qt' => $prdQt,
                'prd_rp' => $prdRp,
                'qty_penjualan' => $qtyPenjualan,
                'value_penjualan' => $valuePenjualan,
                'pro_out_qt' => $proOutQt,
                'pro_out_rp' => $proOutRp,
                'ending' => $ending,
                'ending_value' => $endingValue,
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

    private function resolveCategoryOrder(string $category): int
    {
        return self::CATEGORY_ORDER[$category] ?? 9;
    }

    private function groupByCategoryAndFamily(array $rows): array
    {
        $categoryGroups = [];

        foreach ($rows as $row) {
            $cat = $row['category'] !== '' ? $row['category'] : '(tanpa kategori)';

            if (! isset($categoryGroups[$cat])) {
                $categoryGroups[$cat] = [
                    'category' => $cat,
                    'order' => $this->resolveCategoryOrder($row['category']),
                    'families' => [],
                    'totals' => $this->emptyRowValues(),
                ];
            }

            $fam = $row['family_name'] !== '' ? $row['family_name'] : '(tanpa keluarga)';

            if (! isset($categoryGroups[$cat]['families'][$fam])) {
                $categoryGroups[$cat]['families'][$fam] = [
                    'family_name' => $fam,
                    'items' => [],
                    'totals' => $this->emptyRowValues(),
                ];
            }

            $categoryGroups[$cat]['families'][$fam]['items'][] = $row;
        }

        foreach ($categoryGroups as $catKey => &$catGroup) {
            foreach ($catGroup['families'] as $famKey => &$family) {
                usort($family['items'], static fn (array $a, array $b): int => strcasecmp($a['item_name'], $b['item_name']));

                $family['totals'] = $this->sumRows($family['items']);
                $catGroup['totals'] = $this->accumulateTotals($catGroup['totals'], $family['totals']);
            }
            unset($family);

            uksort($catGroup['families'], static fn (string $a, string $b): int => strcasecmp($a, $b));
            $catGroup['families'] = array_values($catGroup['families']);
        }
        unset($catGroup);

        uasort($categoryGroups, static fn (array $a, array $b): int => $a['order'] <=> $b['order'] ?: strcasecmp($a['category'], $b['category']));

        return array_values($categoryGroups);
    }

    private function calculateGrandTotals(array $categories): array
    {
        $totals = $this->emptyRowValues();

        foreach ($categories as $cat) {
            $totals = $this->accumulateTotals($totals, $cat['totals']);
        }

        return $totals;
    }

    private function emptyRowValues(): array
    {
        return [
            'beginning' => 0,
            'beginning_value' => 0,
            'qty_pembelian' => 0,
            'value_pembelian' => 0,
            'prd_qt' => 0,
            'prd_rp' => 0,
            'qty_penjualan' => 0,
            'value_penjualan' => 0,
            'pro_out_qt' => 0,
            'pro_out_rp' => 0,
            'ending' => 0,
            'ending_value' => 0,
            'hpp' => 0,
            'selisih' => 0,
        ];
    }

    private function sumRows(array $rows): array
    {
        $totals = $this->emptyRowValues();

        foreach ($rows as $row) {
            $totals['beginning'] += $row['beginning'];
            $totals['beginning_value'] += $row['beginning_value'];
            $totals['qty_pembelian'] += $row['qty_pembelian'];
            $totals['value_pembelian'] += $row['value_pembelian'];
            $totals['prd_qt'] += $row['prd_qt'];
            $totals['prd_rp'] += $row['prd_rp'];
            $totals['qty_penjualan'] += $row['qty_penjualan'];
            $totals['value_penjualan'] += $row['value_penjualan'];
            $totals['pro_out_qt'] += $row['pro_out_qt'];
            $totals['pro_out_rp'] += $row['pro_out_rp'];
            $totals['ending'] += $row['ending'];
            $totals['ending_value'] += $row['ending_value'];

            $hpp = ($row['ending'] > 0) ? $row['ending_value'] / $row['ending'] : 0;
            $totals['hpp'] += $hpp;

            $salesProOut = $row['qty_penjualan'] + $row['pro_out_qt'];
            $selisih = ($row['ending'] > 0 && $salesProOut > 0) ? $row['ending'] / $salesProOut : 0;
            $totals['selisih'] += $selisih;
        }

        return $totals;
    }

    private function accumulateTotals(array $target, array $source): array
    {
        foreach ($target as $key => $value) {
            $target[$key] = $value + ($source[$key] ?? 0);
        }

        return $target;
    }

    public static function computeSelisih2(array $totals): float
    {
        $denominator = $totals['qty_penjualan'] + $totals['pro_out_qt'];

        if ($totals['ending'] == 0 || $denominator == 0) {
            return 0;
        }

        return $totals['ending'] / $denominator;
    }

    public static function computeItemHpp(float $ending, float $endingValue): float
    {
        return ($ending > 0) ? $endingValue / $ending : 0;
    }

    public static function computeItemSelisih(float $ending, float $qtyPenjualan, float $proOutQt): float
    {
        $denominator = $qtyPenjualan + $proOutQt;

        if ($ending == 0 || $denominator == 0) {
            return 0;
        }

        return $ending / $denominator;
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
