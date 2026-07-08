<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PendapatanDanBiayaLainReportService
{
    private const TITLE = 'Laporan Pendapatan Dan Biaya Lain-Lain';

    private const RU_ACCOUNT_CODES = [
        '800.000.001', '800.000.002', '800.000.003',
        '900.000.001', '900.000.002', '900.000.006', '900.000.999',
    ];

    private const NON_RU_ACCOUNT_CODES = [
        '721.000.251', '721.000.254', '721.000.260', '721.000.261',
        '721.000.257', '721.000.258', '721.000.259', '721.000.265',
        '721.000.266', '721.000.267', '721.000.268', '721.000.269',
        '721.000.270',
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

        $groups = $this->groupByAccountName($computed);

        $grandTotalDb = array_sum(array_map(static fn (array $g): float => $g['subtotal_db'], $groups));
        $grandTotalCr = array_sum(array_map(static fn (array $g): float => $g['subtotal_cr'], $groups));
        $grandTotal = array_sum(array_map(static fn (array $g): float => $g['subtotal'], $groups));

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
            if ($this->isCodeInRange($accountCode, '800.000.000', '900.000.999')) {
                return true;
            }

            if (in_array($accountCode, self::NON_RU_ACCOUNT_CODES, true)) {
                return true;
            }

            return false;
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
            $accountName = (string) ($row['Account Name'] ?? '');
            $description = (string) ($row['Description'] ?? '');
            $rawAmountDb = (float) ($row['Amount DB'] ?? 0);
            $rawAmountCr = (float) ($row['Amount CR'] ?? 0);

            $amountDb = $this->computeAmountDb($accountName, $rawAmountDb);
            $amountCr = $this->computeAmountCr($accountName, $rawAmountCr);
            $amount = $this->computeAmount($accountName, $amountDb, $amountCr);

            $result[] = [
                'account_name' => $accountName,
                'description' => $description,
                'amount_db' => $amountDb,
                'amount_cr' => $amountCr,
                'amount' => $amount,
            ];
        }

        return $result;
    }

    private function computeAmountDb(string $accountName, float $rawAmountDb): float
    {
        if (str_contains($accountName, 'Beban') || str_contains($accountName, 'Rugi Sel')) {
            return $rawAmountDb;
        }

        if (str_contains($accountName, 'Pendapatan') || str_contains($accountName, 'Laba Se')) {
            return $rawAmountDb;
        }

        return $rawAmountDb * -1;
    }

    private function computeAmountCr(string $accountName, float $rawAmountCr): float
    {
        if (
            str_contains($accountName, 'Beban') ||
            str_contains($accountName, 'Rugi') ||
            str_contains($accountName, 'Pendapatan') ||
            str_contains($accountName, 'Laba S')
        ) {
            return $rawAmountCr;
        }

        return $rawAmountCr * -1;
    }

    private function computeAmount(string $accountName, float $amountDb, float $amountCr): float
    {
        if (str_contains($accountName, 'Beban') && abs($amountDb) < 0.001) {
            return $amountCr;
        }

        if (str_contains($accountName, 'Beban') && abs($amountDb) >= 0.001) {
            return $amountDb * -1;
        }

        if (str_contains($accountName, 'Rugi S') && abs($amountDb) < 0.001) {
            return $amountCr;
        }

        if (str_contains($accountName, 'Rugi S') && abs($amountDb) >= 0.001) {
            return $amountDb * -1;
        }

        if (str_contains($accountName, 'Pendapatan') && $amountCr >= 0.001) {
            return $amountCr;
        }

        if (str_contains($accountName, 'Pendapatan') && $amountDb >= 0.001) {
            return $amountDb * -1;
        }

        if (str_contains($accountName, 'Laba Se') && $amountCr >= 0.001) {
            return $amountCr;
        }

        if (str_contains($accountName, 'Laba Se') && $amountDb >= 0.001) {
            return $amountDb * -1;
        }

        return $amountDb + $amountCr;
    }

    private function groupByAccountName(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $accountName = $row['account_name'];

            if (! isset($grouped[$accountName])) {
                $grouped[$accountName] = [
                    'account_name' => $accountName,
                    'items' => [],
                    'subtotal_db' => 0.0,
                    'subtotal_cr' => 0.0,
                    'subtotal' => 0.0,
                ];
            }

            $grouped[$accountName]['items'][] = $row;
            $grouped[$accountName]['subtotal_db'] += $row['amount_db'];
            $grouped[$accountName]['subtotal_cr'] += $row['amount_cr'];
            $grouped[$accountName]['subtotal'] += $row['amount'];
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
