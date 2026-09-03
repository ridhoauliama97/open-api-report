<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class SalesSummaryPerCustomerPerPeriodReportService
{
    private const TITLE = 'Laporan Sales Summary Per Customer Per Period';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');

        // Collect periods (InvoiceDate) from rows
        $originalPeriods = $this->collectPeriods($rows);

        if ($originalPeriods === []) {
            throw new RuntimeException('Tidak ada periode data ditemukan pada XML.');
        }

        // Create mapping: original (01-2026) -> formatted (Jan-26)
        $periodMapping = [];
        foreach ($originalPeriods as $origPeriod) {
            $carbonPeriod = Carbon::createFromFormat('m-Y', $origPeriod);
            $periodMapping[$origPeriod] = $carbonPeriod->locale('id')->isoFormat('MMM-YY');
        }

        // Use formatted periods for display
        $periods = array_values($periodMapping);

        // Group and aggregate by CustomerName (RP and SKU)
        $customerRpData = [];
        $customerSkuData = [];
        foreach ($rows as $row) {
            $customerName = trim((string) ($row['CustomerName'] ?? ''));
            if ($customerName === '') {
                $customerName = 'TANPA NAMA';
            }
            $period = trim((string) ($row['InvoiceDate'] ?? ''));
            $netTotal = (float) ($row['NetTotal'] ?? 0);
            $tes = (int) ($row['TES'] ?? 0);

            if ($period === '') {
                continue;
            }

            $customerRpData[$customerName][$period] = ($customerRpData[$customerName][$period] ?? 0.0) + $netTotal;
            $customerSkuData[$customerName][$period] = ($customerSkuData[$customerName][$period] ?? 0) + $tes;
        }

        $customers = [];
        $periodTotals = array_fill_keys($periods, 0.0);
        $grandTotalSum = 0.0;
        $grandMin = null;
        $grandMax = null;

        foreach ($customerRpData as $name => $pds) {
            $monthlyValues = [];
            $monthlySkuValues = [];
            $rowTotal = 0.0;
            $skuTotal = 0;
            foreach ($originalPeriods as $origPeriod) {
                $formattedPeriod = $periodMapping[$origPeriod];
                $val = $pds[$origPeriod] ?? 0.0;
                $skuVal = $customerSkuData[$name][$origPeriod] ?? 0;
                $monthlyValues[] = $val;
                $monthlySkuValues[] = $skuVal;
                $rowTotal += $val;
                $skuTotal += $skuVal;
                $periodTotals[$formattedPeriod] += $val;
            }

            $minVal = min($monthlyValues);
            $maxVal = max($monthlyValues);
            $avgVal = count($periods) > 0 ? $rowTotal / count($periods) : 0.0;

            $maxSku = max($monthlySkuValues);
            $avgSku = count($periods) > 0 ? $skuTotal / count($periods) : 0.0;

            $customers[] = [
                'customer_name' => $name,
                'months' => $monthlyValues,
                'sku_months' => $monthlySkuValues,
                'min' => $minVal,
                'max' => $maxVal,
                'avg' => $avgVal,
                'total' => $rowTotal,
                'max_sku' => $maxSku,
                'avg_sku' => $avgSku,
            ];

            $grandTotalSum += $rowTotal;
        }

        // Sort customers by total descending, then customer_name ascending
        usort($customers, function ($a, $b) {
            if ($b['total'] <=> $a['total']) {
                return $b['total'] <=> $a['total'];
            }

            return strcasecmp($a['customer_name'], $b['customer_name']);
        });

        $grandAvg = count($periods) > 0 ? $grandTotalSum / count($periods) : 0.0;

        $grandMinVal = 0.0;
        $grandMaxVal = 0.0;
        if ($customers !== []) {
            $grandMinVal = min(array_column($customers, 'min'));
            $grandMaxVal = max(array_column($customers, 'max'));
        }

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        $startDateFormatted = $startDate ? $startDate->locale('id')->isoFormat('DD-MMM-YY') : '';
        $endDateFormatted = $endDate ? $endDate->locale('id')->isoFormat('DD-MMM-YY') : '';
        if ($startDateFormatted === '' && $originalPeriods !== []) {
            $firstPeriod = reset($originalPeriods);
            $carbonFirst = Carbon::createFromFormat('m-Y', $firstPeriod);
            if ($carbonFirst) {
                $startDateFormatted = $carbonFirst->startOfMonth()->locale('id')->isoFormat('DD-MMM-YY');
            }
        }
        if ($endDateFormatted === '' && $originalPeriods !== []) {
            $lastPeriod = end($originalPeriods);
            $carbonLast = Carbon::createFromFormat('m-Y', $lastPeriod);
            if ($carbonLast) {
                $endDateFormatted = $carbonLast->endOfMonth()->locale('id')->isoFormat('DD-MMM-YY');
            }
        }

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'start_date' => $startDateFormatted,
            'end_date' => $endDateFormatted,
            'period_label' => ($startDateFormatted && $endDateFormatted) ? 'Dari '.$startDateFormatted.' s/d '.$endDateFormatted : '',
            'periods' => $periods,
            'customers' => $customers,
            'period_totals' => $periodTotals,
            'grand_total_sum' => $grandTotalSum,
            'grand_min' => $grandMinVal,
            'grand_max' => $grandMaxVal,
            'grand_avg' => $grandAvg,
        ];
    }

    private function parseRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML wajib dikirim.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid ({$sourceLabel}).");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'table') {
                continue;
            }

            $nodeXml = $reader->readOuterXml();
            if (! is_string($nodeXml) || trim($nodeXml) === '') {
                continue;
            }

            $node = simplexml_load_string($nodeXml);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $cleanKey = trim(str_replace('_x0020_', ' ', $key));
                $row[$cleanKey] = trim((string) $value);
            }

            $rows[] = $row;
        }

        $reader->close();

        return $rows;
    }

    private function resolveDateFilter(array $filters, string $key): ?Carbon
    {
        $value = trim((string) (
            $filters[$key]
            ?? $filters[strtolower($key)]
            ?? $filters['DateRange.'.$key]
            ?? $filters['DateRange_'.$key]
            ?? ''
        ));

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function collectPeriods(array $rows): array
    {
        $periods = [];
        foreach ($rows as $row) {
            $p = trim((string) ($row['InvoiceDate'] ?? ''));
            if ($p !== '') {
                $periods[$p] = true;
            }
        }

        $periods = array_keys($periods);

        // Sort periods chronologically (e.g. 01-2026, 02-2026)
        usort($periods, function ($a, $b) {
            try {
                $da = Carbon::createFromFormat('m-Y', $a);
                $db = Carbon::createFromFormat('m-Y', $b);

                return $da <=> $db;
            } catch (Throwable) {
                return strcmp($a, $b);
            }
        });

        return $periods;
    }
}
