<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class MonitoringSoSiTagihanReportService
{
    private const TITLE = 'Laporan Monitoring SO - SI - Tagihan';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');
        $printDate = $this->resolveDateFilter($filters, 'PrintDate') ?? Carbon::now();

        $filteredRows = $this->filterRows($rows, $startDate, $endDate);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data dalam rentang tanggal yang dipilih.');
        }

        $dataRows = $this->buildRows($filteredRows, $printDate);

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

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
            'rows' => $dataRows,
            'total_rows' => count($dataRows),
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

    /**
     * Filter berdasarkan tanggal SO (SODate) antara StartDate dan EndDate.
     *
     * @param  array<int, array<string, string>>  $rows
     */
    private function filterRows(array $rows, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $filtered = [];
        foreach ($rows as $row) {
            $soDate = $this->parseDate((string) ($row['SODate'] ?? ''));
            if ($soDate === null) {
                continue;
            }

            if ($startDate !== null && $soDate->lessThan($startDate->startOfDay())) {
                continue;
            }

            if ($endDate !== null && $soDate->greaterThan($endDate->endOfDay())) {
                continue;
            }

            $filtered[] = $row;
        }

        return $filtered;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildRows(array $rows, Carbon $printDate): array
    {
        $dataRows = [];
        foreach ($rows as $row) {
            $soDate = $this->parseDate((string) ($row['SODate'] ?? ''));
            $invoiceDate = $this->parseDate((string) ($row['InvoiceDate'] ?? ''));
            $dateV = $this->parseDate((string) ($row['DateV'] ?? ''));

            $dataRows[] = [
                'customer_name' => trim((string) ($row['CustomerName'] ?? '')),
                'so_number' => trim((string) ($row['SONumber'] ?? '')),
                'so_date' => $soDate?->locale('id')->isoFormat('DD-MMM-YY') ?? '',
                'invoice_number' => trim((string) ($row['InvoiceNumber'] ?? '')),
                'inv_date' => $invoiceDate?->locale('id')->isoFormat('DD-MMM-YY') ?? '',
                'so_ke_si' => $this->soKeSi($row),
                'date_pelunas' => $dateV?->locale('id')->isoFormat('DD-MMM-YY') ?? '',
                'si_ke_tgh' => $this->siKeTgh($row, $invoiceDate, $printDate),
                'lunas' => $this->lunas($row),
            ];
        }

        return $dataRows;
    }

    /**
     * Formula sosi: {Table.SO-SI}.
     *
     * @param  array<string, string>  $row
     */
    private function soKeSi(array $row): string
    {
        $value = trim((string) ($row['SO-SI'] ?? ''));

        return $value !== '' ? $value : '';
    }

    /**
     * Formula sosi 2: if isnull({Table.SI-TGH}) then printdate - {Table.InvoiceDate}
     * else {Table.SI-TGH}.
     *
     * @param  array<string, string>  $row
     */
    private function siKeTgh(array $row, ?Carbon $invoiceDate, Carbon $printDate): string
    {
        $value = trim((string) ($row['SI-TGH'] ?? ''));
        if ($value !== '') {
            return $value;
        }

        if ($invoiceDate === null) {
            return '';
        }

        return (string) $invoiceDate->copy()->startOfDay()->diffInDays($printDate->copy()->startOfDay());
    }

    /**
     * Formula Ket: if isnull({Table.Ket}) or 'Belum' in {Table.Ket} then 'No' else 'Yes'.
     *
     * @param  array<string, string>  $row
     */
    private function lunas(array $row): string
    {
        $ket = trim((string) ($row['Ket'] ?? ''));

        if ($ket === '' || str_contains($ket, 'Belum')) {
            return 'No';
        }

        return 'Yes';
    }
}
