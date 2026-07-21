<?php

namespace App\Services\Ascends\Shared\Finance\OutstandingPayableCheck;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class HutangGiroRuReportService
{
    private const TITLE = 'Laporan Hutang Giro';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $rows = $this->parseXml($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $grandTotals = $this->calculateGrandTotals($rows);

        return [
            'title' => self::TITLE,
            'period_label' => $this->formatPeriodLabel($filters),
            'printed_by' => '',
            'rows' => $rows,
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
            if ($supplierName === '') {
                continue;
            }

            $voucherDate = $this->parseDateValue((string) ($node->Voucher_x0020_Date ?? ''));
            $checkDueDate = $this->parseDateValue((string) ($node->Check_x0020_Due_x0020_Date ?? ''));

            $rows[] = [
                'supplier_name' => $supplierName,
                'date' => $voucherDate,
                'due_date' => $checkDueDate,
                'check_number' => trim((string) ($node->Check_x0020_Number ?? '')),
                'net_total' => (float) ($node->Net_x0020_Total ?? 0),
            ];
        }

        $reader->close();

        return $rows;
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

    private function calculateGrandTotals(array $rows): array
    {
        $totals = [
            'net_total' => 0.0,
        ];

        foreach ($rows as $row) {
            $totals['net_total'] += $row['net_total'];
        }

        return $totals;
    }

    private function formatPeriodLabel(array $filters): string
    {
        $perDate = trim((string) ($filters['per_date'] ?? ''));

        if ($perDate === '') {
            return '';
        }

        try {
            $date = Carbon::parse($perDate);

            return 'Per Tanggal : '.$date->locale('id')->isoFormat('DD-MMM-YY');
        } catch (Throwable) {
            return '';
        }
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
