<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class BiayaProduksiTidakLangsungReportService
{
    private const TITLE = 'Laporan Biaya Produksi Tidak Langsung';

    private const RU_PREFIXES = ['500.001', '500.005', '500.015', '500.010', '500.006', '500.018'];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data jurnal tidak ditemukan pada XML.');
        }

        $company = trim((string) ($filters['company'] ?? ''));
        $startDate = trim((string) ($filters['Date.StartDate'] ?? $filters['Date_StartDate'] ?? $filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['Date.EndDate'] ?? $filters['Date_EndDate'] ?? $filters['EndDate'] ?? $filters['end_date'] ?? ''));

        $filtered = $this->applyFilters($allRows, $company, $startDate, $endDate);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $groups = $this->groupByAccount($filtered);

        $grandTotalDb = array_sum(array_map(static fn (array $g): float => $g['subtotal_db'], $groups));
        $grandTotalCr = array_sum(array_map(static fn (array $g): float => $g['subtotal_cr'], $groups));
        $grandTotal = array_sum(array_map(static fn (array $g): float => $g['subtotal'], $groups));

        $period = $this->resolvePeriod($startDate, $endDate);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $period['label'],
            'start_date' => $period['start'],
            'end_date' => $period['end'],
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
            'groups' => $groups,
            'grand_total_db' => $grandTotalDb,
            'grand_total_cr' => $grandTotalCr,
            'grand_total' => $grandTotal,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'invoices') {
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

            $row = [];
            foreach ($node->children() as $key => $value) {
                $cleanKey = $this->cleanXmlKey((string) $key);
                $row[$cleanKey] = trim((string) $value);
            }

            if (($row['Account Code'] ?? '') !== '') {
                $rows[] = $row;
            }
        }

        $reader->close();

        return $rows;
    }

    private function cleanXmlKey(string $key): string
    {
        $key = str_replace('_x0020_', ' ', $key);
        $key = str_replace('_x0028_', '(', $key);
        $key = str_replace('_x0029_', ')', $key);

        return str_replace('_x002F_', '/', $key);
    }

    private function applyFilters(array $rows, string $company, string $startDate, string $endDate): array
    {
        $start = null;
        $end = null;

        if ($startDate !== '') {
            try {
                $start = Carbon::parse($startDate)->startOfDay();
            } catch (Throwable) {
            }
        }

        if ($endDate !== '') {
            try {
                $end = Carbon::parse($endDate)->endOfDay();
            } catch (Throwable) {
            }
        }

        $result = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');

            if (! $this->accountFilter($accountCode, $company)) {
                continue;
            }

            if ($start !== null || $end !== null) {
                $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
                if ($voucherDate === '') {
                    continue;
                }

                try {
                    $vd = Carbon::parse($voucherDate);

                    if ($start !== null && $vd->lessThan($start)) {
                        continue;
                    }

                    if ($end !== null && $vd->greaterThan($end)) {
                        continue;
                    }
                } catch (Throwable) {
                    continue;
                }
            }

            $result[] = $row;
        }

        return $result;
    }

    private function accountFilter(string $accountCode, string $company): bool
    {
        if (strtoupper($company) === 'GSU') {
            if ($accountCode === '514.000.018') {
                return false;
            }

            $code7 = substr($accountCode, 0, 7);
            if ($code7 === '514.000') {
                return true;
            }

            if ($code7 === '511.000') {
                return true;
            }

            return false;
        }

        // Default: RU
        $code7 = substr($accountCode, 0, 7);
        foreach (self::RU_PREFIXES as $prefix) {
            if ($code7 === $prefix) {
                return true;
            }
        }

        return false;
    }

    private function groupByAccount(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $description = (string) ($row['Description'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            $key = $accountCode.'|||'.$accountName;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'account_code' => $accountCode,
                    'account_name' => $accountName,
                    'items' => [],
                    'subtotal_db' => 0.0,
                    'subtotal_cr' => 0.0,
                    'subtotal' => 0.0,
                ];
            }

            $grouped[$key]['items'][] = [
                'description' => $description,
                'amount_db' => $amountDb,
                'amount_cr' => $amountCr,
                'amount' => $amountDb - $amountCr,
            ];

            $grouped[$key]['subtotal_db'] += $amountDb;
            $grouped[$key]['subtotal_cr'] += $amountCr;
            $grouped[$key]['subtotal'] += $amountDb - $amountCr;
        }

        ksort($grouped);

        return array_values($grouped);
    }

    private function resolvePeriod(string $startDate, string $endDate): array
    {
        $startLabel = '';
        $endLabel = '';

        if ($startDate !== '') {
            try {
                $startLabel = Carbon::parse($startDate)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (Throwable) {
                $startLabel = $startDate;
            }
        }

        if ($endDate !== '') {
            try {
                $endLabel = Carbon::parse($endDate)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (Throwable) {
                $endLabel = $endDate;
            }
        }

        return [
            'start' => $startLabel,
            'end' => $endLabel,
            'label' => 'Dari '.$startLabel.' s/d '.$endLabel,
        ];
    }
}
