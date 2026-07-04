<?php

namespace App\Services\Ascends\Shared\Associate;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class ListCustomerPerKotaReportService
{
    private const TITLE = 'Laporan Data Customer Per Kota';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rows = $this->parseXml($xmlContents, $sourceLabel);

        $rows = array_values(array_filter(
            $rows,
            static function (array $row): bool {
                $status = trim((string) ($row['Status'] ?? ''));

                return strcasecmp($status, 'Active') === 0;
            }
        ));

        if ($rows === []) {
            throw new RuntimeException('Data customer tidak ditemukan pada XML.');
        }

        $formattedRows = array_values(array_map(fn (array $row): array => $this->formatRow($row), $rows));

        usort($formattedRows, static function (array $a, array $b): int {
            $cityCmp = strcasecmp(
                (string) ($a['Kota'] ?? ''),
                (string) ($b['Kota'] ?? '')
            );
            if ($cityCmp !== 0) {
                return $cityCmp;
            }

            return strcasecmp(
                (string) ($a['Kode Customer'] ?? ''),
                (string) ($b['Kode Customer'] ?? '')
            );
        });

        $grouped = [];
        foreach ($formattedRows as $row) {
            $kota = (string) ($row['Kota'] ?? '');
            if ($kota === '') {
                $kota = '-';
            }
            $grouped[$kota][] = $row;
        }

        $sections = [];
        $grandTotal = 0;
        foreach ($grouped as $kota => $groupRows) {
            $sections[] = [
                'group_label' => $kota,
                'rows' => array_values(array_map(
                    static fn (array $row): array => [
                        'Kode Customer' => $row['Kode Customer'],
                        'Nama Customer' => $row['Nama Customer'],
                        'Alamat' => $row['Alamat'],
                    ],
                    $groupRows
                )),
                'subtotal' => count($groupRows),
            ];
            $grandTotal += count($groupRows);
        }

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => '',
            'headers' => ['No', 'Kode Customer', 'Nama Customer', 'Alamat'],
            'sections' => $sections,
            'total_rows' => $grandTotal,
        ];
    }

    private function formatRow(array $row): array
    {
        return [
            'Kode Customer' => trim((string) ($row['Customer_x0020_Code'] ?? '')),
            'Nama Customer' => trim((string) ($row['Customer_x0020_Name'] ?? '')),
            'Alamat' => trim((string) ($row['Billing_x0020_Address_x0020_1'] ?? '')),
            'Kota' => trim((string) ($row['Billing_x0020_City'] ?? '')),
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
}
