<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class CustomerOverLimitReportService
{
    private const TITLE = 'Laporan Customer Over Limit';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));
        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));

        $groups = $this->groupRows($rows);
        $dataRows = $this->buildRows($groups);
        $totals = $this->buildTotals($dataRows);

        $asOfDate = $this->resolveAsOfDate($rows);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'as_of_date' => $asOfDate?->locale('id')->isoFormat('DD-MMM-YY') ?? '',
            'period_label' => $asOfDate
                ? 'Per Tanggal : '.$asOfDate->locale('id')->isoFormat('DD-MMM-YY')
                : '',
            'rows' => $dataRows,
            'totals' => $totals,
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

    /**
     * Kelompokkan baris detail berdasarkan nama customer (urut alfabetis).
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, array<int, array<string, string>>>
     */
    private function groupRows(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['CustomerName'] ?? ''));
            $groups[$name][] = $row;
        }

        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

        return $groups;
    }

    /**
     * @param  array<string, array<int, array<string, string>>>  $groups
     * @return array<int, array<string, mixed>>
     */
    private function buildRows(array $groups): array
    {
        $dataRows = [];
        foreach ($groups as $name => $rows) {
            $sums = ['b1_30' => 0.0, 'b31_60' => 0.0, 'b61_90' => 0.0, 'over90' => 0.0, 'tagihan' => 0.0];
            $creditLimit = 0.0;

            foreach ($rows as $row) {
                $hasil = $this->hasil($row);
                $lamaHari = $this->lamaHari($row);
                $creditLimit = $this->asFloat($row['CreditLimit'] ?? null, $creditLimit);

                if ($lamaHari >= 1 && $lamaHari <= 30) {
                    $sums['b1_30'] += $hasil;
                } elseif ($lamaHari >= 31 && $lamaHari <= 60) {
                    $sums['b31_60'] += $hasil;
                } elseif ($lamaHari >= 61 && $lamaHari <= 90) {
                    $sums['b61_90'] += $hasil;
                } elseif ($lamaHari > 90) {
                    $sums['over90'] += $hasil;
                }

                $sums['tagihan'] += $hasil;
            }

            $dataRows[] = [
                'customer_name' => $name,
                'credit_limit' => $creditLimit,
                'b1_30' => $sums['b1_30'],
                'b31_60' => $sums['b31_60'],
                'b61_90' => $sums['b61_90'],
                'over90' => $sums['over90'],
                'tagihan' => $sums['tagihan'],
            ];
        }

        return $dataRows;
    }

    /**
     * Formula Hasil: {Table.Total} - {@Pembayaran}.
     * Pembayaran = jika null maka 0, selain itu {Table.Pembayaran}.
     *
     * @param  array<string, string>  $row
     */
    private function hasil(array $row): float
    {
        $total = $this->asFloat($row['Total'] ?? null, 0.0);
        $pembayaran = $this->asFloat($row['Pembayaran'] ?? null, 0.0);

        return $total - $pembayaran;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function lamaHari(array $row): int
    {
        $value = trim((string) ($row['LamaHari'] ?? ''));

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $dataRows
     * @return array<string, float>
     */
    private function buildTotals(array $dataRows): array
    {
        $totals = ['credit_limit' => 0.0, 'b1_30' => 0.0, 'b31_60' => 0.0, 'b61_90' => 0.0, 'over90' => 0.0, 'tagihan' => 0.0];
        foreach ($dataRows as $row) {
            foreach ($totals as $key => $_) {
                $totals[$key] += (float) ($row[$key] ?? 0);
            }
        }

        return $totals;
    }

    private function resolveAsOfDate(array $rows): ?Carbon
    {
        $maxDate = null;
        foreach ($rows as $row) {
            $invoiceDate = $this->parseDate((string) ($row['InvoiceDate'] ?? ''));
            if ($invoiceDate === null) {
                continue;
            }

            if ($maxDate === null || $invoiceDate->greaterThan($maxDate)) {
                $maxDate = $invoiceDate;
            }
        }

        return $maxDate;
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

    private function asFloat(?string $value, float $default): float
    {
        $value = $value !== null ? trim($value) : '';
        if ($value === '' || ! is_numeric($value)) {
            return $default;
        }

        return (float) $value;
    }
}
