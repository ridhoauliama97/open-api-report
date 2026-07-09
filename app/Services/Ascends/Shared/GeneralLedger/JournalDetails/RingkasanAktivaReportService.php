<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class RingkasanAktivaReportService
{
    private const TITLE = 'Laporan Ringkasan Aktiva Dalam Proses';

    private const TARGET_ACCOUNTS = [
        '121.201.001',
        '121.201.002',
        '121.201.003',
        '121.201.004',
        '121.201.005',
    ];

    private const NO_GROP_VOUCHERS = [
        'RU/IU/21/03/0045',
        'RU/IU/21/03/0046',
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

        $filtered = $this->applyFilters($allRows, $startDate, $endDate);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $computed = $this->computeRows($filtered);

        $groups = $this->groupByAccount($computed);

        $grandTotalDb = array_sum(array_map(static fn (array $g): float => $g['subtotal_db'], $groups));
        $grandTotalCr = array_sum(array_map(static fn (array $g): float => $g['subtotal_cr'], $groups));
        $grandTotal = array_sum(array_map(static fn (array $g): float => $g['subtotal'], $groups));

        $summary = [];
        foreach ($groups as $group) {
            $summary[] = [
                'account_code' => $group['account_code'],
                'account_name' => $group['account_name'],
                'amount' => $group['subtotal'],
            ];
        }

        $period = $this->resolvePeriod($startDate, $endDate);

        return [
            'title' => self::TITLE,
            'company' => $company,
            'period_label' => $period['label'],
            'start_date' => $period['start'],
            'end_date' => $period['end'],
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
            'groups' => $groups,
            'grand_total_db' => $grandTotalDb,
            'grand_total_cr' => $grandTotalCr,
            'grand_total' => $grandTotal,
            'summary' => $summary,
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
        $key = str_replace('_x002F_', '/', $key);

        return str_replace('_x003A_', ':', $key);
    }

    private function applyFilters(array $rows, string $startDate, string $endDate): array
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

            if (! $this->accountFilter($accountCode)) {
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

    private function accountFilter(string $accountCode): bool
    {
        if (in_array($accountCode, self::TARGET_ACCOUNTS, true)) {
            return true;
        }

        if (str_starts_with($accountCode, '121.202')) {
            return true;
        }

        return false;
    }

    private function computeRows(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            $description = (string) ($row['Description'] ?? '');
            $voucherNumber = (string) ($row['Voucher Number'] ?? '');

            $description = $this->applyNoGrop($description, $voucherNumber);

            $result[] = [
                'account_code' => (string) ($row['Account Code'] ?? ''),
                'account_name' => (string) ($row['Account Name'] ?? ''),
                'description' => $description,
                'amount_db' => $amountDb,
                'amount_cr' => $amountCr,
                'amount' => $amountDb - $amountCr,
            ];
        }

        return $result;
    }

    private function applyNoGrop(string $description, string $voucherNumber): string
    {
        if (in_array($voucherNumber, self::NO_GROP_VOUCHERS, true)) {
            return mb_substr($description, 0, 100);
        }

        return $description;
    }

    private function groupByAccount(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $key = $row['account_code'].'|||'.$row['account_name'];

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'account_code' => $row['account_code'],
                    'account_name' => $row['account_name'],
                    'items' => [],
                    'subtotal_db' => 0.0,
                    'subtotal_cr' => 0.0,
                    'subtotal' => 0.0,
                ];
            }

            $grouped[$key]['items'][] = $row;
            $grouped[$key]['subtotal_db'] += $row['amount_db'];
            $grouped[$key]['subtotal_cr'] += $row['amount_cr'];
            $grouped[$key]['subtotal'] += $row['amount'];
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
