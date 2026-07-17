<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalance;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class NeracaPerBulanReportService
{
    private const TITLE = 'Neraca';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $dateEnd = trim((string) ($filters['DateEnd'] ?? ''));
        $periodLabel = $this->resolvePeriodLabel($dateEnd);

        $filtered = $this->applySelectionFormula($allRows);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data Balance Sheet.');
        }

        $groupedByAccount = $this->groupByAccount($filtered);

        $company = strtoupper(trim((string) ($filters['company'] ?? 'UC')));

        $leftRows = $this->processSide($groupedByAccount, 'left', $company);
        $rightRows = $this->processSide($groupedByAccount, 'right', $company);

        $leftSections = $this->buildSections($leftRows);
        $rightSections = $this->buildSections($rightRows);

        $leftGrandTotal = array_sum(array_column($leftSections, 'total'));
        $rightGrandTotal = array_sum(array_column($rightSections, 'total'));

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'left_sections' => $leftSections,
            'left_grand_total' => $leftGrandTotal,
            'right_sections' => $rightSections,
            'right_grand_total' => $rightGrandTotal,
            'printed_by' => '',
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
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'Table1') {
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
                $cleanKey = $this->cleanXmlKey((string) $key);
                $row[$cleanKey] = trim((string) $value);
            }

            if (($row['AccountCode1'] ?? '') !== '') {
                $rows[] = $row;
            }
        }

        $reader->close();

        return $rows;
    }

    private function cleanXmlKey(string $key): string
    {
        $key = str_replace('_x0020_', ' ', $key);
        $key = str_replace('_x0028_', '(', $key);
        $key = str_replace('_x0029_', ')', $key);

        return str_replace('_x002F_', '/', $key);
    }

    private function applySelectionFormula(array $rows): array
    {
        return array_values(array_filter($rows, function (array $row): bool {
            return ($row['Account Type'] ?? '') === 'Balance Sheet';
        }));
    }

    private function resolvePeriodLabel(string $dateEnd): string
    {
        if ($dateEnd === '') {
            return '';
        }

        try {
            $date = Carbon::parse($dateEnd);

            return 'Per Tanggal '.$date->locale('id')->isoFormat('DD-MMM-YY');
        } catch (Throwable) {
            return 'Per Tanggal '.$dateEnd;
        }
    }

    private function groupByAccount(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $ac1 = (string) ($row['AccountCode1'] ?? '');
            $an1 = (string) ($row['AccountName1'] ?? '');
            $ending = (float) ($row['Ending'] ?? 0);

            if (! isset($groups[$ac1])) {
                $groups[$ac1] = [
                    'account_code' => $ac1,
                    'account_name' => $an1,
                    'balance' => 0.0,
                ];
            }

            $groups[$ac1]['balance'] += $ending;
        }

        uasort($groups, fn ($a, $b) => strcmp($a['account_code'], $b['account_code']));

        foreach ($groups as &$group) {
            $group['balance'] = round($group['balance'], 2);
        }

        return $groups;
    }

    private function processSide(array $groupedByAccount, string $side, string $company): array
    {
        $groups = [];

        foreach ($groupedByAccount as $ac1 => $data) {
            $code = $data['account_code'];
            $name = $data['account_name'];
            $ending = $data['balance'];

            $subSection = $this->applyAktFormula($code, $side);
            $skipVal = $side === 'left' ? '3' : '2';
            if ($subSection === $skipVal) {
                continue;
            }

            $section = $this->applyGrpAktvFormula($subSection, $side);
            $displayName = $this->applyNamaFormula($code, $name, $company, $side);
            $ending = $this->applyEndingFormula($code, $ending, $side);
            $sectionSort = $this->applySectionSortKey($section, $side);
            $subSortKey = $this->applyUrutFormula($subSection, $side);
            $itemSort = $this->applyUrutanNama($code, $name, $displayName);

            $groupKey = $section.'||'.$subSection.'||'.$displayName;

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'section' => $section,
                    'sub_section' => $subSection,
                    'display_name' => $displayName,
                    'balance' => 0.0,
                    'section_sort' => $sectionSort,
                    'sub_sort_key' => $subSortKey,
                    'item_sort' => $itemSort,
                    'account_code' => $code,
                    'account_name' => $name,
                ];
            }

            $groups[$groupKey]['balance'] += $ending;
            $groups[$groupKey]['balance'] = round($groups[$groupKey]['balance'], 2);
        }

        uasort($groups, function ($a, $b) {
            $cmp = $a['section_sort'] <=> $b['section_sort'];
            if ($cmp !== 0) return $cmp;

            $cmp = strcmp((string) $a['sub_sort_key'], (string) $b['sub_sort_key']);
            if ($cmp !== 0) return $cmp;

            $cmp = $a['item_sort'] <=> $b['item_sort'];
            if ($cmp !== 0) return $cmp;

            return strcmp($a['account_code'], $b['account_code']);
        });

        return $groups;
    }

    private function buildSections(array $rows): array
    {
        $sections = [];

        foreach ($rows as $row) {
            $sectionName = $row['section'];
            $subName = $row['sub_section'];
            $itemName = $row['display_name'];
            $balance = $row['balance'];

            if (! isset($sections[$sectionName])) {
                $sections[$sectionName] = [
                    'name' => $sectionName,
                    'sub_sections' => [],
                    'total' => 0.0,
                ];
            }

            if (! isset($sections[$sectionName]['sub_sections'][$subName])) {
                $sections[$sectionName]['sub_sections'][$subName] = [
                    'name' => $subName,
                    'items' => [],
                    'total' => 0.0,
                ];
            }

            $sections[$sectionName]['sub_sections'][$subName]['items'][] = [
                'account_code' => $row['account_code'],
                'account_name' => $itemName,
                'balance' => $balance,
            ];
            $sections[$sectionName]['sub_sections'][$subName]['total'] += $balance;
            $sections[$sectionName]['total'] += $balance;
        }

        foreach ($sections as &$section) {
            $section['total'] = round($section['total'], 2);
            foreach ($section['sub_sections'] as &$sub) {
                $sub['total'] = round($sub['total'], 2);
            }
        }

        return array_values($sections);
    }

    private function applySectionSortKey(string $section, string $side): int
    {
        if ($side === 'left') {
            return match ($section) {
                'AKTIVA LANCAR' => 1,
                'AKTIVA LANCAR LAINNYA' => 2,
                'AKTIVA TETAP' => 3,
                default => 99,
            };
        }

        return match ($section) {
            'KEWAJIBAN LANCAR' => 1,
            'EKUITAS' => 2,
            'KEWAJIBAN JANGKA PANJANG' => 3,
            default => 99,
        };
    }

    private function applyAktFormula(string $code, string $side): string
    {
        $prefix7 = substr($code, 0, 7);
        $prefix8 = substr($code, 0, 8);

        if ($side === 'left') {
            return match (true) {
                str_starts_with($code, '111.101') => 'KAS DAN BANK',
                $prefix7 === '111.102' => 'KAS DAN BANK',
                $prefix7 === '111.105' => 'KAS DAN BANK',
                $prefix7 === '111.106' => 'KAS DAN BANK',
                $prefix7 === '111.200' => 'PIUTANG DAGANG',
                $prefix7 === '111.300' => 'BIAYA DIBAYAR DIMUKA',
                $prefix7 === '111.400' => 'PERSEDIAAN',
                $prefix7 === '112.100' => 'PIUTANG LAIN - LAIN',
                $prefix7 === '112.200' => 'BIAYA DIBAYAR DIMUKA',
                $prefix7 === '121.200' => 'AKTIVA DALAM PROSES',
                $prefix7 === '121.102' => 'AKTIVA TETAP',
                $prefix7 === '121.101' => 'AKTIVA TETAP',
                $prefix7 === '131.100' => 'AKM PNY AKTIVA TETAP',
                $prefix7 === '121.103' => 'AKM PNY AKTIVA TETAP',
                $prefix7 === '112.300' => 'PAJAK DIBAYAR DIMUKA',
                default => '3',
            };
        }

        return match (true) {
            $prefix8 === '211.100.' => 'HUTANG DAGANG',
            $prefix7 === '211.200' => 'KEWAJIBAN LANCAR LAINNYA',
            $prefix7 === '211.300' => 'KEWAJIBAN LANCAR LAINNYA',
            $prefix7 === '212.100' => 'KEWAJIBAN LANCAR LAINNYA',
            $prefix7 === '211.500' => 'KEWAJIBAN LANCAR LAINNYA',
            $prefix7 === '211.400' => 'HUTANG PAJAK',
            $prefix7 === '311.200' => 'MODAL',
            $prefix7 === '222.000' => 'HUTANG DAGANG',
            $prefix7 === '311.100' => 'MODAL',
            $prefix7 === '312.000' => 'LABA (RUGI) DITAHAN',
            $prefix7 === '313.000' => 'LABA (RUGI) DITAHAN',
            $prefix7 === '314.000' => 'LABA (RUGI) DITAHAN',
            $prefix7 === '312.100' => 'LABA (RUGI) DITAHAN',
            default => '2',
        };
    }

    private function applyGrpAktvFormula(string $subSection, string $side): string
    {
        if ($side === 'left') {
            return match (true) {
                $subSection === 'KAS DAN BANK' => 'AKTIVA LANCAR',
                $subSection === 'PERSEDIAAN' => 'AKTIVA LANCAR',
                $subSection === 'PIUTANG DAGANG' => 'AKTIVA LANCAR',
                $subSection === 'PIUTANG LAIN - LAIN' => 'AKTIVA LANCAR LAINNYA',
                $subSection === 'BIAYA DIBAYAR DIMUKA' => 'AKTIVA LANCAR LAINNYA',
                str_contains($subSection, 'PAJAK DIBAYAR') => 'AKTIVA LANCAR LAINNYA',
                str_contains($subSection, 'AKTIVA DALAM PROSES') => 'AKTIVA LANCAR LAINNYA',
                $subSection === 'AKTIVA TETAP' => 'AKTIVA TETAP',
                $subSection === 'AKM PNY AKTIVA TETAP' => 'AKTIVA TETAP',
                default => ' ',
            };
        }

        return match (true) {
            $subSection === 'HUTANG DAGANG' => 'KEWAJIBAN LANCAR',
            $subSection === 'KEWAJIBAN LANCAR LAINNYA' => 'KEWAJIBAN LANCAR',
            $subSection === 'HUTANG PAJAK' => 'KEWAJIBAN LANCAR',
            $subSection === 'HUTANG JANGKA PANJANG' => 'KEWAJIBAN JANGKA PANJANG',
            $subSection === 'MODAL' => 'EKUITAS',
            $subSection === 'LABA (RUGI) DITAHAN' => 'EKUITAS',
            default => 'KEWAJIBAN JANGKA PANJANG',
        };
    }

    private function applyNamaFormula(string $code, string $name, string $company, string $side): string
    {
        if ($side === 'right') {
            return $name;
        }

        $prefix7 = substr($code, 0, 7);
        $prefix9 = substr($code, 0, 9);

        return match (true) {
            $prefix9 === '111.400.1' => 'BAHAN BAKU',
            $code === '111.400.411' => 'BAHAN BAKU',
            $prefix9 === '111.400.9' => 'PERSEDIAN LAINNYA',
            $prefix9 === '111.400.4' && $company === 'RU' => 'BARANG & BAHAN PENOLONG',
            $code === '111.400.202' => 'WIP',
            $code === '111.400.203' => 'WIP',
            $code === '111.400.204' => 'WIP',
            $code === '111.400.205' => 'WIP BROKER',
            $code === '111.400.402' => 'BD - '.$name,
            $code === '111.400.403' => 'BD - '.$name,
            $code === '111.400.415' => 'BARANG & BAHAN PENOLONG',
            $code === '111.400.414' => 'BARANG & BAHAN PENOLONG',
            $code === '111.400.416' => 'BARANG & BAHAN PENOLONG',
            $code === '111.400.417' => 'BARANG & BAHAN PENOLONG',
            $code === '111.400.207' => 'KOMPONEN FURNITURE',
            $code === '111.400.407' => 'KOMPONEN FURNITURE',
            $code === '111.400.413' => 'BARANG & BAHAN PENOLONG',
            $code === '111.400.412' => 'BARANG & BAHAN PENOLONG',
            $prefix7 === '111.400.3' => 'BJ - '.$name,
            $prefix7 === '111.400.5' => 'BD - '.$name,
            default => $name,
        };
    }

    private function applyEndingFormula(string $code, float $ending, string $side): float
    {
        $prefix7 = substr($code, 0, 7);

        if ($side === 'left') {
            if ($prefix7 === '121.103' || $prefix7 === '131.100') {
                return $ending * -1;
            }
            return $ending;
        }

        if ($code === '212.100.108') {
            return $ending * -1;
        }

        return $ending;
    }

    private function applyUrutFormula(string $subSection, string $side): string
    {
        if ($side === 'left') {
            return match ($subSection) {
                'KAS DAN BANK' => '1',
                'PIUTANG DAGANG' => '2',
                'PERSEDIAAN' => '3',
                'PIUTANG LAIN - LAIN' => '4',
                'BIAYA DIBAYAR DIMUKA' => '5',
                'AKTIVA DALAM PROSES' => '6',
                'PAJAK DIBAYAR DIMUKA' => '7',
                'AKTIVA TETAP' => '8',
                'AKM PNY AKTIVA TETAP' => '9',
                default => '99',
            };
        }

        return match ($subSection) {
            'HUTANG DAGANG' => '1',
            'KEWAJIBAN LANCAR LAINNYA' => '2',
            'HUTANG PAJAK' => '3',
            'MODAL' => '4',
            'LABA (RUGI) DITAHAN' => '5',
            'HUTANG JANGKA PANJANG' => '6',
            default => '99',
        };
    }

    private function applyUrutanNama(string $code, string $name, string $displayName): int
    {
        $upper = strtoupper($name);

        if (str_contains($upper, 'KAS KECIL')) return 1;
        if (str_contains($upper, 'KAS BESAR')) return 2;
        if (str_contains($upper, 'KAS GANTUNG')) return 3;
        if (str_contains($upper, 'KAS')) {
            $suffix = substr($code, -5);
            $num = is_numeric($suffix) ? (int) $suffix : 0;
            return max(4, $num);
        }

        if (stripos($displayName, 'bank garansi') !== false) return 7;

        return 6;
    }
}
