<?php

namespace App\Services\Ascends\Shared\Associate;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class CustomerBaruPerTahunReportService
{
    private const TITLE = 'Laporan Penambahan Customer Baru (Periode 1 Tahun)';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rows = $this->parseXml($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data customer tidak ditemukan pada XML.');
        }

        $tahun = trim((string) ($filters['tahun'] ?? ''));

        if ($tahun === '' || ! is_numeric($tahun)) {
            throw new RuntimeException('Parameter Tahun wajib dikirim dan harus berupa angka (YYYY).');
        }

        $tahun = (int) $tahun;

        $rows = array_values(array_filter(
            $rows,
            static function (array $row) use ($tahun): bool {
                $code = trim((string) ($row['Sales_x0020_Person_x0020_Code'] ?? ''));
                if ($code === '' || str_starts_with($code, 'SP-0011')) {
                    return false;
                }

                $createdDate = trim((string) ($row['Created_x0020_Date_x002F_Time'] ?? ''));
                if ($createdDate === '') {
                    return false;
                }

                try {
                    return (int) Carbon::parse($createdDate)->format('Y') === $tahun;
                } catch (Throwable) {
                    return false;
                }
            }
        ));

        if ($rows === []) {
            throw new RuntimeException("Tidak ada data customer untuk tahun {$tahun}.");
        }

        $formattedRows = array_values(array_map(fn (array $row): array => $this->formatRow($row), $rows));

        usort($formattedRows, static fn (array $a, array $b): int => strcasecmp(
            (string) ($a['Salesman'] ?? ''),
            (string) ($b['Salesman'] ?? '')
        ));

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => self::resolvePrintedBy($rows),
            'headers' => ['No', 'Nama Customer', 'Salesman', 'Tipe', 'Tanggal', 'Credit Limit', 'Kota'],
            'rows' => $this->buildRows($formattedRows),
            'total_rows' => count($formattedRows),
            'per_date' => 'Periode '.$tahun,
        ];
    }

    private function buildRows(array $formattedRows): array
    {
        return array_values(array_map(
            static fn (array $row): array => [
                'Nama Customer' => $row['Nama Customer'],
                'Salesman' => $row['Salesman'],
                'Tipe' => $row['Tipe'],
                'Tanggal' => $row['Tanggal'],
                'Credit Limit' => $row['Credit Limit'],
                'Kota' => $row['Kota'],
            ],
            $formattedRows
        ));
    }

    private function formatRow(array $row): array
    {
        $type = trim((string) ($row['Customer_x0020_Type'] ?? ''));
        $creditLimitRaw = trim((string) ($row['Credit_x0020_Limit'] ?? '0'));
        $creditLimitNumeric = is_numeric($creditLimitRaw) ? (float) $creditLimitRaw : 0;

        return [
            'Nama Customer' => trim((string) ($row['Customer_x0020_Name'] ?? '')),
            'Salesman' => trim((string) ($row['Sales_x0020_Person_x0020_Name'] ?? '')),
            'Tipe' => $type !== '' ? $type : '-',
            'Tanggal' => $this->formatDate($row['Birth_x0020_Date'] ?? ''),
            'Credit Limit' => $creditLimitNumeric > 0
                ? 'Rp '.number_format($creditLimitNumeric, 0, ',', '.')
                : 'Rp 0',
            'Kota' => trim((string) ($row['Billing_x0020_City'] ?? '')),
        ];
    }

    private function formatDate(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '-';
        }

        try {
            return Carbon::parse($value)->locale('id')->translatedFormat('d-M-y');
        } catch (Throwable) {
            return '-';
        }
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
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'tabel') {
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
                $row[(string) $key] = trim((string) $value);
            }

            $rows[] = $row;
        }

        $reader->close();

        return $rows;
    }

    private static function resolvePrintedBy(array $rows): string
    {
        $candidateKeys = [
            'Last_x0020_Modified_x0020_By',
            'Created_x0020_By',
        ];

        foreach ($rows as $row) {
            foreach ($candidateKeys as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '' && strcasecmp($value, 'system') !== 0) {
                    return $value;
                }
            }
        }

        return '';
    }
}
