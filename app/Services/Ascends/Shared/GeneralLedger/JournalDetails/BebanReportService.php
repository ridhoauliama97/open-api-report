<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class BebanReportService
{
    private const TITLE = 'Laporan Beban';

    private const CATEGORY_ORDER = [
        'BEBAN UMUM',
        'BEBAN MARKETING',
        'BEBAN PENJUALAN',
        'BIAYA PRODUKSI',
        'PENDAPATAN LAINNYA',
        'BEBAN LAINNYA',
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

        $months = $this->resolveMonths($startDate, $endDate);

        $accounts = $this->aggregateByAccount($filtered, $months);

        $categorized = $this->groupByCategory($accounts);

        $categoriesWithSubtotals = $this->computeCategorySubtotals($categorized, $months);

        $grandTotalMonthly = [];
        foreach ($months as $monthKey => $monthLabel) {
            $grandTotalMonthly[$monthKey] = array_sum(array_map(
                static fn (array $cat): float => $cat['monthly_subtotals'][$monthKey],
                $categoriesWithSubtotals
            ));
        }

        $grandTotal = array_sum($grandTotalMonthly);

        $categories = $this->computeCategoryPercentages($categoriesWithSubtotals, $grandTotalMonthly, $grandTotal, $months);

        $grandTotalStats = $this->computeStats($grandTotalMonthly);

        $period = $this->resolvePeriod($startDate, $endDate);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $period['label'],
            'start_date' => $period['start'],
            'end_date' => $period['end'],
            'months' => $months,
            'categories' => $categories,
            'grand_total_monthly' => $grandTotalMonthly,
            'grand_total' => $grandTotal,
            'grand_total_terendah' => $grandTotalStats['terendah'],
            'grand_total_tertinggi' => $grandTotalStats['tertinggi'],
            'grand_total_rata_rata' => $grandTotalStats['rata_rata'],
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

            $category = $this->applyUpahLangsung($accountCode, $company);

            if ($category === 'NOT') {
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

            $row['_category'] = $category;
            $result[] = $row;
        }

        return $result;
    }

    private function applyUpahLangsung(string $accountCode, string $company): string
    {
        $upperCompany = strtoupper($company);

        if (substr($accountCode, 0, 3) === '721') {
            return 'BEBAN UMUM';
        }

        if (substr($accountCode, 0, 3) === '712') {
            return 'BEBAN MARKETING';
        }

        if ($accountCode === '711.000.091' && $upperCompany === 'RU') {
            return 'BEBAN UMUM';
        }

        if (substr($accountCode, 0, 3) === '711') {
            return 'BEBAN PENJUALAN';
        }

        if ($accountCode === '514.000.018') {
            return 'BIAYA PRODUKSI';
        }

        $biayaProduksiPrefixes = ['500.004', '511.000', '514.000', '500.001', '500.005', '500.015', '500.010', '500.006', '500.018'];
        foreach ($biayaProduksiPrefixes as $prefix) {
            if (substr($accountCode, 0, 7) === $prefix) {
                return 'BIAYA PRODUKSI';
            }
        }

        if (substr($accountCode, 0, 7) === '800.000' && $upperCompany !== 'RU') {
            return 'PENDAPATAN LAINNYA';
        }

        if (substr($accountCode, 0, 7) === '900.000') {
            return 'BEBAN LAINNYA';
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

    private function aggregateByAccount(array $rows, array $months): array
    {
        $accounts = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $category = (string) ($row['_category'] ?? '');
            $voucherDate = (string) ($row['Voucher Date'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);
            $amount = $amountDb - $amountCr;

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

            $key = $accountCode.'|||'.$accountName;

            if (! isset($accounts[$key])) {
                $accounts[$key] = [
                    'account_code' => $accountCode,
                    'account_name' => $accountName,
                    'category' => $category,
                    'monthly_amounts' => [],
                ];
                foreach ($months as $mk => $ml) {
                    $accounts[$key]['monthly_amounts'][$mk] = 0.0;
                }
            }

            $accounts[$key]['monthly_amounts'][$monthKey] += $amount;
        }

        return array_values($accounts);
    }

    private function groupByCategory(array $accounts): array
    {
        $grouped = [];

        foreach ($accounts as $account) {
            $category = $account['category'];

            if (! isset($grouped[$category])) {
                $grouped[$category] = [
                    'name' => $category,
                    'accounts' => [],
                ];
            }

            $grouped[$category]['accounts'][] = $account;
        }

        $ordered = [];
        foreach (self::CATEGORY_ORDER as $cat) {
            if (isset($grouped[$cat])) {
                $ordered[] = $grouped[$cat];
                unset($grouped[$cat]);
            }
        }

        foreach ($grouped as $cat) {
            $ordered[] = $cat;
        }

        return $ordered;
    }

    private function computeCategorySubtotals(array $categories, array $months): array
    {
        foreach ($categories as &$category) {
            $category['monthly_subtotals'] = [];
            foreach ($months as $mk => $ml) {
                $category['monthly_subtotals'][$mk] = array_sum(array_map(
                    static fn (array $a): float => $a['monthly_amounts'][$mk] ?? 0,
                    $category['accounts']
                ));
            }

            $category['total_amount'] = array_sum($category['monthly_subtotals']);
        }

        return $categories;
    }

    private function computeCategoryPercentages(array $categories, array $grandTotalMonthly, float $grandTotal, array $months): array
    {
        foreach ($categories as &$category) {
            foreach ($months as $mk => $ml) {
                $gt = $grandTotalMonthly[$mk] ?? 0;
                $category['monthly_pcts'][$mk] = $gt != 0
                    ? round(($category['monthly_subtotals'][$mk] / $gt) * 100, 1)
                    : 0.0;
            }

            $category['total_pct'] = $grandTotal != 0
                ? round(($category['total_amount'] / $grandTotal) * 100, 1)
                : 0.0;

            $stats = $this->computeStats($category['monthly_subtotals']);
            $category['terendah'] = $stats['terendah'];
            $category['tertinggi'] = $stats['tertinggi'];
            $category['rata_rata'] = $stats['rata_rata'];

            foreach ($category['accounts'] as &$account) {
                foreach ($months as $mk => $ml) {
                    $catSubtotal = $category['monthly_subtotals'][$mk] ?? 0;
                    $account['monthly_pcts'][$mk] = $catSubtotal != 0
                        ? round(($account['monthly_amounts'][$mk] / $catSubtotal) * 100, 1)
                        : 0.0;
                }

                $account['total_amount'] = array_sum($account['monthly_amounts']);
                $account['total_pct'] = $category['total_amount'] != 0
                    ? round(($account['total_amount'] / $category['total_amount']) * 100, 1)
                    : 0.0;

                $stats = $this->computeStats($account['monthly_amounts']);
                $account['terendah'] = $stats['terendah'];
                $account['tertinggi'] = $stats['tertinggi'];
                $account['rata_rata'] = $stats['rata_rata'];
            }
        }

        return $categories;
    }

    private function computeStats(array $monthlyAmounts): array
    {
        $values = array_values($monthlyAmounts);

        return [
            'terendah' => count($values) > 0 ? min($values) : 0.0,
            'tertinggi' => count($values) > 0 ? max($values) : 0.0,
            'rata_rata' => count($values) > 0 ? array_sum($values) / count($values) : 0.0,
        ];
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
