<?php

namespace App\Services\Ascends\Shared\Finance\ReceiptVoucherDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class GiroCashTransferReportService
{
    private const TITLE = 'Laporan Pembayaran';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $paymentMethod = trim((string) ($filters['PaymentMethod'] ?? ''));
        $dateStart = trim((string) ($filters['ReceiptVoucherDate.StartDate'] ?? $filters['StartDate'] ?? ''));
        $dateEnd = trim((string) ($filters['ReceiptVoucherDate.EndDate'] ?? $filters['EndDate'] ?? ''));

        $periodLabel = $this->buildPeriodLabel($dateStart, $dateEnd, $allRows);

        $title = self::TITLE.' '.$paymentMethod;

        $filteredByDate = $this->applyDateFilter($allRows, $dateStart, $dateEnd);

        $filteredByMethod = $this->applyPaymentMethodFilter($filteredByDate, $paymentMethod);

        if ($filteredByMethod === []) {
            return [
                'title' => $title,
                'company' => '',
                'period_label' => $periodLabel,
                'records' => [],
                'printed_by' => '',
                'payment_method' => $paymentMethod,
            ];
        }

        $records = $this->buildRecords($filteredByMethod, $paymentMethod);

        return [
            'title' => $title,
            'company' => '',
            'period_label' => $periodLabel,
            'records' => $records,
            'printed_by' => '',
            'payment_method' => $paymentMethod,
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
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'Sukamu') {
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

            if (($row['Customer Name'] ?? '') !== '') {
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
        $key = str_replace('_x0027_', "'", $key);
        $key = str_replace('_x002F_', '/', $key);
        $key = str_replace('_x0023_', '#', $key);
        $key = str_replace('_x002D_', '-', $key);

        return $key;
    }

    private function applyDateFilter(array $rows, string $dateStart, string $dateEnd): array
    {
        if ($dateStart === '' && $dateEnd === '') {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) use ($dateStart, $dateEnd): bool {
            $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
            if ($voucherDate === '') {
                return true;
            }

            try {
                $voucherCarbon = Carbon::parse($voucherDate);
            } catch (\Throwable) {
                return true;
            }

            $startCarbon = $this->parseDateSafe($dateStart);
            $endCarbon = $this->parseDateSafe($dateEnd);

            if ($startCarbon !== null && $voucherCarbon->lt($startCarbon->startOfDay())) {
                return false;
            }

            if ($endCarbon !== null && $voucherCarbon->gt($endCarbon->endOfDay())) {
                return false;
            }

            return true;
        }));
    }

    private function applyPaymentMethodFilter(array $rows, string $paymentMethod): array
    {
        $pm = match (strtolower($paymentMethod)) {
            'cash' => 'Cash',
            'giro' => 'Check',
            'transfer' => 'Transfer',
            default => '',
        };

        if ($pm === '') {
            throw new RuntimeException("Metode pembayaran tidak valid: {$paymentMethod}");
        }

        return array_values(array_filter($rows, function (array $row) use ($pm): bool {
            $rowPm = trim((string) ($row['Payment Method'] ?? ''));

            return $rowPm === $pm;
        }));
    }

    private function buildRecords(array $rows, string $paymentMethod): array
    {
        $vouchers = [];
        foreach ($rows as $row) {
            $vno = trim((string) ($row['Voucher No.'] ?? ''));
            if ($vno === '') {
                continue;
            }
            $vouchers[$vno][] = $row;
        }

        $rawRecords = [];
        foreach ($vouchers as $vno => $group) {
            $first = $group[0];

            $salesPerson = trim((string) ($first["Customer's Sales Person Name"] ?? ''));
            if ($salesPerson === '') {
                $salesPerson = trim((string) ($first["Invoice's Sales Person Name"] ?? ''));
            }

            usort($group, function (array $a, array $b): int {
                $typeA = trim((string) ($a['Item Type'] ?? ''));
                $typeB = trim((string) ($b['Item Type'] ?? ''));
                $sortA = str_contains($typeA, 'Return') ? 'A' : 'B';
                $sortB = str_contains($typeB, 'Return') ? 'A' : 'B';
                if ($sortA !== $sortB) {
                    return strcmp($sortA, $sortB);
                }

                return strcmp($a['Item Ref'] ?? '', $b['Item Ref'] ?? '');
            });

            $last = $group[array_key_last($group)];
            $customerName = trim((string) ($last['Customer Name'] ?? ''));
            $itemDate = trim((string) ($last['Item Date'] ?? ''));

            $isGiro = strtolower($paymentMethod) === 'giro';
            $voucherDateRaw = trim((string) ($first['Voucher Date'] ?? ''));
            $checkDueRaw = trim((string) ($first['Check Due Date'] ?? ''));

            $tglVoucher = $isGiro && $checkDueRaw !== ''
                ? $checkDueRaw
                : $voucherDateRaw;

            $total = (float) ($first['Total Amount Paid (Local)'] ?? 0);

            $hari = 0;
            try {
                $itemCarbon = Carbon::parse($itemDate);
                $voucherCarbon = Carbon::parse($tglVoucher);
                $hari = (int) $itemCarbon->startOfDay()->diffInDays($voucherCarbon->startOfDay(), false);
            } catch (\Throwable) {
                $hari = 0;
            }

            $groupKey = $salesPerson !== '' && $salesPerson !== '-'
                ? explode(' ', $salesPerson)[0]
                : '-';

            $rawRecords[] = [
                'group_key' => $groupKey,
                'sales_person' => $salesPerson ?: '-',
                'customer_name' => $customerName,
                'voucher_no' => $vno,
                'item_date' => $itemDate,
                'voucher_date' => $tglVoucher,
                'hari' => $hari,
                'total' => $total,
            ];
        }

        usort($rawRecords, function (array $a, array $b): int {
            $cmp = strcmp($a['group_key'], $b['group_key']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['voucher_no'], $b['voucher_no']);
        });

        return $rawRecords;
    }

    private function buildPeriodLabel(string $dateStart, string $dateEnd, array $allRows): string
    {
        if ($dateStart !== '' && $dateEnd !== '') {
            return "Dari {$this->formatDateSafe($dateStart)} s/d {$this->formatDateSafe($dateEnd)}";
        }

        $dates = [];
        foreach ($allRows as $row) {
            $raw = trim((string) ($row['Voucher Date'] ?? ''));
            if ($raw === '') {
                continue;
            }
            $c = $this->parseDateSafe($raw);
            if ($c !== null) {
                $dates[] = $c;
            }
        }

        if ($dates === []) {
            return '';
        }

        $min = min($dates);
        $max = max($dates);

        return "Dari {$min->locale('id')->isoFormat('DD-MMM-YY')} s/d {$max->locale('id')->isoFormat('DD-MMM-YY')}";
    }

    private function parseDateSafe(string $date): ?Carbon
    {
        if ($date === '') {
            return null;
        }
        try {
            return Carbon::createFromFormat('d/m/Y', $date);
        } catch (\Throwable) {
            try {
                return Carbon::parse($date);
            } catch (\Throwable) {
                return null;
            }
        }
    }

    private function formatDateSafe(string $date): string
    {
        if ($date === '') {
            return '';
        }
        $c = $this->parseDateSafe($date);
        if ($c !== null) {
            return $c->locale('id')->isoFormat('DD-MMM-YY');
        }

        return $date;
    }
}
