<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LabaKotorRu12BulanReportService
{
    private const TITLE = 'Laporan Laba Kotor (Periode 12 Bulan/Tahunan)';

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
            $voucherNumber = (string) ($row['Voucher Number'] ?? '');

            $adj = $this->applyAdj($accountCode, $voucherRef, $description);
            if ($adj !== 'tampil') {
                continue;
            }

            $nameGroupKayu = $this->applyNameGroupKayu($accountCode, $accountName, $description, $voucherNumber);
            if ($nameGroupKayu === 'KOSONG' || str_starts_with($nameGroupKayu, 'KOSONG')) {
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

            $row['_nameGroupKayu'] = $nameGroupKayu;
            $row['_groupName'] = $this->applyGroupName($accountName);
            $row['_nameGroup'] = $this->applyNameGroup($row['_groupName']);
            $row['_noUrut'] = $this->applyNoUrut($accountName);
            $row['_penjualan'] = $this->applyPenjualan($accountCode);
            $row['_assembly'] = $this->applyAssembly($accountCode, $description);

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

    private function applyAssembly(string $accountCode, string $description): int
    {
        $descUpper = strtoupper($description);

        if (str_contains($descUpper, 'ASSEMBLY')) {
            return 1;
        }

        if (str_contains($descUpper, 'IU:')) {
            $prefix9 = substr($accountCode, 0, 9);
            if (in_array($prefix9, ['111.400.1', '111.400.2', '111.400.3', '111.400.9'], true)) {
                return 1;
            }
        }

        return 2;
    }

    private function applyGroupName(string $accountName): string
    {
        $nameUpper = strtoupper($accountName);

        if (str_contains($nameUpper, 'HOUSEWARE')) {
            return 'A';
        }
        if (str_contains($nameUpper, 'FURNITURE')) {
            return 'B';
        }
        if (str_contains($nameUpper, 'ENAMEL')) {
            return 'C';
        }
        if (str_contains($nameUpper, 'STAINLESS')) {
            return 'D';
        }
        if (str_contains($nameUpper, 'SAPU')) {
            return 'E';
        }
        if (str_contains($nameUpper, 'LEMARI')) {
            return 'F';
        }

        return ' ';
    }

    private function applyNameGroup(string $groupName): string
    {
        return match ($groupName) {
            'A' => 'PL HOUSEWARE',
            'B' => 'PL FURNITURE',
            'C' => 'ENAMEL',
            'D' => 'STAINLESS',
            'E' => 'SAPU',
            'F' => 'LEMARI',
            default => 'O',
        };
    }

    private function applyNameGroupKayu(string $accountCode, string $accountName, string $description, string $voucherNumber): string
    {
        $nameUpper = strtoupper($accountName);
        $descUpper = strtoupper($description);

        if (str_contains($nameUpper, 'JABON')) {
            return '01JABON BJ';
        }
        if (str_contains($nameUpper, 'RAMBUNG')) {
            return '02RAMBUNG BJ';
        }
        if (str_contains($nameUpper, 'PULAI')) {
            return '03PULAI BJ';
        }
        if (str_contains($nameUpper, 'DADAP')) {
            return '03DADAP BJ';
        }

        if (str_contains($nameUpper, 'RETUR PENJ. KAYU LAT')) {
            return '05RETUR KAYU LAT';
        }

        if (str_contains($nameUpper, 'HARGA POKOK') && str_contains($voucherNumber, 'SR-')) {
            return '05RETUR KAYU LAT';
        }

        if (str_contains($nameUpper, 'KAYU LAT')) {
            return '04KAYU LAT';
        }
        if (str_contains($nameUpper, 'SAWN TIMBER')) {
            return '04KAYU LAT';
        }

        if ($accountCode === '111.400.202' && str_contains($descUpper, 'SALES')) {
            return '04KAYU LAT';
        }

        if (str_contains($nameUpper, 'ABU SEKAM') || str_contains($nameUpper, 'KAYU SEMPENGAN')) {
            return '06KAYU SEMPENGAN';
        }

        if ($accountCode === '111.400.101' && str_contains($descUpper, 'SALES')) {
            return '07KAYU BULAT';
        }

        $sparepartCodes = ['111.400.903', '111.400.904', '111.400.906', '111.400.905', '111.400.910', '111.400.911'];
        if (in_array($accountCode, $sparepartCodes, true) && str_contains($descUpper, 'SALES')) {
            return '20HPP PENJ. SPAREPART';
        }

        if ($accountCode === '111.400.404' && str_contains($descUpper, 'SALES')) {
            return '21LEM';
        }

        if (str_contains($nameUpper, 'POTONGAN PEMBELIAN')) {
            return '22POTONGAN';
        }

        if (str_contains($descUpper, 'SALES 3.1.6.16.0071: TAL') && $accountCode === '111.400.407') {
            return '23sTRAPT';
        }

        $prefix3 = substr($accountCode, 0, 3);
        if ($prefix3 === '516' || $prefix3 === '621') {
            return 'GOR';
        }

        return 'KOSONG';
    }

    private function applyNoUrut(string $accountName): int
    {
        return str_contains(strtoupper($accountName), 'PENJUALAN') ? 1 : 2;
    }

    private function applyPenjualan(string $accountCode): string
    {
        $prefix3 = substr($accountCode, 0, 3);

        if (in_array($prefix3, ['411', '412', '451', '516', '621'], true)) {
            return 'PENJUALAN';
        }

        return 'NOT';
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

        $groupKeys = [];

        foreach ($rows as $row) {
            $nameGroupKayu = (string) ($row['_nameGroupKayu'] ?? '');
            $noUrut = (int) ($row['_noUrut'] ?? 2);
            $penjualan = (string) ($row['_penjualan'] ?? 'NOT');
            $assembly = (int) ($row['_assembly'] ?? 2);
            $accountCode = (string) ($row['Account Code'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $description = (string) ($row['Description'] ?? '');
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

            if (! isset($groups[$nameGroupKayu])) {
                $groups[$nameGroupKayu] = [
                    'name' => $nameGroupKayu,
                    'header_name' => '',
                    'description_names' => [],
                    'monthly_sales' => [],
                    'monthly_hpp' => [],
                ];
                foreach ($months as $mk => $ml) {
                    $groups[$nameGroupKayu]['monthly_sales'][$mk] = 0.0;
                    $groups[$nameGroupKayu]['monthly_hpp'][$mk] = 0.0;
                }
                $groupKeys[] = $nameGroupKayu;
            }

            $descKey = $accountCode.'|||'.$accountName;
            if (! isset($groups[$nameGroupKayu]['description_names'][$descKey])) {
                $groups[$nameGroupKayu]['description_names'][$descKey] = [
                    'account_code' => $accountCode,
                    'account_name' => $accountName,
                    'no_urut' => $noUrut,
                ];
            }

            if ($assembly === 2 && $penjualan === 'PENJUALAN' && $amountCr > 0) {
                $groups[$nameGroupKayu]['monthly_sales'][$monthKey] += $amountCr;

                if ($groups[$nameGroupKayu]['header_name'] === '') {
                    $groups[$nameGroupKayu]['header_name'] = $accountName;
                }
            }

            if ($assembly === 2 && $this->isHppAccount($accountCode) && $amountCr > 0) {
                $groups[$nameGroupKayu]['monthly_hpp'][$monthKey] += $amountCr;
            }
        }

        $sorted = [];
        sort($groupKeys);
        foreach ($groupKeys as $key) {
            if (isset($groups[$key])) {
                $hasSales = array_sum($groups[$key]['monthly_sales']) > 0;
                $hasHpp = array_sum($groups[$key]['monthly_hpp']) > 0;
                if (! $hasSales && ! $hasHpp) {
                    continue;
                }
                $descriptions = array_values($groups[$key]['description_names']);
                usort($descriptions, fn ($a, $b) => $a['no_urut'] <=> $b['no_urut']);
                $groups[$key]['description_names'] = $descriptions;
                $sorted[] = $groups[$key];
            }
        }

        return $sorted;
    }

    private function isHppAccount(string $accountCode): bool
    {
        return str_starts_with($accountCode, '111.400.');
    }

    private function computeGroupMargins(array $groups, array $months): array
    {
        foreach ($groups as &$group) {
            $group['monthly_margin'] = [];

            $hasPenjualanAccount = ! empty(array_filter(
                $group['description_names'] ?? [],
                fn ($d) => ($d['no_urut'] ?? 2) === 1
            ));

            if (! $hasPenjualanAccount) {
                foreach ($months as $mk => $ml) {
                    $group['monthly_margin'][$mk] = null;
                }
                $group['rata_rata'] = null;
                $group['terendah'] = null;
                $group['tertinggi'] = null;

                continue;
            }

            foreach ($months as $mk => $ml) {
                $sales = $group['monthly_sales'][$mk] ?? 0;
                $hpp = $group['monthly_hpp'][$mk] ?? 0;

                if ($sales != 0) {
                    $group['monthly_margin'][$mk] = round(($sales - $hpp) / $sales * 100, 2);
                } else {
                    $group['monthly_margin'][$mk] = null;
                }
            }

            $margins = array_values(array_filter($group['monthly_margin'], fn ($v) => $v !== null));
            $group['rata_rata'] = count($margins) > 0 ? round(array_sum($margins) / count($margins), 2) : null;
            $group['terendah'] = count($margins) > 0 ? round(min($margins), 2) : null;
            $group['tertinggi'] = count($margins) > 0 ? round(max($margins), 2) : null;
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
                $hppGlobal[$mk] = null;
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
