<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PendapatanDanBiayaLainBaruReportService
{
    private const TITLE = 'Laporan Pendapatan Dan Biaya Lain-Lain';

    private const RU_ACCOUNT_CODES = [
        '800.000.001', '800.000.002', '800.000.003',
        '900.000.001', '900.000.002', '900.000.006', '900.000.999',
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

        $computed = $this->computeRows($filtered);

        $grouped = $this->groupByCodeLft($computed);

        $grandTotalDb = 0.0;
        $grandTotalCr = 0.0;
        $grandTotal = 0.0;
        foreach ($grouped as $prefixGroup) {
            foreach ($prefixGroup['accounts'] as $account) {
                $grandTotalDb += $account['subtotal_db'];
                $grandTotalCr += $account['subtotal_cr'];
                $grandTotal += $account['subtotal'];
            }
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
            'groups' => $grouped,
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
            $upperCompany = strtoupper($company);

            if (! $this->tampilData($accountCode, $upperCompany)) {
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

    private function tampilData(string $accountCode, string $company): bool
    {
        if ($company !== 'RU') {
            return $this->isCodeInRange($accountCode, '800.000.000', '900.000.999');
        }

        return in_array($accountCode, self::RU_ACCOUNT_CODES, true);
    }

    private function isCodeInRange(string $code, string $start, string $end): bool
    {
        return $code >= $start && $code <= $end;
    }

    private function computeRows(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'account_code' => (string) ($row['Account Code'] ?? ''),
                'account_name' => (string) ($row['Account Name'] ?? ''),
                'description' => (string) ($row['Description'] ?? ''),
                'voucher_date' => (string) ($row['Voucher Date'] ?? ''),
                'amount_db' => (float) ($row['Amount DB'] ?? 0),
                'amount_cr' => (float) ($row['Amount CR'] ?? 0),
            ];
        }

        return $result;
    }

    private function groupByCodeLft(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $accountCode = $row['account_code'];
            $codeLftPrefix = str_starts_with($accountCode, '800') ? 'B' : 'A';

            if (! isset($grouped[$codeLftPrefix])) {
                $grouped[$codeLftPrefix] = [
                    'prefix' => $codeLftPrefix,
                    'accounts' => [],
                ];
            }

            $accountKey = $accountCode;
            if (! isset($grouped[$codeLftPrefix]['accounts'][$accountKey])) {
                $grouped[$codeLftPrefix]['accounts'][$accountKey] = [
                    'account_code' => $accountCode,
                    'account_name' => $row['account_name'],
                    'items' => [],
                    'subtotal_db' => 0.0,
                    'subtotal_cr' => 0.0,
                    'subtotal' => 0.0,
                ];
            }

            $saldo = $row['amount_db'] - $row['amount_cr'];

            $grouped[$codeLftPrefix]['accounts'][$accountKey]['items'][] = [
                'voucher_date' => $row['voucher_date'],
                'description' => $row['description'],
                'amount_db' => $row['amount_db'],
                'amount_cr' => $row['amount_cr'],
                'saldo' => $saldo,
            ];

            $grouped[$codeLftPrefix]['accounts'][$accountKey]['subtotal_db'] += $row['amount_db'];
            $grouped[$codeLftPrefix]['accounts'][$accountKey]['subtotal_cr'] += $row['amount_cr'];
            $grouped[$codeLftPrefix]['accounts'][$accountKey]['subtotal'] += $saldo;
        }

        ksort($grouped);

        foreach ($grouped as &$prefixGroup) {
            ksort($prefixGroup['accounts']);
            $prefixGroup['accounts'] = array_values($prefixGroup['accounts']);
        }

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
