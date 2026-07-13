<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalanceMonthly;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class ArusKasRuReportService
{
    private const TITLE = 'Laporan Arus Kas';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $periodStart = trim((string) ($filters['PeriodStart'] ?? ''));
        $periodEnd = trim((string) ($filters['PeriodEnd'] ?? ''));

        [$periodStart, $periodEnd] = $this->resolvePeriodRange($allRows, $periodStart, $periodEnd);

        if ($periodStart === '' || $periodEnd === '') {
            throw new RuntimeException('Periode laporan tidak dapat ditentukan dari data XML.');
        }

        $startLabel = $this->resolvePeriodLabel($periodStart);
        $endLabel = $this->resolvePeriodLabel($periodEnd);

        $filtered = $this->applyTampilKas($allRows);

        $sections = $this->buildSections($filtered, $allRows, $periodStart, $periodEnd);
        $totalOperasi = $this->sumSectionItems($sections, 1);
        $totalInvestasi = $this->sumSectionItems($sections, 2);
        $totalPendanaan = $this->sumSectionItems($sections, 3);
        $totalKeseluruhan = $totalOperasi + $totalInvestasi + $totalPendanaan;

        $openingBalance = $this->calculateCashBalance($allRows, $periodStart);
        $closingBalance = $this->calculateCashBalance($allRows, $periodEnd);
        $crossCek = $closingBalance;
        $selisih = $closingBalance - $totalKeseluruhan - $openingBalance;

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => 'Periode : '.$endLabel,
            'period_start_label' => $startLabel,
            'period_end_label' => $endLabel,
            'sections' => $sections,
            'total_operasi' => $totalOperasi,
            'total_investasi' => $totalInvestasi,
            'total_pendanaan' => $totalPendanaan,
            'total_keseluruhan' => $totalKeseluruhan,
            'opening_balance_label' => strtoupper($startLabel),
            'opening_balance' => $openingBalance,
            'closing_balance_label' => strtoupper($endLabel),
            'closing_balance' => $closingBalance,
            'cross_cek' => $crossCek,
            'selisih' => $selisih,
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

    private function resolvePeriodRange(array $rows, string $periodStart, string $periodEnd): array
    {
        if ($periodStart !== '' && $periodEnd !== '') {
            return [$periodStart, $periodEnd];
        }

        $months = [];
        foreach ($rows as $row) {
            $pd = trim((string) ($row['PeriodDate'] ?? ''));
            if ($pd === '') {
                continue;
            }

            try {
                $months[] = Carbon::parse($pd)->format('Y-m');
            } catch (Throwable) {
                continue;
            }
        }

        if ($months === []) {
            return [$periodStart, $periodEnd];
        }

        $months = array_unique($months);
        sort($months);

        if ($periodStart === '') {
            $periodStart = $months[0];
        }

        if ($periodEnd === '') {
            $periodEnd = $months[count($months) - 1];
        }

        return [$periodStart, $periodEnd];
    }

    private function resolvePeriodLabel(string $period): string
    {
        if ($period === '') {
            return '';
        }

        try {
            $date = Carbon::parse($period.'-01');

            return $date->locale('id')->isoFormat('MMM-YY');
        } catch (Throwable) {
            return $period;
        }
    }

    private function applyTampilKas(array $rows): array
    {
        return array_values(array_filter($rows, fn (array $row): bool => $this->tampilKas(
            (string) ($row['AccountCode1'] ?? '')
        )));
    }

    private function tampilKas(string $ac1): bool
    {
        $left7 = substr($ac1, 0, 7);
        $left9 = substr($ac1, 0, 9);

        if ($left9 === '111.200.3') {
            return true;
        }

        if ($left9 === '111.200.2') {
            return true;
        }

        if ($left9 === '111.300.2') {
            return true;
        }

        if ($left7 === '111.400') {
            return true;
        }

        if ($left7 === '112.200') {
            return true;
        }

        if ($left7 === '112.100') {
            return true;
        }

        if ($left7 === '211.100') {
            return true;
        }

        if ($left7 === '211.400') {
            return true;
        }

        if ($left7 === '212.100') {
            return true;
        }

        if ($left7 === '211.200') {
            return true;
        }

        if ($left7 === '211.300') {
            return true;
        }

        if ($left7 === '121.102') {
            return true;
        }

        if ($left7 === '121.101') {
            return true;
        }

        if ($left7 === '121.200') {
            return true;
        }

        if ($left7 === '310.200') {
            return true;
        }

        return false;
    }

    private function getEndingForPeriod(array $rows, string $ac1, string $period): float
    {
        foreach ($rows as $row) {
            if ((string) ($row['AccountCode1'] ?? '') !== $ac1) {
                continue;
            }

            try {
                $rowPeriod = Carbon::parse((string) ($row['PeriodDate'] ?? ''))->format('Y-m');
            } catch (Throwable) {
                continue;
            }

            if ($rowPeriod === $period) {
                return (float) ($row['Ending'] ?? 0);
            }
        }

        return 0.0;
    }

    private function cashFlowImpact(array $rows, string $ac1, string $periodStart, string $periodEnd, bool $liability = false): float
    {
        $startVal = $this->getEndingForPeriod($rows, $ac1, $periodStart);
        $endVal = $this->getEndingForPeriod($rows, $ac1, $periodEnd);

        return $liability ? $endVal - $startVal : $startVal - $endVal;
    }

    private function groupItemsBySection(array $filtered, string $periodStart, string $periodEnd): array
    {
        $ac1Sections = [];

        $ac1Sections['1.2'] = ['111.200', '111.300'];
        $ac1Sections['1.3'] = ['111.400'];
        $ac1Sections['1.4'] = ['112.200'];
        $ac1Sections['1.7'] = ['211.100'];
        $ac1Sections['1.8'] = ['211.400'];
        $ac1Sections['1.9'] = ['212.100', '211.200', '211.300'];
        $ac1Sections['2.1'] = ['121.101', '121.102', '121.200'];

        $sections = [];
        $sectionCodes = ['1.2', '1.3', '1.4', '1.7', '1.8', '1.9', '2.1'];

        $sectionLabels = [
            '1.2' => 'Penurunan (Kenaikan) Piutang Dagang',
            '1.3' => 'Penurunan (Kenaikan) Persediaan',
            '1.4' => 'Penurunan (Kenaikan) Biaya Dibayar Dimuka',
            '1.7' => 'Kenaikan (Penurunan) Hutang Dagang',
            '1.8' => 'Kenaikan (Penurunan) Beban Yang Masih Harus Dibayar',
            '1.9' => 'Kenaikan (Penurunan) Kewajiban Lancar Lainnya',
            '2.1' => 'Penurunan (Kenaikan) Aktiva Tetap',
        ];

        $totalLabels = [
            '1.2' => 'Total Arus Kas Dari Penurunan (Kenaikan) Piutang Dagang',
            '1.3' => 'Total Arus Kas Dari Penurunan (Kenaikan) Persediaan',
            '1.4' => 'Total Arus Kas Dari Penurunan (Kenaikan) Biaya Dibayar Dimuka',
            '1.7' => 'Total Arus Kas Dari Kenaikan (Penurunan) Hutang Dagang',
            '1.8' => 'Total Arus Kas Dari Kenaikan (Penurunan) Beban Yang Masih Harus Dibayar',
            '1.9' => 'Total Arus Kas Dari Kenaikan (Penurunan) Kewajiban Lancar Lainnya',
            '2.1' => 'Total Arus Kas Dari Penurunan (Kenaikan) Aktiva Tetap',
        ];

        $liabilitySections = ['1.7', '1.8', '1.9'];

        foreach ($sectionCodes as $code) {
            $prefixes = $ac1Sections[$code];
            $items = [];

            foreach ($filtered as $row) {
                $ac1 = (string) ($row['AccountCode1'] ?? '');
                $matched = false;

                foreach ($prefixes as $prefix) {
                    if (str_starts_with($ac1, $prefix)) {
                        $matched = true;
                        break;
                    }
                }

                if (! $matched) {
                    continue;
                }

                $already = false;
                foreach ($items as $existing) {
                    if ($existing['account_code'] === $ac1) {
                        $already = true;
                        break;
                    }
                }

                if (! $already) {
                    $isLiability = in_array($code, $liabilitySections, true);
                    $impact = $this->cashFlowImpact($filtered, $ac1, $periodStart, $periodEnd, $isLiability);
                    $name = (string) ($row['AccountName1'] ?? '');
                    $items[] = [
                        'account_code' => $ac1,
                        'account_name' => $name,
                        'amount' => $impact,
                    ];
                }
            }

            $total = array_sum(array_column($items, 'amount'));

            $sections[$code] = [
                'code' => $code,
                'label' => $sectionLabels[$code],
                'total_label' => $totalLabels[$code],
                'items' => $items,
                'total' => $total,
            ];
        }

        return $sections;
    }

    private function buildSections(array $filtered, array $allRows, string $periodStart, string $periodEnd): array
    {
        $sectionGroups = $this->groupItemsBySection($filtered, $periodStart, $periodEnd);

        $labaBersih = $this->getEndingForPeriod($allRows, '314.000.000', $periodEnd);
        $penyusutan = $this->getEndingForPeriod($allRows, '500.001.000', $periodEnd);

        $operasiItems = [];
        $operasiItems[] = [
            'type' => 'sub_header',
            'label' => '1.1 Laba Bersih',
            'code' => '1.1',
            'amount' => $labaBersih,
        ];
        $operasiItems[] = [
            'type' => 'item',
            'label' => 'BU - Penyusutan',
            'account_code' => '',
            'amount' => $penyusutan,
        ];

        $subSections = ['1.2', '1.3', '1.4', '1.7', '1.8', '1.9'];
        $operasiSubtotal = $labaBersih + $penyusutan;

        foreach ($subSections as $code) {
            if (! isset($sectionGroups[$code])) {
                continue;
            }

            $sec = $sectionGroups[$code];
            $operasiItems[] = [
                'type' => 'sub_header',
                'label' => $code.' '.$sec['label'],
                'code' => $code,
            ];

            foreach ($sec['items'] as $item) {
                $operasiItems[] = [
                    'type' => 'item',
                    'label' => $item['account_name'],
                    'account_code' => $item['account_code'],
                    'amount' => $item['amount'],
                ];
            }

            $operasiItems[] = [
                'type' => 'total',
                'label' => $sec['total_label'],
                'code' => $code,
                'amount' => $sec['total'],
            ];

            $operasiSubtotal += $sec['total'];
        }

        $investasiItems = [];
        $investasiSubSections = ['2.1'];
        foreach ($investasiSubSections as $code) {
            if (! isset($sectionGroups[$code])) {
                continue;
            }

            $sec = $sectionGroups[$code];
            $investasiItems[] = [
                'type' => 'sub_header',
                'label' => $code.' '.$sec['label'],
                'code' => $code,
            ];

            foreach ($sec['items'] as $item) {
                $investasiItems[] = [
                    'type' => 'item',
                    'label' => $item['account_name'],
                    'account_code' => $item['account_code'],
                    'amount' => $item['amount'],
                ];
            }

            $investasiItems[] = [
                'type' => 'total',
                'label' => $sec['total_label'],
                'code' => $code,
                'amount' => $sec['total'],
            ];
        }

        $investasiTotal = 0.0;
        foreach ($investasiSubSections as $s) {
            $investasiTotal += $sectionGroups[$s]['total'] ?? 0;
        }

        $pendanaanItems = [];
        $pendanaanTotal = 0.0;

        $sections = [];
        $sections[] = [
            'header' => '1 Arus Kas Dari Aktivitas Operasi',
            'code' => '1',
            'items' => $operasiItems,
            'subtotal_label' => 'Total Arus Kas Dari Aktivitas Operasi',
            'subtotal' => $operasiSubtotal,
        ];

        $sections[] = [
            'header' => '2 Arus Kas Dari Aktivitas Investasi',
            'code' => '2',
            'items' => $investasiItems,
            'subtotal_label' => 'Total Arus Kas Dari Aktivitas Investasi',
            'subtotal' => $investasiTotal,
        ];

        $sections[] = [
            'header' => '3 Arus Kas Dari Aktivitas Pendanaan',
            'code' => '3',
            'items' => $pendanaanItems,
            'subtotal_label' => 'Total Arus Kas Dari Aktivitas Pendanaan',
            'subtotal' => $pendanaanTotal,
        ];

        return $sections;
    }

    private function sumSectionItems(array $sections, int $sectionIndex): float
    {
        foreach ($sections as $sec) {
            if ((int) $sec['code'] === $sectionIndex) {
                return (float) ($sec['subtotal'] ?? 0);
            }
        }

        return 0.0;
    }

    private function calculateCashBalance(array $rows, string $period): float
    {
        $cashPrefixes = ['111.101', '111.102', '111.103'];
        $total = 0.0;
        $processed = [];

        foreach ($rows as $row) {
            $ac1 = (string) ($row['AccountCode1'] ?? '');
            $matched = false;

            foreach ($cashPrefixes as $prefix) {
                if (str_starts_with($ac1, $prefix)) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                continue;
            }

            if (!in_array($ac1, $processed, true)) {
                $total += $this->getEndingForPeriod($rows, $ac1, $period);
                $processed[] = $ac1;
            }
        }

        return $total;
    }
}
