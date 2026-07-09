<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LabaKotorRuReportService
{
    private const TITLE = 'Laporan Laba Kotor';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data jurnal tidak ditemukan pada XML.');
        }

        $startDate = trim((string) ($filters['Date.StartDate'] ?? $filters['Date_StartDate'] ?? $filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['Date.EndDate'] ?? $filters['Date_EndDate'] ?? $filters['EndDate'] ?? $filters['end_date'] ?? ''));

        $filtered = $this->applyFilters($allRows, $startDate, $endDate);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $months = $this->resolveMonths($startDate, $endDate);

        $groups = $this->aggregateGroups($filtered, $months);

        if ($groups === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $groups = $this->computeGroupMargins($groups, $months);

        $totals = $this->computeTotals($groups, $months);

        $hppGlobal = $this->computeHppGlobal($totals, $months);

        $period = $this->resolvePeriod($startDate, $endDate);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $period['label'],
            'start_date' => $period['start'],
            'end_date' => $period['end'],
            'months' => $months,
            'groups' => $groups,
            'total_monthly_sales' => $totals['sales'],
            'total_monthly_hpp' => $totals['hpp'],
            'hpp_global' => $hppGlobal,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
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
            $accountName = (string) ($row['Account Name'] ?? '');
            $description = (string) ($row['Description'] ?? '');
            $voucherRef = (string) ($row['Voucher Ref'] ?? '');

            $adjResult = $this->applyAdj($accountCode, $voucherRef, $description);
            if ($adjResult !== 'tampil') {
                continue;
            }

            $groupName2 = $this->applyGroupName2($accountCode, $accountName, $description);
            if ($groupName2 === '') {
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

            $row['_groupName2'] = $groupName2;
            $row['_assembly'] = $this->applyAssembly($description);
            $result[] = $row;
        }

        return $result;
    }

    private function applyAdj(string $accountCode, string $voucherRef, string $description): string
    {
        if (str_contains($voucherRef, 'ADJ/') || str_contains($voucherRef, 'JP/AP')) {
            return 'ADJ';
        }

        if (str_contains($description, 'ADJUSMENT:') || str_contains($description, 'ADJUSTMENT:')) {
            return 'ADJ';
        }

        if ($accountCode === '111.400.304') {
            return 'ADJ';
        }

        return 'tampil';
    }

    private function applyGroupName2(string $accountCode, string $accountName, string $description): string
    {
        $nameUpper = strtoupper($accountName);
        $descUpper = strtoupper($description);

        if (str_contains($nameUpper, 'JABON')) {
            return 'JABON BJ';
        }

        if (str_contains($nameUpper, 'PULAI')) {
            return 'PULAI BJ';
        }

        if (str_contains($nameUpper, 'RAMBUNG')) {
            return 'RAMBUNG BJ';
        }

        if (str_contains($nameUpper, 'DADAP')) {
            return 'DADAP BJ';
        }

        if (str_contains($nameUpper, 'ABU SEKAM') || str_contains($nameUpper, 'KAYU SEMPENGAN')) {
            return 'KAYU SEMPENGAN';
        }

        if (str_contains($nameUpper, 'RETUR PENJ. KAYU LAT')) {
            return 'RETUR KAYU LAT';
        }

        if (str_contains($nameUpper, 'KAYU LAT')) {
            return 'KAYU LAT';
        }

        if (str_contains($nameUpper, 'SAWN TIMBER')) {
            return 'KAYU LAT';
        }

        if ($accountCode === '111.400.202' && str_contains($descUpper, 'SALES')) {
            return 'KAYU LAT';
        }

        if (str_contains($nameUpper, 'POTONGAN PEMBELIAN')) {
            return 'POTONGAN';
        }

        if (str_contains($nameUpper, 'LEM')) {
            return 'LEM';
        }

        if (str_contains($nameUpper, 'HARGA POKOK') && str_contains($description, 'SR-')) {
            return 'RETUR KAYU LAT';
        }

        $sparepartCodes = ['111.400.903', '111.400.904', '111.400.906', '111.400.905', '111.400.910'];
        if (in_array($accountCode, $sparepartCodes, true) && str_starts_with($descUpper, 'SALES')) {
            return 'HPP PENJ. SPAREPART';
        }

        if ($accountCode === '111.400.101' && str_contains($descUpper, 'SALES')) {
            return 'KAYU BULAT';
        }

        if (str_contains($descUpper, 'SALES 3.1.6.16.0071')) {
            return 'STRAPPING BAND';
        }

        return '';
    }

    private function applyAssembly(string $description): int
    {
        $descUpper = strtoupper($description);

        if (str_contains($descUpper, 'ASSEMBLY')) {
            return 1;
        }

        if (str_contains($descUpper, 'IU:')) {
            return 1;
        }

        return 2;
    }

    private function resolveMonths(string $startDate, string $endDate): array
    {
        $months = [];

        try {
            $start = Carbon::parse($startDate)->startOfMonth();
            $end = Carbon::parse($endDate)->startOfMonth();

            $current = $start->copy();
            while ($current->lessThanOrEqualTo($end)) {
                $key = $current->format('Y-m');
                $label = $current->locale('id')->isoFormat('MMM-YY');
                $months[$key] = $label;
                $current->addMonth();
            }
        } catch (Throwable) {
        }

        return $months;
    }

    private function aggregateGroups(array $rows, array $months): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $groupName2 = (string) ($row['_groupName2'] ?? '');
            $assembly = (int) ($row['_assembly'] ?? 2);
            $accountCode = (string) ($row['Account Code'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $voucherDate = (string) ($row['Voucher Date'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            $monthKey = '';
            if ($voucherDate !== '') {
                try {
                    $monthKey = Carbon::parse($voucherDate)->format('Y-m');
                } catch (Throwable) {
                    continue;
                }
            }

            if (! isset($months[$monthKey])) {
                continue;
            }

            if (! isset($groups[$groupName2])) {
                $groups[$groupName2] = [
                    'name' => $groupName2,
                    'penjualan_items' => [],
                    'monthly_sales' => [],
                    'monthly_hpp' => [],
                ];
                foreach ($months as $mk => $ml) {
                    $groups[$groupName2]['monthly_sales'][$mk] = 0.0;
                    $groups[$groupName2]['monthly_hpp'][$mk] = 0.0;
                }
            }

            if ($assembly === 2) {
                if ($this->isSalesAccount($accountCode) && $amountCr > 0) {
                    $groups[$groupName2]['monthly_sales'][$monthKey] += $amountCr;

                    $itemKey = $accountCode.'|||'.$accountName;
                    if (! isset($groups[$groupName2]['penjualan_items'][$itemKey])) {
                        $groups[$groupName2]['penjualan_items'][$itemKey] = [
                            'account_code' => $accountCode,
                            'account_name' => $accountName,
                            'monthly_amounts' => [],
                        ];
                        foreach ($months as $mk => $ml) {
                            $groups[$groupName2]['penjualan_items'][$itemKey]['monthly_amounts'][$mk] = 0.0;
                        }
                    }
                    $groups[$groupName2]['penjualan_items'][$itemKey]['monthly_amounts'][$monthKey] += $amountCr;
                } elseif ($this->isHppAccount($accountCode) && $amountCr > 0) {
                    $groups[$groupName2]['monthly_hpp'][$monthKey] += $amountCr;
                }
            }
        }

        return $groups;
    }

    private function isSalesAccount(string $accountCode): bool
    {
        return str_starts_with($accountCode, '411.') || str_starts_with($accountCode, '412.');
    }

    private function isHppAccount(string $accountCode): bool
    {
        return str_starts_with($accountCode, '111.400.');
    }

    private function computeGroupMargins(array $groups, array $months): array
    {
        foreach ($groups as &$group) {
            $group['monthly_margin'] = [];
            foreach ($months as $mk => $ml) {
                $sales = $group['monthly_sales'][$mk] ?? 0;
                $hpp = $group['monthly_hpp'][$mk] ?? 0;

                if ($sales != 0) {
                    $group['monthly_margin'][$mk] = round(($sales - $hpp) / $sales * 100, 2);
                } else {
                    $group['monthly_margin'][$mk] = 0.0;
                }
            }

            $margins = array_values($group['monthly_margin']);
            $group['rata_rata'] = count($margins) > 0 ? round(array_sum($margins) / count($margins), 2) : 0.0;
            $group['terendah'] = count($margins) > 0 ? round(min($margins), 2) : 0.0;
            $group['tertinggi'] = count($margins) > 0 ? round(max($margins), 2) : 0.0;

            $group['penjualan_items'] = array_values($group['penjualan_items']);
            foreach ($group['penjualan_items'] as &$item) {
                $item['total_amount'] = array_sum($item['monthly_amounts']);
            }
        }

        return $groups;
    }

    private function computeTotals(array $groups, array $months): array
    {
        $totalSales = [];
        $totalHpp = [];

        foreach ($months as $mk => $ml) {
            $totalSales[$mk] = 0.0;
            $totalHpp[$mk] = 0.0;
        }

        foreach ($groups as $group) {
            foreach ($months as $mk => $ml) {
                $totalSales[$mk] += $group['monthly_sales'][$mk] ?? 0;
                $totalHpp[$mk] += $group['monthly_hpp'][$mk] ?? 0;
            }
        }

        return [
            'sales' => $totalSales,
            'hpp' => $totalHpp,
        ];
    }

    private function computeHppGlobal(array $totals, array $months): array
    {
        $hppGlobal = [];

        foreach ($months as $mk => $ml) {
            $sales = $totals['sales'][$mk] ?? 0;
            $hpp = $totals['hpp'][$mk] ?? 0;

            if ($sales != 0) {
                $hppGlobal[$mk] = round($hpp / $sales * 100, 2);
            } else {
                $hppGlobal[$mk] = 0.0;
            }
        }

        return $hppGlobal;
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
