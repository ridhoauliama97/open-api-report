<?php

namespace App\Services\Ascends\Shared\Finance\ReceiptVoucherDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class PenerimaanVoucherReportService
{
    private const TITLE = 'Laporan Penerimaan Voucher (Intensif Penagihan)';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $dateStart = trim((string) ($filters['ReceiptVoucherDate.StartDate'] ?? $filters['StartDate'] ?? ''));
        $dateEnd = trim((string) ($filters['ReceiptVoucherDate.EndDate'] ?? $filters['EndDate'] ?? ''));

        $periodLabel = $this->buildPeriodLabel($dateStart, $dateEnd, $allRows);

        $filtered = $this->applySelectionFormula($allRows, $dateStart, $dateEnd);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data untuk periode yang dipilih.');
        }

        $collectors = $this->groupByCollector($filtered);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'collectors' => $collectors,
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

    private function applySelectionFormula(array $rows, string $dateStart, string $dateEnd): array
    {
        return array_values(array_filter($rows, function (array $row) use ($dateStart, $dateEnd): bool {
            $collectorName = trim((string) ($row['Collector Name'] ?? ''));
            if ($collectorName === '') {
                return false;
            }

            if ($dateStart === '' && $dateEnd === '') {
                return true;
            }

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

    private function groupByCollector(array $rows): array
    {
        $collectorGroups = [];
        $collectorOrder = [];

        foreach ($rows as $row) {
            $collectorName = trim((string) ($row['Collector Name'] ?? ''));
            if ($collectorName === '') {
                $collectorName = 'TANPA COLLECTOR';
            }

            if (! isset($collectorGroups[$collectorName])) {
                $collectorGroups[$collectorName] = [
                    'collector_name' => $collectorName,
                    'customer_groups' => [],
                ];
                $collectorOrder[] = $collectorName;
            }

            $collectorGroups[$collectorName]['customer_groups'][] = $row;
        }

        $result = [];
        foreach ($collectorOrder as $name) {
            $result[] = [
                'collector_name' => $name,
                'customer_groups' => $this->groupByCustomer($collectorGroups[$name]['customer_groups']),
            ];
        }

        return $result;
    }

    private function groupByCustomer(array $rows): array
    {
        $customerGroups = [];
        $customerOrder = [];

        foreach ($rows as $row) {
            $customerName = trim((string) ($row['Customer Name'] ?? ''));
            if ($customerName === '') {
                $customerName = 'TANPA NAMA';
            }

            if (! isset($customerGroups[$customerName])) {
                $customerGroups[$customerName] = [
                    'customer_name' => $customerName,
                    'invoice_groups' => [],
                ];
                $customerOrder[] = $customerName;
            }

            $customerGroups[$customerName]['invoice_groups'][] = $row;
        }

        $result = [];
        foreach ($customerOrder as $name) {
            $result[] = [
                'customer_name' => $name,
                'invoice_groups' => $this->groupByInvoice($customerGroups[$name]['invoice_groups']),
            ];
        }

        return $result;
    }

    private function groupByInvoice(array $rows): array
    {
        $invoiceGroups = [];
        $invoiceOrder = [];

        foreach ($rows as $row) {
            $itemRef = trim((string) ($row['Item Ref'] ?? ''));
            if ($itemRef === '') {
                $itemRef = '__NO_REF__';
            }

            if (! isset($invoiceGroups[$itemRef])) {
                $itemAmount = (float) ($row['Item Amount'] ?? 0);
                $itemDate = trim((string) ($row['Item Date'] ?? ''));

                $invoiceGroups[$itemRef] = [
                    'item_ref' => $itemRef === '__NO_REF__' ? '' : $itemRef,
                    'item_date' => $itemDate,
                    'item_amount' => $itemAmount,
                    '_allocation_dates' => [],
                    'vouchers' => [],
                    'total_payment' => 0.0,
                ];
                $invoiceOrder[] = $itemRef;
            }

            $paymentAmount = (float) ($row['Item Effective Amount Paid (Local)'] ?? 0);
            $voucherNo = trim((string) ($row['Voucher No.'] ?? ''));
            $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
            $allocationDate = trim((string) ($row['Allocation Date'] ?? $row['Settle Date'] ?? $voucherDate));

            $invoiceGroups[$itemRef]['vouchers'][] = [
                'voucher_no' => $voucherNo,
                'voucher_date' => $voucherDate,
                'allocation_date' => $allocationDate,
                'payment' => $paymentAmount,
            ];

            $invoiceGroups[$itemRef]['total_payment'] += $paymentAmount;
            $invoiceGroups[$itemRef]['_allocation_dates'][] = $allocationDate;
        }

        $result = [];
        foreach ($invoiceOrder as $ref) {
            $inv = $invoiceGroups[$ref];
            $inv['total_payment'] = round($inv['total_payment'], 2);
            $inv['sisa'] = round($inv['item_amount'] - $inv['total_payment'], 2);

            $maxCarbon = null;
            foreach ($inv['_allocation_dates'] as $dateStr) {
                if ($dateStr === '') {
                    continue;
                }
                try {
                    $c = Carbon::parse($dateStr);
                    if ($maxCarbon === null || $c->gt($maxCarbon)) {
                        $maxCarbon = $c;
                    }
                } catch (\Throwable) {
                }
            }

            $bedaHariGlobal = 0;
            if ($maxCarbon !== null && $inv['item_date'] !== '') {
                try {
                    $itemCarbon = Carbon::parse($inv['item_date']);
                    $bedaHariGlobal = (int) $maxCarbon->startOfDay()->diffInDays($itemCarbon->startOfDay());
                } catch (\Throwable) {
                }
            }

            foreach ($inv['vouchers'] as &$voucher) {
                $voucher['beda_hari'] = $bedaHariGlobal;
            }

            unset($inv['_allocation_dates']);

            $result[] = $inv;
        }

        usort($result, function (array $a, array $b): int {
            return strcmp($a['item_date'], $b['item_date']);
        });

        return $result;
    }

    private function buildPeriodLabel(string $dateStart, string $dateEnd, array $allRows): string
    {
        if ($dateStart !== '' && $dateEnd !== '') {
            $startLabel = $this->formatDateSafe($dateStart);
            $endLabel = $this->formatDateSafe($dateEnd);

            return "Dari {$startLabel} s/d {$endLabel}";
        }

        $dates = [];
        foreach ($allRows as $row) {
            $raw = trim((string) ($row['Item Date'] ?? ''));
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
