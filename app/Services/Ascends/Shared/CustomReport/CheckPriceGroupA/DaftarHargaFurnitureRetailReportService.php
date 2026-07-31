<?php

namespace App\Services\Ascends\Shared\CustomReport\CheckPriceGroupA;

use RuntimeException;
use XMLReader;

class DaftarHargaFurnitureRetailReportService
{
    private const TITLE = 'DAFTAR HARGA FURNITURE';

    private const ALLOWED_FAMILIES = [
        'PLASTIK KABINET 2',
        'PLASTIK KABINET 1',
        'FURNITURE LIPAT',
        'PLASTIK FURNITURE 1',
        'PLASTIK FURNITURE 2',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $items = $this->processItems($rows);

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'items' => $items,
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

    private function determineGroup(string $priceGroupName): string
    {
        $upper = strtoupper($priceGroupName);

        if (
            str_contains($upper, '2401') ||
            str_contains($upper, '2402') ||
            str_contains($upper, '2501') ||
            str_contains($upper, '2301') ||
            str_contains($upper, '2302') ||
            str_contains($upper, '2303') ||
            str_contains($upper, '2304') ||
            str_contains($upper, '2502')
        ) {
            return 'MERONA';
        }

        if (
            str_contains($upper, '2801') ||
            str_contains($upper, '2802') ||
            str_contains($upper, '2814') ||
            str_contains($upper, '2816') ||
            str_contains($upper, '2832') ||
            str_contains($upper, '2870')
        ) {
            return 'MO.RE';
        }

        if (str_contains($upper, '2601')) {
            return 'MODELUX';
        }

        if (
            str_contains($upper, '53003') ||
            str_contains($upper, '53004') ||
            str_contains($upper, '53014') ||
            str_contains($upper, '53024')
        ) {
            return 'GRANDE';
        }

        return '';
    }

    private function determineGRUrut(string $group): int
    {
        $upper = strtoupper($group);
        if ($upper === 'MERONA') {
            return 1;
        }
        if ($upper === 'MO.RE') {
            return 2;
        }
        if ($upper === 'MODELUX') {
            return 3;
        }
        if ($upper === 'GRANDE') {
            return 4;
        }

        return 5;
    }

    private function determineUrut(string $desc): int
    {
        $upper = strtoupper($desc);

        if (str_contains($upper, 'PREMIUM')) {
            return 1;
        }
        if (
            str_contains($upper, 'KM 2401A') ||
            str_contains($upper, 'KM 2402A') ||
            str_contains($upper, 'KM 2401 A') ||
            str_contains($upper, 'KM 2402 A') ||
            str_contains($upper, 'MS 2801A') ||
            str_contains($upper, 'MS 2801 A') ||
            str_contains($upper, 'MS 2802') ||
            str_contains($upper, 'MS 2802A') ||
            str_contains($upper, 'MS 2802 A')
        ) {
            return 2;
        }
        if (
            str_contains($upper, 'KS 2501 A') ||
            str_contains($upper, 'KS 2502 A') ||
            str_contains($upper, 'KS 2501A') ||
            str_contains($upper, 'KS 2502A')
        ) {
            return 3;
        }
        if (str_contains($upper, 'MEJA LIPAT 6') || str_contains($upper, 'MEJA LIPAT 4')) {
            return 7;
        }
        if (str_contains($upper, 'MEJA LIPAT 32')) {
            return 8;
        }

        return 9;
    }

    private function determineKet(string $group): string
    {
        $upper = strtoupper($group);
        if ($upper === 'MERONA' || $upper === 'MO.RE' || $upper === 'MODELUX') {
            return 'Isi Per Bal (Pcs)';
        }

        return 'Isi Per Dus (Unit)';
    }

    private function processItems(array $rows): array
    {
        $groupedItems = [];

        foreach ($rows as $row) {
            $family = trim((string) ($row['FamilyName'] ?? ''));
            if (! in_array($family, self::ALLOWED_FAMILIES, true)) {
                continue;
            }

            $priceLevel = trim((string) ($row['PriceLevelName'] ?? ''));
            if (! str_starts_with($priceLevel, '01. Harga Retail')) {
                continue;
            }

            $priceGroupName = trim((string) ($row['PriceGroupName'] ?? ''));
            $group = $this->determineGroup($priceGroupName);

            $desc = trim((string) ($row['PriceGroupDescription'] ?? $priceGroupName));
            $key = $priceGroupName !== '' ? $priceGroupName : $desc;

            if (! isset($groupedItems[$key])) {
                $groupedItems[$key] = [
                    'group' => $group,
                    'gr_urut' => $this->determineGRUrut($group),
                    'urut' => $this->determineUrut($desc),
                    'description' => $desc,
                    'per_dus' => (float) ($row['PerDus'] ?? 1),
                    'ket' => $this->determineKet($group),
                    'harga_konsumen' => (float) ($row['Price'] ?? 0),
                    'retail_diskon_5' => (float) ($row['PriceAfterDisc'] ?? 0),
                ];
            }
        }

        $items = array_values($groupedItems);
        usort($items, function ($a, $b) {
            if ($a['gr_urut'] !== $b['gr_urut']) {
                return $a['gr_urut'] <=> $b['gr_urut'];
            }
            if ($a['group'] !== $b['group']) {
                return strnatcasecmp($a['group'], $b['group']);
            }
            if ($a['urut'] !== $b['urut']) {
                return $a['urut'] <=> $b['urut'];
            }

            return strnatcasecmp($a['description'], $b['description']);
        });

        return $items;
    }
}
