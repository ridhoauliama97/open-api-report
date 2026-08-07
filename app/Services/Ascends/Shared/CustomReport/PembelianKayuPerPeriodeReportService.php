<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PembelianKayuPerPeriodeReportService
{
    private const TITLE = 'Laporan Pembelian Kayu Per Periode';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');

        $filteredRows = $this->filterByDateRange($rows, $startDate, $endDate);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data dalam rentang tanggal yang dipilih.');
        }

        $sections = $this->buildSections($filteredRows);

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        $startDateFormatted = $startDate ? $startDate->locale('id')->isoFormat('DD-MMM-YY') : '';
        $endDateFormatted = $endDate ? $endDate->locale('id')->isoFormat('DD-MMM-YY') : '';
        $periodMonth = $this->resolvePeriodMonth($filteredRows, $startDate);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'start_date' => $startDateFormatted,
            'end_date' => $endDateFormatted,
            'period_label' => ($startDateFormatted && $endDateFormatted)
                ? 'Dari '.$startDateFormatted.' s/d '.$endDateFormatted
                : '',
            'period_month' => $periodMonth,
            'sections' => $sections,
        ];
    }

    private function parseRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML wajib dikirim.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA)) {
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

        return $this->parseDate($value);
    }

    private function parseDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function filterByDateRange(array $rows, ?Carbon $startDate, ?Carbon $endDate): array
    {
        if ($startDate === null && $endDate === null) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->isWithinDateRange($row, $startDate, $endDate)
        ));
    }

    private function isWithinDateRange(array $row, ?Carbon $startDate, ?Carbon $endDate): bool
    {
        $rowDate = $this->parseDate((string) ($row['PurchaseDate'] ?? ''));
        if ($rowDate === null) {
            return false;
        }

        if ($startDate !== null && $rowDate->lessThan($startDate->startOfDay())) {
            return false;
        }

        if ($endDate !== null && $rowDate->greaterThan($endDate->endOfDay())) {
            return false;
        }

        return true;
    }

    private function resolvePeriodMonth(array $rows, ?Carbon $fallback): string
    {
        $earliest = null;
        foreach ($rows as $row) {
            $date = $this->parseDate((string) ($row['PurchaseDate'] ?? ''));
            if ($date === null) {
                continue;
            }
            if ($earliest === null || $date->lessThan($earliest)) {
                $earliest = $date;
            }
        }

        $monthDate = $earliest ?? $fallback;
        if ($monthDate === null) {
            return '';
        }

        $month = $monthDate->locale('id')->isoFormat('MMM');
        $year = $monthDate->year;

        return $month.' - '.$year;
    }

    private function buildSections(array $rows): array
    {
        $byUom = [];
        foreach ($rows as $row) {
            $uom = trim((string) ($row['UOMCode'] ?? ''));
            if ($uom === '') {
                continue;
            }
            $byUom[$uom][] = $row;
        }

        $sections = [];
        foreach ($byUom as $uom => $uomRows) {
            $sections[] = $this->buildSection($uom, $uomRows);
        }

        return $sections;
    }

    private function buildSection(string $uom, array $rows): array
    {
        $suppliers = $this->groupSuppliers($rows);

        $grandQty = 0.0;
        $grandTotal = 0.0;
        foreach ($suppliers as $supplier) {
            $grandQty += $supplier['qty'];
            $grandTotal += $supplier['total'];
        }

        foreach ($suppliers as $key => $supplier) {
            $suppliers[$key]['percent'] = $grandQty > 0 ? ($supplier['qty'] / $grandQty * 100) : 0.0;
        }

        return [
            'uom' => $uom,
            'qty' => $grandQty,
            'total' => $grandTotal,
            'suppliers' => $suppliers,
        ];
    }

    private function groupSuppliers(array $rows): array
    {
        $supplierMap = [];
        foreach ($rows as $row) {
            $supplier = trim((string) ($row['SupplierName'] ?? ''));
            if ($supplier === '') {
                continue;
            }

            $item = trim((string) ($row['ItemName'] ?? ''));
            $qty = (float) ($row['Quantity'] ?? 0);
            $total = (float) ($row['Hasil'] ?? 0);

            if (! isset($supplierMap[$supplier])) {
                $supplierMap[$supplier] = [
                    'supplier' => $supplier,
                    'qty' => 0.0,
                    'total' => 0.0,
                    'items' => [],
                ];
            }

            $supplierMap[$supplier]['qty'] += $qty;
            $supplierMap[$supplier]['total'] += $total;

            if (! isset($supplierMap[$supplier]['items'][$item])) {
                $supplierMap[$supplier]['items'][$item] = [
                    'item' => $item,
                    'qty' => 0.0,
                    'total' => 0.0,
                ];
            }

            $supplierMap[$supplier]['items'][$item]['qty'] += $qty;
            $supplierMap[$supplier]['items'][$item]['total'] += $total;
        }

        $result = [];
        foreach ($supplierMap as $supplier => $data) {
            $items = array_values($data['items']);
            usort($items, fn ($a, $b) => $b['qty'] <=> $a['qty']);

            $result[] = [
                'supplier' => $supplier,
                'qty' => $data['qty'],
                'total' => $data['total'],
                'items' => $items,
            ];
        }

        usort($result, fn ($a, $b) => $b['qty'] <=> $a['qty']);

        return $result;
    }
}
