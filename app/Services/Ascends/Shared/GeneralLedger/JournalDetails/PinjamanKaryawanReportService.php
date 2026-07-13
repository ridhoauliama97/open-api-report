<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PinjamanKaryawanReportService
{
    private const TITLE = 'Laporan Ringkasan Pinjaman Karyawan';

    private const GSU_ACCOUNT_CODES = ['111.200.300'];

    private const UC_ACCOUNT_PREFIXES = [
        '112.100.3A1', '112.100.3A2', '112.100.3A3', '112.100.3A4',
        '112.100.3B1', '112.100.3E1', '112.100.3K1', '112.100.3M1',
        '112.100.3W1', '112.100.999', '112.100.3F1',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data jurnal tidak ditemukan pada XML.');
        }

        $company = trim((string) ($filters['company'] ?? ''));
        $rawStartDate = trim((string) ($filters['Date.StartDate'] ?? $filters['Date_StartDate'] ?? $filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $rawEndDate = trim((string) ($filters['Date.EndDate'] ?? $filters['Date_EndDate'] ?? $filters['EndDate'] ?? $filters['end_date'] ?? ''));

        if ($rawStartDate === '' && $rawEndDate === '') {
            throw new RuntimeException('Parameter Date.StartDate dan Date.EndDate wajib dikirim.');
        }

        $startDate = Carbon::parse($rawStartDate);
        $endDate = Carbon::parse($rawEndDate);

        $filtered = $this->filterRows($allRows, $company, $startDate, $endDate);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $grouped = $this->groupByAccount($filtered);

        $grandTotalDb = 0;
        $grandTotalCr = 0;

        foreach ($grouped as &$account) {
            $account['items'] = $this->sortItemsByDate($account['items']);

            $subtotalDb = 0;
            $subtotalCr = 0;
            foreach ($account['items'] as $item) {
                $subtotalDb += $item['amount_db'];
                $subtotalCr += $item['amount_cr'];
            }
            $account['subtotal_db'] = $subtotalDb;
            $account['subtotal_cr'] = $subtotalCr;
            $account['subtotal'] = $subtotalDb - $subtotalCr;

            $grandTotalDb += $subtotalDb;
            $grandTotalCr += $subtotalCr;
        }
        unset($account);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => 'Dari '.$startDate->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$endDate->locale('id')->isoFormat('DD-MMM-YY'),
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
            'accounts' => $grouped,
            'grand_total_db' => $grandTotalDb,
            'grand_total_cr' => $grandTotalCr,
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

    private function filterRows(array $rows, string $company, Carbon $startDate, Carbon $endDate): array
    {
        $result = [];
        $isGsu = strcasecmp($company, 'gsu') === 0;

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');

            if ($accountCode === '') {
                continue;
            }

            $matched = false;

            if ($isGsu) {
                $matched = in_array($accountCode, self::GSU_ACCOUNT_CODES, true);
            } else {
                foreach (self::UC_ACCOUNT_PREFIXES as $prefix) {
                    if (str_starts_with($accountCode, $prefix)) {
                        $matched = true;

                        break;
                    }
                }
            }

            if (! $matched) {
                continue;
            }

            $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
            if ($voucherDate === '') {
                continue;
            }

            try {
                $vd = Carbon::parse($voucherDate);
            } catch (Throwable) {
                continue;
            }

            if ($vd->lessThan($startDate) || $vd->greaterThan($endDate)) {
                continue;
            }

            $result[] = $row;
        }

        return $result;
    }

    private function groupByAccount(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $code = (string) ($row['Account Code'] ?? '');
            $name = (string) ($row['Account Name'] ?? '');
            $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
            $description = (string) ($row['Description'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);
            $amount = $amountDb - $amountCr;

            $key = $code;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'account_code' => $code,
                    'account_name' => $name,
                    'items' => [],
                ];
            }

            $grouped[$key]['items'][] = [
                'voucher_date' => $voucherDate,
                'description' => $description,
                'amount_db' => $amountDb,
                'amount_cr' => $amountCr,
                'amount' => $amount,
            ];
        }

        return array_values($grouped);
    }

    private function sortItemsByDate(array $items): array
    {
        usort($items, static function (array $a, array $b): int {
            $dateA = strtotime((string) ($a['voucher_date'] ?? ''));
            $dateB = strtotime((string) ($b['voucher_date'] ?? ''));

            return $dateA - $dateB;
        });

        return $items;
    }
}
