<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class BankAccountDailyCashReportService
{
    private const TITLE = 'Laporan Kas Harian';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);

        if ($records === []) {
            throw new RuntimeException('Tidak ada transaksi kas harian di XML.');
        }

        $sections = $this->groupSections($records);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => '',
            'printed_by' => trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? '')),
            'company' => trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? '')),
            'rows' => $sections,
            'total_rows' => count($sections),
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML tidak valid: {$sourceLabel}");
        }

        $records = [];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'table') {
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

            $bankName = trim((string) ($node->BankName ?? ''));
            if ($bankName === '') {
                continue;
            }

            $receiveDate = self::parseDate((string) ($node->ReceiveDate ?? ''));
            $paymentDate = self::parseDate((string) ($node->PaymentDate ?? ''));

            $receiveAmount = (float) ($node->ReceiveAmount ?? 0);
            $paymentAmount = (float) ($node->PaymentAmount ?? 0);

            $records[] = [
                'bank_name' => $bankName,
                'bank_account_code' => trim((string) ($node->BankAccountCode ?? '')),
                'receive_date' => $receiveDate,
                'receive_remark' => trim((string) ($node->ReceiveRemarks ?? '')),
                'receive_amount' => $receiveAmount,
                'payment_date' => $paymentDate,
                'payment_remark' => trim((string) ($node->PaymentRemarks ?? '')),
                'payment_amount' => $paymentAmount,
            ];
        }

        $reader->close();

        return $records;
    }

    private function groupSections(array $records): array
    {
        $byBank = [];

        foreach ($records as $record) {
            $bank = $record['bank_name'];

            if (! isset($byBank[$bank])) {
                $byBank[$bank] = [
                    'bank_name' => $bank,
                    'banks' => [],
                    'rows' => [],
                    'total_receive_amount' => 0.0,
                    'total_payment_amount' => 0.0,
                ];
            }

            if (! in_array($record['bank_account_code'], $byBank[$bank]['banks'], true)) {
                $byBank[$bank]['banks'][] = $record['bank_account_code'];
            }

            $byBank[$bank]['rows'][] = $record;
            $byBank[$bank]['total_receive_amount'] += $record['receive_amount'];
            $byBank[$bank]['total_payment_amount'] += $record['payment_amount'];
        }

        uksort($byBank, 'strcasecmp');

        $sections = [];
        foreach ($byBank as $group) {
            $sections[] = [
                'bank_name' => $group['bank_name'],
                'bank_account_codes' => $group['banks'],
                'rows' => array_values($group['rows']),
                'total_receive_amount' => $group['total_receive_amount'],
                'total_payment_amount' => $group['total_payment_amount'],
            ];
        }

        return $sections;
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
