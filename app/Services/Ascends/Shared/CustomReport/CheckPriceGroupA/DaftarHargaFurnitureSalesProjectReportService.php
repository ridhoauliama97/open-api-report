<?php

namespace App\Services\Ascends\Shared\CustomReport\CheckPriceGroupA;

use RuntimeException;
use XMLReader;

class DaftarHargaFurnitureSalesProjectReportService
{
    private const TITLE = 'DAFTAR HARGA FURNITURE';

    private const ALLOWED_FAMILIES = [
        'FURNITURE LIPAT',
        'PLASTIK FURNITURE 1',
        'PLASTIK FURNITURE 2',
        'PLASTIK KABINET 1',
        'PLASTIK KABINET 2',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $groups = $this->processItems($rows);

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'groups' => $groups,
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

        if (str_contains($upper, '2502')) {
            return 'MERONA 2';
        }
        if (
            str_contains($upper, '2401') ||
            str_contains($upper, '2402') ||
            str_contains($upper, '2501') ||
            str_contains($upper, '2301') ||
            str_contains($upper, '2302') ||
            str_contains($upper, '2303') ||
            str_contains($upper, '2304')
        ) {
            return 'MERONA';
        }
        if (str_contains($upper, '2814') || str_contains($upper, '2816')) {
            return 'MORE 2';
        }
        if (str_contains($upper, '2801') || str_contains($upper, '2802') || str_contains($upper, '2832') || str_contains($upper, '2870')) {
            return 'MORE 1';
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

        return 'NOT';
    }

    private function determineGRUrut(string $group): int
    {
        $upper = strtoupper($group);
        if ($upper === 'MERONA') {
            return 1;
        }
        if ($upper === 'MERONA 2') {
            return 1;
        }
        if ($upper === 'MORE 1') {
            return 2;
        }
        if ($upper === 'MORE 2') {
            return 2;
        }
        if ($upper === 'MODELUX') {
            return 3;
        }
        if ($upper === 'GRANDE') {
            return 4;
        }

        return 999;
    }

    private function getGroupTierInfo(string $group): array
    {
        $upper = strtoupper($group);
        if ($upper === 'MERONA' || $upper === 'MERONA 2' || $upper === 'MORE 1') {
            return [
                'qty_labels' => ['15 - 44 Pcs', '45 - 150 Pcs', '> 150 Pcs', ''],
                'disc_labels' => ['DISC 7%', 'DISC 8%', 'DISC 10%', ''],
                'has_4_tiers' => false,
            ];
        }
        if ($upper === 'MORE 2') {
            return [
                'qty_labels' => ['1 - 5 Pcs', '6 - 10 Pcs', '11 - 20 Pcs', '> 20 Pcs'],
                'disc_labels' => ['DISC 32%', 'DISC 35%', 'DISC 35% + 10.000', 'DISC 35% + 20.000'],
                'has_4_tiers' => true,
            ];
        }
        if ($upper === 'MODELUX') {
            return [
                'qty_labels' => ['5 - 44 Pcs', '45 - 150 Pcs', '> 150 Pcs', ''],
                'disc_labels' => ['DISC 20%', 'DISC 21%', 'DISC 22%', ''],
                'has_4_tiers' => false,
            ];
        }
        if ($upper === 'GRANDE') {
            return [
                'qty_labels' => ['5 - 19 Dus', '20 - 49 Dus', '> 50 Dus', ''],
                'disc_labels' => ['DISC 14%', 'DISC 15%', 'DISC 17%', ''],
                'has_4_tiers' => false,
            ];
        }

        return [
            'qty_labels' => ['', '', '', ''],
            'disc_labels' => ['', '', '', ''],
            'has_4_tiers' => false,
        ];
    }

    private function processItems(array $rows): array
    {
        $productMap = [];

        foreach ($rows as $row) {
            $family = trim((string) ($row['FamilyName'] ?? ''));
            if (! in_array($family, self::ALLOWED_FAMILIES, true)) {
                continue;
            }

            $priceLevel = trim((string) ($row['PriceLevelName'] ?? ''));
            if (! str_starts_with($priceLevel, '12. Harga Sales Project')) {
                continue;
            }

            $priceGroupName = trim((string) ($row['PriceGroupName'] ?? ''));
            $group = $this->determineGroup($priceGroupName);
            if ($group === 'NOT') {
                continue;
            }

            $desc = trim((string) ($row['PriceGroupDescription'] ?? $priceGroupName));
            $key = $priceGroupName !== '' ? $priceGroupName : $desc;

            if (! isset($productMap[$key])) {
                $productMap[$key] = [
                    'group' => $group,
                    'gr_urut' => $this->determineGRUrut($group),
                    'description' => $desc,
                    'max_price' => 0.0,
                    'sp_records' => [],
                ];
            }

            $price = (float) ($row['Price'] ?? 0);
            $afterDisc = (float) ($row['PriceAfterDisc'] ?? 0);

            if ($price > $productMap[$key]['max_price']) {
                $productMap[$key]['max_price'] = $price;
            }

            $productMap[$key]['sp_records'][] = $afterDisc;
        }

        $items = [];
        foreach ($productMap as $p) {
            $spRecords = $p['sp_records'];
            $items[] = [
                'group' => $p['group'],
                'gr_urut' => $p['gr_urut'],
                'description' => $p['description'],
                'harga_konsumen' => $p['max_price'],
                'semi_grosir_1' => $spRecords[0] ?? 0.0,
                'semi_grosir_2' => $spRecords[1] ?? 0.0,
                'semi_grosir_3' => $spRecords[2] ?? 0.0,
                'semi_grosir_4' => $spRecords[3] ?? 0.0,
            ];
        }

        // Sort items by gr_urut asc, then description asc
        usort($items, function ($a, $b) {
            if ($a['gr_urut'] !== $b['gr_urut']) {
                return $a['gr_urut'] <=> $b['gr_urut'];
            }
            if ($a['group'] !== $b['group']) {
                return strnatcasecmp($a['group'], $b['group']);
            }

            return strnatcasecmp($a['description'], $b['description']);
        });

        // Group into structured groups for view
        $grouped = [];
        foreach ($items as $item) {
            $gName = $item['group'];
            if (! isset($grouped[$gName])) {
                $tierInfo = $this->getGroupTierInfo($gName);
                $grouped[$gName] = [
                    'name' => $gName,
                    'gr_urut' => $item['gr_urut'],
                    'qty_labels' => $tierInfo['qty_labels'],
                    'disc_labels' => $tierInfo['disc_labels'],
                    'has_4_tiers' => $tierInfo['has_4_tiers'],
                    'items' => [],
                ];
            }
            $grouped[$gName]['items'][] = $item;
        }

        // Sort groups by gr_urut asc, then name asc
        uasort($grouped, function ($a, $b) {
            if ($a['gr_urut'] !== $b['gr_urut']) {
                return $a['gr_urut'] <=> $b['gr_urut'];
            }

            return strnatcasecmp($a['name'], $b['name']);
        });

        return array_values($grouped);
    }
}
