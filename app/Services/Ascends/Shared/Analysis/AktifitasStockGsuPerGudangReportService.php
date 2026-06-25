<?php

namespace App\Services\Ascends\Shared\Analysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class AktifitasStockGsuPerGudangReportService
{
    private const TITLE = 'Ringkasan Valuasi Persediaan Per Gudang';

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

        $filtered = $this->applyRecordSelection($allRows);
        $grouped = $this->groupByCategoryAndFamily($filtered);
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
            $category = trim((string) ($node->Item_x0020_Category ?? ''));
            $familyName = trim((string) ($node->Item_x0020_Family_x0020_Name ?? ''));
            $status = trim((string) ($node->Status ?? ''));
            $uomFactors = trim((string) ($node->UOM_x0020_Factors ?? ''));

            if ($itemName === '' || $status === '') {
                continue;
            }

            if (! str_starts_with($status, 'Active')) {
                continue;
            }

            $beginning = (float) ($node->Beginning ?? 0);
            $beginningValue = (float) ($node->Beginning_x0020_Value ?? 0);
            $purchase = (float) ($node->Purchase ?? 0);
            $purchaseValue = (float) ($node->Purchase_x0020_Value ?? 0);
            $purchaseReturn = (float) ($node->Purchase_x0020_Return ?? 0);
            $purchaseReturnValue = (float) ($node->Purchase_x0020_Return_x0020_Value ?? 0);
            $assmAddlOut = (float) ($node->{'Assm_x002F_Addl.Out'} ?? 0);
            $assmGood = (float) ($node->{'Assm_x002F_Good'} ?? 0);
            $assmWaste = (float) ($node->{'Assm_x002F_Waste'} ?? 0);
            $adjDb = (float) ($node->{'Adjustment_x0020__x0028_DB_x0029_'} ?? 0);
            $mutationIn = (float) ($node->Mutation_x0020_In ?? 0);
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
            $mutationOut = (float) ($node->Mutation_x0020_Out ?? 0);
            $assmMaterialValue = (float) ($node->{'Assm_x002F_Material_x0020_Value'} ?? 0);
            $usageValue = (float) ($node->Usage_x0020_Value ?? 0);
            $adjCrValue = (float) ($node->{'Adjustment_x0020__x0028_CR_x0029__x0020_Value'} ?? 0);
            $ending = (float) ($node->Ending ?? 0);
            $endingValue = (float) ($node->Ending_x0020_Value ?? 0);

            $qtyPembelian = $purchase + ($purchaseReturn * -1);
            $valuePembelian = $purchaseValue + ($purchaseReturnValue * -1);
            $prdQt = $assmAddlOut + $assmGood + $assmWaste + $adjDb + $mutationIn;
            $prdRp = $assmAddlOutValue + $assmGoodValue + $adjDbValue + $assmWasteValue;
            $qtyPenjualan = $sales + ($salesReturn * -1) + $gdn;
            $valuePenjualan = $salesValue + ($salesReturnValue * -1) + $gdnValue;
            $proOutQt = $assmMaterial + $usage + $adjCr + $mutationOut;
            $proOutRp = $assmMaterialValue + $usageValue + $adjCrValue;

            $bdg = self::computeBdg($uomFactors, $category);
            $hpp = self::computeHpp($ending, $category, $uomFactors, $bdg);
            $selisih = self::computeItemSelisih($ending, $sales, $proOutQt);

            $rawRows[] = [
                'item_name' => $itemName,
                'category' => $category,
                'family_name' => $familyName,
                'uom_factors' => $uomFactors,
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
                'sales' => $sales,
                'sales_value' => $salesValue,
                'purchase' => $purchase,
                'purchase_value' => $purchaseValue,
                'ending' => $ending,
                'ending_value' => $endingValue,
                'hpp' => $hpp,
                'selisih' => $selisih,
                'bdg' => $bdg,
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

    private function applyRecordSelection(array $rows): array
    {
        return array_values(array_filter($rows, static fn (array $row): bool => self::computeHasilNull($row) !== 0.0));
    }

    private static function computeBdg(string $uomFactors, string $category): float
    {
        if ($uomFactors === '' || $category !== 'BARANG DAGANG') {
            return 0;
        }

        $full = str_replace([' ', ','], ['', '.'], $uomFactors);
        $parts = explode('/', $full);
        $last = end($parts);

        if ($last !== false && is_numeric($last)) {
            return (float) $last;
        }

        return 0;
    }

    private static function computeHpp(float $ending, string $category, string $uomFactors, float $bdg): float
    {
        if ($bdg !== 0.0 && $category === 'BARANG DAGANG') {
            return ($bdg > 0) ? $ending / $bdg : $ending;
        }

        if ($category === 'BARANG JADI' && $uomFactors !== '') {
            $uomNumber = (float) $uomFactors;
            if ($uomNumber > 0) {
                return $ending / $uomNumber;
            }
        }

        if ($bdg !== 0.0 && $category === 'BAHAN BAKU') {
            return ($bdg > 0) ? $ending / $bdg : $ending;
        }

        return $ending;
    }

    private static function computeHasilNull(array $row): float
    {
        $adjustedSawalRp = self::adjustTinyValue($row['beginning_value']);
        $adjustedEndingValue = self::adjustTinyValue($row['ending_value']);

        return $row['beginning']
            + $adjustedSawalRp
            + abs($row['qty_pembelian'])
            + abs($row['value_pembelian'])
            + $row['prd_qt']
            + $row['prd_rp']
            + $row['sales']
            + $row['sales_value']
            + $row['pro_out_qt']
            + $row['pro_out_rp']
            + $row['ending']
            + $adjustedEndingValue;
    }

    private static function adjustTinyValue(float $value): float
    {
        if ($value === 0.000 || $value === 0.001 || $value === -0.0001 || $value === -0.0002) {
            return 0;
        }

        if ($value > 0 && $value < 0.004) {
            return 0;
        }

        return $value;
    }

    public static function computeItemSelisih(float $ending, float $sales, float $proOutQt): float
    {
        $denominator = $sales + $proOutQt;

        if ($ending == 0 || $denominator == 0) {
            return 0;
        }

        return $ending / $denominator;
    }

    public static function computeSelisih2(array $totals): float
    {
        $denominator = $totals['sales'] + $totals['pro_out_qt'];

        if ($totals['ending'] == 0 || $denominator == 0) {
            return 0;
        }

        return $totals['ending'] / $denominator;
    }

    public static function computeItemHpp(float $ending, string $category, string $uomFactors, float $bdg): float
    {
        return self::computeHpp($ending, $category, $uomFactors, $bdg);
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
            'sales' => 0,
            'sales_value' => 0,
            'purchase' => 0,
            'purchase_value' => 0,
            'ending' => 0,
            'ending_value' => 0,
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
            $totals['sales'] += $row['sales'];
            $totals['sales_value'] += $row['sales_value'];
            $totals['purchase'] += $row['purchase'];
            $totals['purchase_value'] += $row['purchase_value'];
            $totals['ending'] += $row['ending'];
            $totals['ending_value'] += $row['ending_value'];
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
