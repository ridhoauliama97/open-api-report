<?php

namespace App\Services\Ascends\Shared\Finance\ReceiptVoucherDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class PelunasanReportService
{
    private const TITLE = 'Laporan Pelunasan';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $dateStart = trim((string) ($filters['ItemDate.StartDate'] ?? $filters['StartDate'] ?? ''));
        $dateEnd = trim((string) ($filters['ItemDate.EndDate'] ?? $filters['EndDate'] ?? ''));

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
        $arSalesRows = array_values(array_filter($rows, function (array $row): bool {
            return ($row['Item Type'] ?? '') === 'AR Sales';
        }));

        $groups = [];
        foreach ($arSalesRows as $row) {
            $ref = trim((string) ($row['Item Ref'] ?? ''));
            if ($ref === '') {
                continue;
            }
            $groups[$ref][] = $row;
        }

        $records = [];
        foreach ($groups as $ref => $group) {
            $first = $group[0];

            $customerName = trim((string) ($first['Customer Name'] ?? ''));
            $itemDate = trim((string) ($first['Item Date'] ?? ''));
            $itemAmount = (float) ($first['Item Amount'] ?? 0);

            $maxVoucherDate = '';
            $voucherDates = [];
            $sumGab = 0.0;

            foreach ($group as $row) {
                $vDate = trim((string) ($row['Voucher Date'] ?? ''));
                if ($vDate !== '') {
                    $voucherDates[] = $vDate;
                }

                $amtPaid = (float) ($row['Item Amount Paid'] ?? 0);
                $debitNote = (float) ($row['Item Debit Note'] ?? 0);
                $creditNote = (float) ($row['Item Credit Note'] ?? 0);
                $gab = $amtPaid - $debitNote + $creditNote;
                $sumGab += $gab;
            }

            if ($voucherDates !== []) {
                usort($voucherDates, function (string $a, string $b): int {
                    return strcmp($a, $b);
                });
                $maxVoucherDate = end($voucherDates);
            }

            $hari = 0;
            try {
                $itemCarbon = Carbon::parse($itemDate);
                $voucherCarbon = Carbon::parse($maxVoucherDate ?: $itemDate);
                $hari = (int) $itemCarbon->startOfDay()->diffInDays($voucherCarbon->startOfDay(), false);
            } catch (\Throwable) {
                $hari = 0;
            }

            if ($hari >= 0 && $hari <= 44) {
                $ketHari = '0 - 45';
            } elseif ($hari >= 45 && $hari <= 60) {
                $ketHari = '46 - 60';
            } elseif ($hari >= 61 && $hari <= 90) {
                $ketHari = '61 - 90';
            } elseif ($hari >= 91 && $hari <= 120) {
                $ketHari = '91 - 120';
            } else {
                $ketHari = 'Di Atas 120';
            }

            $status = abs($itemAmount - $sumGab) < 0.01 ? 'Lunas' : 'Belum Lunas';

            $records[] = [
                'customer_name' => $customerName,
                'item_ref' => $ref,
                'item_date' => $itemDate,
                'voucher_date' => $maxVoucherDate,
                'line_total' => $itemAmount,
                'total_voucher' => $sumGab,
                'age' => $hari,
                'ket_hari' => $ketHari,
                'status' => $status,
            ];
        }

        usort($records, function (array $a, array $b): int {
            $cmp = strcmp($a['customer_name'], $b['customer_name']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['item_date'], $b['item_date']);
        });

        return $records;
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
