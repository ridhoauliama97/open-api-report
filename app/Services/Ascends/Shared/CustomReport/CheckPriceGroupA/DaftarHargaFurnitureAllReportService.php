<?php

namespace App\Services\Ascends\Shared\CustomReport\CheckPriceGroupA;

use RuntimeException;
use XMLReader;

class DaftarHargaFurnitureAllReportService
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

        // HANA / RAK SUSUN (Unnamed group at the bottom)
        if (
            str_contains($upper, '3101') ||
            str_contains($upper, '3102') ||
            str_contains($upper, '3103') ||
            str_contains($upper, '3104') ||
            str_contains($upper, '3105') ||
            str_contains($upper, '2204')
        ) {
            return '';
        }

        // MERONA
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

        // MO.RE
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

        // MODELUX
        if (str_contains($upper, '2601')) {
            return 'MODELUX';
        }

        // GRANDE
        if (
            str_contains($upper, '53003') ||
            str_contains($upper, '53004') ||
            str_contains($upper, '53014') ||
            str_contains($upper, '53024')
        ) {
            return 'GRANDE';
        }

        return 'NOT';
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
        if ($group === '') {
            return 5;
        }

        return 999;
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

            $priceGroupName = trim((string) ($row['PriceGroupName'] ?? ''));
            $group = $this->determineGroup($priceGroupName);
            if ($group === 'NOT') {
                continue;
            }

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
                    'base_price' => 0.0,
                    'retail_disc_5' => 0.0,
                    'semi_grosir' => 0.0,
                    'grosir' => 0.0,
                    'akun_spesial' => 0.0,
                ];
            }

            $priceLevel = trim((string) ($row['PriceLevelName'] ?? ''));
            $price = (float) ($row['Price'] ?? 0);
            $afterDisc = (float) ($row['PriceAfterDisc'] ?? 0);

            // Track max base price
            if ($price > $groupedItems[$key]['base_price']) {
                $groupedItems[$key]['base_price'] = $price;
            }

            if (str_contains($priceLevel, '01. Harga Retail')) {
                $groupedItems[$key]['retail_disc_5'] = $afterDisc;
            } elseif (str_contains($priceLevel, '02. Harga Semi Grosir')) {
                $groupedItems[$key]['semi_grosir'] = $afterDisc;
            } elseif (str_contains($priceLevel, '03. Harga Grosir')) {
                $groupedItems[$key]['grosir'] = $afterDisc;
            } elseif (str_contains($priceLevel, '04. Harga Akun Special')) {
                $groupedItems[$key]['akun_spesial'] = $afterDisc;
            }
        }

        // Sort items by gr_urut asc, then urut asc, then description asc
        $items = array_values($groupedItems);
        usort($items, function ($a, $b) {
            if ($a['gr_urut'] !== $b['gr_urut']) {
                return $a['gr_urut'] <=> $b['gr_urut'];
            }
            if ($a['urut'] !== $b['urut']) {
                return $a['urut'] <=> $b['urut'];
            }

            return strcasecmp($a['description'], $b['description']);
        });

        return $items;
    }
}
