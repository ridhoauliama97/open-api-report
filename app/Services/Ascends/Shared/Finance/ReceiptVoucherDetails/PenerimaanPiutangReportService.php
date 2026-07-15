<?php

namespace App\Services\Ascends\Shared\Finance\ReceiptVoucherDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class PenerimaanPiutangReportService
{
    private const TITLE = 'Laporan Penerimaan Piutang';

    private const AKUN_BANK_MAP = [
        '111.102.101' => 'BCA',
        '111.102.102' => 'BCA',
        '111.102.103' => 'MANDIRI',
        '111.102.104' => 'MANDIRI',
        '111.102.105' => 'MANDIRI',
        '111.102.106' => 'MAYBANK',
        '111.102.107' => 'MAYBANK',
        '111.102.108' => 'MANDIRI',
        '111.102.109' => 'BRI',
        '111.102.110' => 'BRI',
        '111.101.100' => 'Kas Kecil',
        '111.101.200' => 'Kas Bu Florida',
        '111.101.300' => 'Kas Dalam Perjalanan',
        '111.101.400' => 'Kas Gantung',
        '111.101.500' => 'Kas Besar',
    ];

    private const KET_BANK_MAP = [
        '111.102.101' => 'PT. GANDA SARIBU UTAMA',
        '111.102.102' => 'NETTY PASARIBU',
        '111.102.103' => 'HARYO PADMOASMOLO',
        '111.102.104' => 'PT. GANDA SARIBU UTAMA',
        '111.102.105' => 'SAMSURI',
        '111.102.106' => 'HARYO PADMOASMOLO',
        '111.102.107' => 'PT. GANDA SARIBU UTAMA',
        '111.102.108' => 'NETTY PASARIBU',
        '111.102.109' => 'NETTY PASARIBU',
        '111.102.110' => 'PT. GANDA SARIBU UTAMA',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $dateStart = trim((string) ($filters['ReceiptVoucherDate.StartDate'] ?? $filters['StartDate'] ?? ''));
        $dateEnd = trim((string) ($filters['ReceiptVoucherDate.EndDate'] ?? $filters['EndDate'] ?? ''));

        $periodLabel = $this->buildPeriodLabel($dateStart, $dateEnd, $allRows);

        $filtered = $this->applyDateFilter($allRows, $dateStart, $dateEnd);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data untuk periode yang dipilih.');
        }

        $records = $this->buildRecords($filtered);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'records' => $records,
            'printed_by' => '',
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

    private function buildRecords(array $rows): array
    {
        $records = [];

        foreach ($rows as $row) {
            $bankCode = trim((string) ($row['Bank Account Code'] ?? ''));
            $salesPerson = trim((string) ($row["Customer's Sales Person Name"] ?? ''));
            if ($salesPerson === '') {
                $salesPerson = trim((string) ($row["Invoice's Sales Person Name"] ?? ''));
            }
            $customerName = trim((string) ($row['Customer Name'] ?? ''));
            $customerCode = trim((string) ($row['Customer Code'] ?? ''));
            $itemRef = trim((string) ($row['Item Ref'] ?? ''));
            $itemDate = trim((string) ($row['Item Date'] ?? ''));
            $voucherNo = trim((string) ($row['Voucher No.'] ?? ''));
            $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
            $itemAmount = (float) ($row['Item Amount'] ?? 0);
            $amountPaidLocal = (float) ($row['Item Amount Paid (Local)'] ?? 0);
            $totalPaid = (float) ($row['Total Amount Paid (Local)'] ?? 0);
            $paymentMethod = trim((string) ($row['Payment Method'] ?? ''));
            $lamaPiutang = (int) ($row['Invoice-Allocation Date Days'] ?? 0);

            $records[] = [
                'sales_person' => $salesPerson ?: '-',
                'customer_name' => $customerName,
                'customer_code' => $customerCode,
                'item_ref' => $itemRef,
                'item_date' => $itemDate,
                'voucher_no' => $voucherNo,
                'voucher_date' => $voucherDate,
                'item_amount' => $itemAmount,
                'nilai_bayar' => $amountPaidLocal,
                'total_nilai_bayar' => $amountPaidLocal,
                'lama_piutang' => $lamaPiutang,
                'payment_method' => $paymentMethod ?: '',
                'akun_bank' => $this->getAkunBank($bankCode),
                'ket_bank' => $this->getKetBank($bankCode),
                'gab_ket' => $this->getGabKet($bankCode),
                'bank_code' => $bankCode,
            ];
        }

        usort($records, function (array $a, array $b): int {
            $cmp = strcmp($a['sales_person'], $b['sales_person']);
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcmp($a['customer_name'], $b['customer_name']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['item_date'], $b['item_date']);
        });

        return $records;
    }

    private function getAkunBank(string $code): string
    {
        return self::AKUN_BANK_MAP[$code] ?? $code;
    }

    private function getKetBank(string $code): string
    {
        return self::KET_BANK_MAP[$code] ?? '';
    }

    private function getGabKet(string $code): string
    {
        $akun = $this->getAkunBank($code);
        if (str_starts_with($code, '111.102')) {
            $ket = $this->getKetBank($code);
            if ($ket !== '') {
                return "{$akun} - {$ket}";
            }
        }

        return $akun;
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
