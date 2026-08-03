<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class CekProduksiGsuReportService
{
    private const TITLE = 'Laporan Item Tidak ada Penjualan Dan Tidak Ada Produksi';

    private const EXCLUDED_ITEM_CODE_PREFIX = '2.1.5.1.08.12';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $rows = $this->applyRecordSelection($rows);

        if ($rows === []) {
            throw new RuntimeException('Tidak ada data yang sesuai dengan filter laporan.');
        }

        $grouped = $this->groupAndSort($rows);
        $sections = $this->buildSections($grouped);

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'start_date' => $startDate?->locale('id')->isoFormat('DD-MMM-YY') ?? '',
            'end_date' => $endDate?->locale('id')->isoFormat('DD-MMM-YY') ?? '',
            'sections' => $sections,
            'total_rows' => count($rows),
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
                $row[$key] = trim((string) $value);
            }

            $rows[] = $row;
        }

        $reader->close();

        return $rows;
    }

    /**
     * Record selection:
     * not ({Table.ItemCode} startswith "2.1.5.1.08.12") and
     * {@HasilStokBegining} > 0.00 and
     * {@Kosong} startswith "A".
     *
     * @param  array<string, string>  $rows
     * @return array<int, array<string, string>>
     */
    private function applyRecordSelection(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->isSelected($row)
        ));
    }

    /**
     * @param  array<string, string>  $row
     */
    private function isSelected(array $row): bool
    {
        $itemCode = trim((string) ($row['ItemCode'] ?? ''));

        if (str_starts_with($itemCode, self::EXCLUDED_ITEM_CODE_PREFIX)) {
            return false;
        }

        if ($this->hasilStokBegining($row) <= 0.0) {
            return false;
        }

        return $this->kosong($row) === 'A';
    }

    /**
     * @param  array<string, string>  $row
     */
    private function kosong(array $row): string
    {
        return $this->totalQty($row) == 0.0 ? 'A' : 'B';
    }

    /**
     * {@Qty} = {Table.Qty} + {Table.QtyProd} + {Table.QtyMatrl} + {Table.PrcIN} +
     * {Table.AdjusIn} + {Table.AdjusOut} + {Table.UsageIn}.
     *
     * @param  array<string, string>  $row
     */
    private function totalQty(array $row): float
    {
        return $this->frm($row, 'Qty')
            + $this->frm($row, 'QtyProd')
            + $this->frm($row, 'QtyMatrl')
            + $this->frm($row, 'PrcIN')
            + $this->frm($row, 'AdjusIn')
            + $this->frm($row, 'AdjusOut')
            + $this->frm($row, 'UsageIn');
    }

    /**
     * {HasilStokBegining} = {@FrmSawal} + {@FrmGood} + {@FrmBrokern} + {@FrmAdjusIn} + {@FrmRetur} -
     * {@FrmSales} - {@FrmAdjusOut} - {@FrmMaterial} + {@FrmQtyPrcIn} - {@FrmQtyUsage} - {@FrmQtyPrcOut}.
     *
     * @param  array<string, string>  $row
     */
    private function hasilStokBegining(array $row): float
    {
        return $this->frm($row, 'Sawal')
            + $this->frm($row, 'Good')
            + $this->frm($row, 'Broken')
            + $this->frm($row, 'QtyAdjusIn')
            + $this->frm($row, 'Retur')
            - $this->frm($row, 'Sales')
            - $this->frm($row, 'QtyAdjusOut')
            - $this->frm($row, 'Material')
            + $this->frm($row, 'QtyPrcIn')
            - $this->frm($row, 'QtyUsg')
            - $this->frm($row, 'QtyPrcOut');
    }

    /**
     * Formula helper: if null/empty then 0 else value.
     *
     * @param  array<string, string>  $row
     */
    private function frm(array $row, string $key): float
    {
        $value = $row[$key] ?? null;

        if ($value === null || trim((string) $value) === '') {
            return 0.0;
        }

        return (float) $value;
    }

    /**
     * Kelompokkan berdasarkan StockCategoryName (urutan abjad) lalu urutkan
     * ItemCode secara leksikografis menyesuaikan output Crystal Reports.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, array<int, array<string, string>>>
     */
    private function groupAndSort(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $category = trim((string) ($row['StockCategoryName'] ?? ''));
            if ($category === '') {
                $category = 'Lain-Lain';
            }
            $grouped[$category][] = $row;
        }

        ksort($grouped, SORT_STRING);

        foreach ($grouped as $category => &$groupRows) {
            usort(
                $groupRows,
                fn (array $a, array $b): int => strcmp(
                    trim((string) ($a['ItemCode'] ?? '')),
                    trim((string) ($b['ItemCode'] ?? ''))
                )
            );
        }
        unset($groupRows);

        return $grouped;
    }

    /**
     * @param  array<string, array<int, array<string, string>>>  $grouped
     * @return array<int, array<string, mixed>>
     */
    private function buildSections(array $grouped): array
    {
        $sections = [];

        foreach ($grouped as $category => $groupRows) {
            $sectionRows = [];

            foreach ($groupRows as $row) {
                $sectionRows[] = [
                    'item_code' => trim((string) ($row['ItemCode'] ?? '')),
                    'item_name' => trim((string) ($row['ItemName'] ?? '')),
                    'category_name' => trim((string) ($row['StockCategoryName'] ?? '')),
                    'family_name' => trim((string) ($row['FamilyName'] ?? '')),
                    'saldo_awal' => $this->hasilStokBegining($row),
                    'qty_sales' => 0.0,
                    'qty_prod' => 0.0,
                ];
            }

            $sections[] = [
                'category_name' => $category,
                'rows' => $sectionRows,
            ];
        }

        return $sections;
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
}
