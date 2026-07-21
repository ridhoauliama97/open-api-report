<?php

namespace App\Services\Ascends\Shared\Finance\PayableSummary;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class SaldoHutangRuReportService
{
    private const TITLE = 'Laporan Saldo Hutang';

    private const EXCLUDED_NAME_PATTERNS = [
        'HUTANG GAJI',
        'HUTANG UC',
        'HUTANG GSU',
        'HUTANG BEBAN THR',
        'UPAH BORONGAN',
        'SALDO AWAL',
        'FEE KAYU',
        'KOMPENSASI PEMBELIAN KAYU',
        'Hutang Gaji',
        'Hutang UC',
        'Hutang Beban THR',
        'FEE Kayu',
        'UTAMA CORP-FEE PEMBELIAN',
        'UTAMA CORP-MOULD FEE',
    ];

    private const EXCLUDED_SUPPLIER_CODES = [
        '1.2.2.0.0.S030',
        '1.2.2.0.0.K005',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $filteredRows = $this->applyNameSuppFilter($allRows);

        usort($filteredRows, static fn (array $a, array $b): int => strcasecmp($a['supplier_name'], $b['supplier_name']));

        $grandTotals = $this->calculateGrandTotals($filteredRows);

        return [
            'title' => self::TITLE,
            'period_label' => $this->formatPeriodLabel($filters),
            'printed_by' => '',
            'rows' => $filteredRows,
            'grand_totals' => $grandTotals,
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

            $supplierName = trim((string) ($node->Supplier_x0020_Name ?? ''));
            $supplierCode = trim((string) ($node->Supplier_x0020_Code ?? ''));

            if ($supplierName === '') {
                continue;
            }

            $rows[] = [
                'supplier_name' => $supplierName,
                'supplier_code' => $supplierCode,
                'opening' => (float) ($node->Opening ?? 0),
                'ap_purchase' => (float) ($node->APPurchase ?? 0),
                'ap_note' => (float) ($node->APNote ?? 0),
                'payment' => (float) ($node->Payment ?? 0),
                'ending' => (float) ($node->Ending ?? 0),
            ];
        }

        $reader->close();

        return $rows;
    }

    private function applyNameSuppFilter(array $rows): array
    {
        $filtered = [];

        foreach ($rows as $row) {
            if ($this->isExcludedSupplier($row)) {
                continue;
            }

            $filtered[] = $row;
        }

        return $filtered;
    }

    private function isExcludedSupplier(array $row): bool
    {
        $name = strtoupper($row['supplier_name']);
        $code = $row['supplier_code'];

        if (in_array($code, self::EXCLUDED_SUPPLIER_CODES, true)) {
            return true;
        }

        foreach (self::EXCLUDED_NAME_PATTERNS as $pattern) {
            if (str_contains($name, strtoupper($pattern))) {
                return true;
            }
        }

        return false;
    }

    private function calculateGrandTotals(array $rows): array
    {
        $totals = [
            'opening' => 0.0,
            'ap_purchase' => 0.0,
            'ap_note' => 0.0,
            'payment' => 0.0,
            'ending' => 0.0,
        ];

        foreach ($rows as $row) {
            $totals['opening'] += $row['opening'];
            $totals['ap_purchase'] += $row['ap_purchase'];
            $totals['ap_note'] += $row['ap_note'];
            $totals['payment'] += $row['payment'];
            $totals['ending'] += $row['ending'];
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
