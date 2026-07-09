<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class BebanUmumBaruReportService
{
    private const TITLE = 'Laporan Biaya Beban Umum';

    private const UC_EXCLUDED = [
        '721.000.251',
        '721.000.254',
        '721.000.260',
        '721.000.261',
        '721.000.257',
    ];

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

        $rows = $this->aggregateByAccount($filtered);

        $grandTotalDb = array_sum(array_map(static fn (array $r): float => $r['amount_db'], $rows));
        $grandTotalCr = array_sum(array_map(static fn (array $r): float => $r['amount_cr'], $rows));
        $grandTotal = array_sum(array_map(static fn (array $r): float => $r['amount'], $rows));

        $period = $this->resolvePeriod($startDate, $endDate);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $period['label'],
            'start_date' => $period['start'],
            'end_date' => $period['end'],
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
            'rows' => $rows,
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
        if (strtoupper($company) === 'UC') {
            if (in_array($accountCode, self::UC_EXCLUDED, true)) {
                return false;
            }
        }

        $prefix3 = substr($accountCode, 0, 3);
        if ($prefix3 === '721') {
            return true;
        }

        if ($accountCode === '711.000.091') {
            return true;
        }

        return false;
    }

    private function aggregateByAccount(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            $key = $accountCode.'|||'.$accountName;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'account_code' => $accountCode,
                    'account_name' => $accountName,
                    'amount_db' => 0.0,
                    'amount_cr' => 0.0,
                    'amount' => 0.0,
                ];
            }

            $grouped[$key]['amount_db'] += $amountDb;
            $grouped[$key]['amount_cr'] += $amountCr;
            $grouped[$key]['amount'] += $amountDb - $amountCr;
        }

        $result = array_values($grouped);

        usort($result, static fn (array $a, array $b): int => strcmp($a['account_code'], $b['account_code']));

        foreach ($result as &$item) {
            $item['amount_db'] = round($item['amount_db'], 2);
            $item['amount_cr'] = round($item['amount_cr'], 2);
            $item['amount'] = round($item['amount'], 2);
        }

        return $result;
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
