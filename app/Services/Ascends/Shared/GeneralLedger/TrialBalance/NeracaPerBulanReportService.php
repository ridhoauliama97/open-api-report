<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalance;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class NeracaPerBulanReportService
{
    private const TITLE = 'Neraca';

    private const SECTIONS_UC = [
        'assets' => [
            'AKTIVA LANCAR' => [
                'KAS DAN BANK' => ['111.101', '111.102', '111.103', '121.300'],
                'PIUTANG DAGANG' => ['111.200'],
            ],
            'AKTIVA LANCAR LAINNYA' => [
                'BIAYA DIBAYAR DIMUKA' => ['112.200'],
                'PL - HUBUNGAN ISTIMEWA' => ['112.100'],
            ],
            'AKTIVA TIDAK LANCAR' => [
                'AKTIVA TETAP' => ['121.101', '121.102'],
                'AKM PNY AKTIVA TETAP' => ['121.103'],
                'TANAH DALAM PROSES' => ['121.201'],
                'KONTRUKSI DALAM PROSES' => ['121.202'],
            ],
        ],
        'liabilities_equity' => [
            'KEWAJIBAN LANCAR' => [
                'HUTANG DAGANG' => ['211.100'],
                'BEBAN YANG MASIH HARUS DI BAYAR' => ['211.300'],
                'KEWAJIBAN LANCAR LAINNYA' => ['212.100'],
            ],
            'HUTANG JANGKA PANJANG' => [
                'HUTANG JANGKA PANJANG' => ['221.100'],
            ],
            'EKUITAS' => [
                'LABA (RUGI) DITAHAN' => ['312.000', '313.000', '314.000'],
                'MODAL' => ['311.100'],
            ],
        ],
    ];

    private const SECTIONS_RU = [
        'assets' => [
            'AKTIVA LANCAR' => [
                'KAS DAN BANK' => ['111.101', '111.102', '111.103'],
                'PERSEDIAAN' => ['111.400'],
                'PIUTANG DAGANG' => ['111.200'],
            ],
            'AKTIVA LANCAR LAINNYA' => [
                'BIAYA DIBAYAR DIMUKA' => ['112.200', '111.300'],
                'PL - HUBUNGAN ISTIMEWA' => ['112.100'],
            ],
            'AKTIVA TIDAK LANCAR' => [
                'AKTIVA TETAP' => ['121.101', '121.102'],
                'AKM PNY AKTIVA TETAP' => ['121.103'],
                'TANAH DALAM PROSES' => ['121.201'],
                'KONTRUKSI DALAM PROSES' => ['121.202'],
            ],
        ],
        'liabilities_equity' => [
            'KEWAJIBAN LANCAR' => [
                'HUTANG DAGANG' => ['211.100'],
                'HUTANG PAJAK' => ['211.400'],
                'KEWAJIBAN LANCAR LAINNYA' => ['211.300', '212.100', '212.200', '211.200'],
            ],
            'HUTANG JANGKA PANJANG' => [
                'HUTANG JANGKA PANJANG' => ['221.100'],
            ],
            'EKUITAS' => [
                'LABA (RUGI) DITAHAN' => ['312.000', '313.000', '314.000'],
                'MODAL' => ['311.100', '311.200'],
            ],
        ],
    ];

    private const SECTIONS_GSU = [
        'assets' => [
            'AKTIVA LANCAR' => [
                'KAS DAN BANK' => ['111.101', '111.102', '111.103', '111.105'],
                'PERSEDIAAN' => ['111.400'],
                'PIUTANG DAGANG' => ['111.200'],
            ],
            'AKTIVA LANCAR LAINNYA' => [
                'BIAYA DIBAYAR DIMUKA' => ['112.200', '111.300'],
                'PAJAK DIBAYAR DIMUKA' => ['112.300'],
                'PL - HUBUNGAN ISTIMEWA' => ['112.100'],
            ],
            'AKTIVA TIDAK LANCAR' => [
                'AKTIVA TETAP' => ['121.101', '121.102'],
                'AKM PNY AKTIVA TETAP' => ['121.103', '131.100'],
            ],
        ],
        'liabilities_equity' => [
            'KEWAJIBAN LANCAR' => [
                'HUTANG DAGANG' => ['211.100', '222.000'],
                'HUTANG PAJAK' => ['211.400'],
                'KEWAJIBAN LANCAR LAINNYA' => ['211.300', '212.100', '212.200', '211.200', '211.500'],
            ],
            'EKUITAS' => [
                'LABA (RUGI) DITAHAN' => ['312.000', '313.000', '314.000'],
                'MODAL' => ['311.100', '311.200'],
            ],
        ],
    ];

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
        $sectionDefs = match ($company) {
            'RU' => self::SECTIONS_RU,
            'GSU' => self::SECTIONS_GSU,
            default => self::SECTIONS_UC,
        };

        $leftSections = $this->buildSide($sectionDefs['assets'], $groupedByAccount, 'left');
        $rightSections = $this->buildSide($sectionDefs['liabilities_equity'], $groupedByAccount, 'right');

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
            $normalBalance = (string) ($row['Normal Balance'] ?? 'Debit');

            if (! isset($groups[$ac1])) {
                $groups[$ac1] = [
                    'account_code' => $ac1,
                    'account_name' => $an1,
                    'balance' => 0.0,
                    'normal_balance' => $normalBalance,
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

    private function buildSide(array $sectionDefs, array $groupedByAccount, string $side): array
    {
        $sections = [];

        foreach ($sectionDefs as $sectionName => $subSections) {
            $builtSubs = [];
            $sectionTotal = 0.0;

            foreach ($subSections as $subName => $prefixes) {
                $items = [];
                $subTotal = 0.0;

                foreach ($groupedByAccount as $ac1 => $data) {
                    if (! $this->matchesAnyPrefix($ac1, $prefixes)) {
                        continue;
                    }

                    $bal = $data['balance'];
                    $normalBalance = $data['normal_balance'] ?? 'Debit';

                    if ($side === 'left' && $normalBalance === 'Credit') {
                        $bal = -$bal;
                    } elseif ($side === 'right' && $normalBalance === 'Debit') {
                        $bal = -$bal;
                    }

                    $items[] = [
                        'account_name' => $data['account_name'],
                        'balance' => $bal,
                    ];
                    $subTotal += $bal;
                }

                $subTotal = round($subTotal, 2);

                $builtSubs[] = [
                    'name' => $subName,
                    'items' => $items,
                    'total' => $subTotal,
                ];

                $sectionTotal += $subTotal;
            }

            $sectionTotal = round($sectionTotal, 2);

            $sections[] = [
                'name' => $sectionName,
                'sub_sections' => $builtSubs,
                'total' => $sectionTotal,
            ];
        }

        return $sections;
    }

    private function matchesAnyPrefix(string $ac1, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($ac1, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
