<?php

namespace App\Services\Ascends\Shared\CustomReport\CheckPriceGroupA;

use RuntimeException;
use XMLReader;

class DaftarHargaEnamelAllReportService
{
    private const TITLE = 'DAFTAR HARGA ENAMEL (ALL)';

    private const ALLOWED_FAMILY_PREFIX = 'ENAMEL';

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
            str_contains($upper, 'BASKOM BIASA 30') ||
            str_contains($upper, 'BASKOM BIASA 40 CM M/MH') ||
            str_contains($upper, 'BASKOM BIASA 40 CM DECO') ||
            str_contains($upper, 'BASKOM BIASA 60') ||
            str_contains($upper, 'BASKOM DALAM 16') ||
            str_contains($upper, 'BASKOM DALAM 18') ||
            str_contains($upper, 'BASKOM DALAM 20') ||
            str_contains($upper, 'BASKOM DALAM 22') ||
            str_contains($upper, 'BASKOM DALAM 24') ||
            str_contains($upper, 'BASKOM DALAM 26') ||
            str_contains($upper, 'BASKOM DALAM 28') ||
            str_contains($upper, 'BASKOM DALAM 30') ||
            str_contains($upper, 'BASKOM DALAM 40 CM M/MH') ||
            str_contains($upper, 'BASKOM DALAM 40 CM PLS') ||
            str_contains($upper, 'KOBOKAN') ||
            str_contains($upper, 'KUALI HITAM 40') ||
            str_contains($upper, 'KUALI HITAM 45') ||
            str_contains($upper, 'SEKAR KUALI/WAJAN') ||
            str_contains($upper, 'NAMPAN 30') ||
            str_contains($upper, 'NAMPAN 40') ||
            str_contains($upper, 'NAMPAN 45') ||
            str_contains($upper, 'NAMPAN 52') ||
            str_contains($upper, 'NAMPAN 60 CM') ||
            str_contains($upper, 'PANCI 40') ||
            str_contains($upper, 'CANGKIR 10') ||
            str_contains($upper, 'CANGKIR 12') ||
            str_contains($upper, 'CANGKIR 9') ||
            str_contains($upper, 'CANGKIR 7') ||
            str_contains($upper, 'CANGKIR TUTUP') ||
            str_contains($upper, 'SEKAR MUG RIM') ||
            str_contains($upper, 'SEKAR MUG WITH RIM') ||
            str_contains($upper, 'SEKAR CANGKIR RING') ||
            str_contains($upper, 'PIRING') ||
            str_contains($upper, 'BASKOM VICTORY')
        ) {
            return 'Sekar1';
        }

        return 'Z';
    }

    private function determineBnt(string $priceGroupDescription): int
    {
        $upper = strtoupper($priceGroupDescription);

        if (
            str_contains($upper, 'SEKAR CANGKIR 7') ||
            str_contains($upper, 'SEKAR NAMPAN') ||
            str_contains($upper, 'SEKAR PIRING SOUP')
        ) {
            return 1;
        }

        return 0;
    }

    private function processItems(array $rows): array
    {
        $groupedItems = [];

        foreach ($rows as $row) {
            $family = trim((string) ($row['FamilyName'] ?? ''));
            if (! str_starts_with(strtoupper($family), self::ALLOWED_FAMILY_PREFIX)) {
                continue;
            }

            $priceGroupName = trim((string) ($row['PriceGroupName'] ?? ''));
            $group = $this->determineGroup($priceGroupName);
            if ($group === 'Z') {
                continue;
            }

            $desc = trim((string) ($row['PriceGroupDescription'] ?? $priceGroupName));
            $perDus = (float) ($row['PerDus'] ?? 1);
            $conversion = (float) ($row['Conversion'] ?? 1);
            $price = (float) ($row['Price'] ?? 0);
            $afterDisc = (float) ($row['PriceAfterDisc'] ?? 0);
            $priceLevel = trim((string) ($row['PriceLevelName'] ?? ''));
            $bnt = $this->determineBnt($desc);

            $key = $priceGroupName !== '' ? $priceGroupName : $desc;

            if (! isset($groupedItems[$key])) {
                $groupedItems[$key] = [
                    'group' => $group,
                    'description' => $desc,
                    'per_dus' => $perDus,
                    'bnt' => $bnt,
                    'harga_konsumen' => 0.0,
                    'retail' => 0.0,
                    'semi_grosir' => 0.0,
                    'grosir' => 0.0,
                    'akun_spesial' => 0.0,
                ];
            }

            if (str_contains($priceLevel, '01. Harga Retail')) {
                $groupedItems[$key]['retail'] = $conversion > 0 ? $afterDisc / $conversion : 0.0;
            } elseif (str_contains($priceLevel, '02. Harga Semi Grosir')) {
                $groupedItems[$key]['semi_grosir'] = $conversion > 0 ? $afterDisc / $conversion : 0.0;
            } elseif (str_contains($priceLevel, '03. Harga Grosir')) {
                $groupedItems[$key]['grosir'] = $conversion > 0 ? $afterDisc / $conversion : 0.0;
            } elseif (str_contains($priceLevel, '04. Harga Akun Special')) {
                $groupedItems[$key]['akun_spesial'] = $conversion > 0 ? $afterDisc / $conversion : 0.0;
            }

            if ($price > $groupedItems[$key]['harga_konsumen']) {
                $groupedItems[$key]['harga_konsumen'] = $price;
            }
        }

        $items = array_values($groupedItems);
        usort($items, function ($a, $b) {
            if ($a['group'] !== $b['group']) {
                return $a['group'] <=> $b['group'];
            }

            return strnatcasecmp($a['description'], $b['description']);
        });

        return $items;
    }
}
