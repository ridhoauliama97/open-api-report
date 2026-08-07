<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class ItemDiscontinueReportService
{
    private const TITLE = 'Laporan Item Discontinue';

    private const CATEGORY_ORDER = [
        'BAHAN BAKU',
        'BAHAN PENDUKUNG',
        'BARANG DAGANG',
        'BARANG JADI',
        'WORK IN PROGRESS',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $sections = $this->buildSections($rows);

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');
        $startDateFormatted = $startDate ? $startDate->locale('id')->isoFormat('DD-MMM-YY') : '';
        $endDateFormatted = $endDate ? $endDate->locale('id')->isoFormat('DD-MMM-YY') : '';

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
            ?? $filters['DateRange_x0020_'.$key]
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

    private function buildSections(array $rows): array
    {
        $byCategory = [];
        foreach ($rows as $row) {
            $category = trim((string) ($row['StockCategoryName'] ?? ''));
            if ($category === '') {
                continue;
            }
            $byCategory[$category][] = $this->buildItem($row);
        }

        $sections = [];
        foreach (self::CATEGORY_ORDER as $category) {
            if (! isset($byCategory[$category])) {
                continue;
            }
            $sections[] = [
                'category' => $category,
                'items' => $byCategory[$category],
            ];
        }

        // Any category not listed in the fixed order is appended at the end.
        foreach ($byCategory as $category => $items) {
            if (in_array($category, self::CATEGORY_ORDER, true)) {
                continue;
            }
            $sections[] = [
                'category' => $category,
                'items' => $items,
            ];
        }

        return $sections;
    }

    private function buildItem(array $row): array
    {
        $sawal = $this->decimal($row, 'Sawal');
        $good = $this->decimal($row, 'Good');
        $broken = $this->decimal($row, 'Broken');
        $qtyAdjusIn = $this->decimal($row, 'QtyAdjusIn');
        $retur = $this->decimal($row, 'Retur');
        $sales = $this->decimal($row, 'Sales');
        $qtyAdjusOut = $this->decimal($row, 'QtyAdjusOut');
        $material = $this->decimal($row, 'Material');
        $qtyPrcIn = $this->decimal($row, 'QtyPrcIn');
        $qtyUsg = $this->decimal($row, 'QtyUsg');
        $qtyPrcOut = $this->decimal($row, 'QtyPrcOut');

        $saldoAwal = $sawal + $good + $broken + $qtyAdjusIn + $retur
            - $sales - $qtyAdjusOut - $material
            + $qtyPrcIn - $qtyUsg - $qtyPrcOut;

        $stokIn = $this->decimal($row, 'PrcIN')
            + $this->decimal($row, 'AdjusIn')
            + $this->decimal($row, 'UsageIn')
            + $this->decimal($row, 'QtyProd');

        $stokOut = $this->decimal($row, 'Qty')
            + $this->decimal($row, 'QtyMatrl')
            + $this->decimal($row, 'AdjusOut');

        $akhir = $saldoAwal + $stokIn - $stokOut;

        return [
            'item_code' => (string) ($row['ItemCode'] ?? ''),
            'item_name' => (string) ($row['ItemName'] ?? ''),
            'category_name' => (string) ($row['StockCategoryName'] ?? ''),
            'family_name' => (string) ($row['FamilyName'] ?? ''),
            'saldo_awal' => $saldoAwal,
            'masuk' => $stokIn,
            'keluar' => $stokOut,
            'akhir' => $akhir,
        ];
    }

    private function decimal(array $row, string $key): float
    {
        $value = $row[$key] ?? null;

        if ($value === null || trim((string) $value) === '') {
            return 0.0;
        }

        return (float) $value;
    }
}
