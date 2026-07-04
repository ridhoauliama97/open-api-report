<?php

namespace App\Services\Ascends\Shared\Associate;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class CustomerBaruReportService
{
    private const TITLE = 'Laporan Customer Baru';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rows = $this->parseXml($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data customer tidak ditemukan pada XML.');
        }

        $tanggalRaw = trim((string) ($filters['tanggal'] ?? ''));
        if ($tanggalRaw === '') {
            throw new RuntimeException('Parameter Tanggal wajib dikirim.');
        }

        try {
            $tanggal = Carbon::parse($tanggalRaw)->startOfDay();
        } catch (Throwable) {
            throw new RuntimeException('Format Tanggal tidak valid. Gunakan YYYY-MM-DD.');
        }

        $monthPlus1 = $tanggal->copy()->addMonth()->startOfDay();
        $monthMinus1 = $monthPlus1->copy()->subDay()->endOfDay();

        $rows = array_values(array_filter(
            $rows,
            static function (array $row) use ($tanggal, $monthPlus1): bool {
                $status = trim((string) ($row['Status'] ?? ''));
                if (strcasecmp($status, 'Active') !== 0) {
                    return false;
                }

                $createdDate = trim((string) ($row['Created_x0020_Date_x002F_Time'] ?? ''));
                if ($createdDate === '') {
                    return false;
                }

                try {
                    $created = Carbon::parse($createdDate);
                } catch (Throwable) {
                    return false;
                }

                return $created->greaterThan($tanggal) && $created->lessThan($monthPlus1);
            }
        ));

        if ($rows === []) {
            throw new RuntimeException('Tidak ada customer baru pada periode tersebut.');
        }

        $formattedRows = array_values(array_map(fn (array $row): array => $this->formatRow($row), $rows));

        usort($formattedRows, static fn (array $a, array $b): int => strcasecmp(
            (string) ($a['Kode Customer'] ?? ''),
            (string) ($b['Kode Customer'] ?? '')
        ));

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => self::resolvePrintedBy($rows),
            'headers' => ['No', 'Kode Customer', 'Nama Customer', 'Alamat', 'Kota', 'Nama Pemilik', 'Telepon', 'Syarat (Term)', 'Credit Limit'],
            'rows' => $this->buildRows($formattedRows),
            'total_rows' => count($formattedRows),
            'per_date' => 'Dari '.$tanggal->locale('id')->translatedFormat('d-M-y').' s/d '.$monthMinus1->locale('id')->translatedFormat('d-M-y'),
        ];
    }

    private function buildRows(array $formattedRows): array
    {
        return array_values(array_map(
            static fn (array $row): array => [
                'Kode Customer' => $row['Kode Customer'],
                'Nama Customer' => $row['Nama Customer'],
                'Alamat' => $row['Alamat'],
                'Kota' => $row['Kota'],
                'Nama Pemilik' => $row['Nama Pemilik'],
                'Telepon' => $row['Telepon'],
                'Syarat (Term)' => $row['Syarat (Term)'],
                'Credit Limit' => $row['Credit Limit'],
            ],
            $formattedRows
        ));
    }

    private function formatRow(array $row): array
    {
        $creditLimitRaw = trim((string) ($row['Credit_x0020_Limit'] ?? '0'));
        $creditLimitNumeric = is_numeric($creditLimitRaw) ? (float) $creditLimitRaw : 0;

        return [
            'Kode Customer' => trim((string) ($row['Customer_x0020_Code'] ?? '')),
            'Nama Customer' => trim((string) ($row['Customer_x0020_Name'] ?? '')),
            'Alamat' => trim((string) ($row['Billing_x0020_Address_x0020_1'] ?? '')),
            'Kota' => trim((string) ($row['Billing_x0020_City'] ?? '')),
            'Nama Pemilik' => $this->orDash($row['Owner_x0020_Name'] ?? ''),
            'Telepon' => $this->orDash($row['Phone'] ?? ''),
            'Syarat (Term)' => $this->orDash($row['Payment_x0020_Term'] ?? ''),
            'Credit Limit' => $creditLimitNumeric > 0
                ? 'Rp '.number_format($creditLimitNumeric, 0, ',', '.')
                : 'Rp 0',
        ];
    }

    private function orDash(string $value): string
    {
        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : '-';
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
