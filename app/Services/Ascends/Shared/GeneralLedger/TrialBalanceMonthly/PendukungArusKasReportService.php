<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalanceMonthly;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PendukungArusKasReportService
{
    private const TITLE = 'Laporan Pendukung Arus Kas';

    private const NAME_GROUP = [
        'A' => 'KAS DAN BANK',
        'B' => 'PIUTANG DAGANG',
        'C' => 'BIAYA DI BAYAR DIMUKA',
        'D' => 'PL - HUBUNGAN ISTIMEWA',
        'E' => 'PL - PIHAK KETIGA',
        'F' => 'AKTIVA TETAP',
        'G' => 'AKM PENY. AKTIVA TETAP',
        'H' => 'TANAH DALAM PROSES',
        'I' => 'KONTRUKSI DALAM PROSES',
        'J' => 'HUTANG DAGANG',
        'K' => 'BEBAN YANG MASIH HARUS DI BAYAR',
        'L' => 'KEWAJIBAN LANCAR LAINNYA',
        'M' => 'MODAL',
        'N' => 'LABA (RUGI) DITAHAN',
        'O' => 'HUTANG JANGKA PANJANG',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $periodStart = trim((string) ($filters['PeriodStart'] ?? ''));
        $periodEnd = trim((string) ($filters['PeriodEnd'] ?? ''));

        $filtered = $this->applySelectionFormula($allRows);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        [$periodStart, $periodEnd] = $this->resolvePeriodRange($filtered, $periodStart, $periodEnd);

        $startLabel = $this->resolvePeriodLabel($periodStart);
        $endLabel = $this->resolvePeriodLabel($periodEnd);

        $grouped = $this->groupBySection($filtered, $periodStart, $periodEnd);

        $sections = [];

        foreach (range('A', 'O') as $letter) {
            $letter = (string) $letter;
            if (! isset($grouped[$letter]) || $grouped[$letter]['items'] === []) {
                continue;
            }

            $sectionName = self::NAME_GROUP[$letter] ?? $letter;
            $items = $grouped[$letter]['items'];
            $subtotalStart = 0.0;
            $subtotalEnd = 0.0;

            foreach ($items as &$item) {
                $subtotalStart += $item['amount_start'];
                $subtotalEnd += $item['amount_end'];
            }

            $selisih = $subtotalStart - $subtotalEnd;

            $sections[] = [
                'section_code' => $letter,
                'section_name' => $sectionName,
                'items' => $items,
                'subtotal_start' => $subtotalStart,
                'subtotal_end' => $subtotalEnd,
                'selisih' => $selisih,
            ];
        }

        $grandStart = array_sum(array_column($sections, 'subtotal_start'));
        $grandEnd = array_sum(array_column($sections, 'subtotal_end'));
        $grandSelisih = $grandStart - $grandEnd;

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => ($startLabel !== '' && $endLabel !== '')
                ? 'Periode : '.$startLabel.' s.d '.$endLabel
                : '',
            'period_start_label' => $startLabel,
            'period_end_label' => $endLabel,
            'sections' => $sections,
            'grand_start' => $grandStart,
            'grand_end' => $grandEnd,
            'grand_selisih' => $grandSelisih,
            'grand_selisih_neg' => $grandSelisih < 0,
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
            $ac1 = (string) ($row['AccountCode1'] ?? '');
            $groupLetter = $this->computeGroupTotal($ac1);

            return $groupLetter !== 'z';
        }));
    }

    private function computeGroupTotal(string $ac1): string
    {
        if ($ac1 === '111.102.921') {
            return 'O';
        }

        $prefix7 = substr($ac1, 0, 7);
        $prefix9 = substr($ac1, 0, 9);

        if (in_array($prefix7, ['111.101', '111.102', '111.103', '121.300'], true)) {
            return 'A';
        }

        if ($prefix7 === '111.200') {
            return 'B';
        }

        if ($prefix7 === '112.200') {
            return 'C';
        }

        if ($prefix9 === '112.100.1') {
            return 'D';
        }

        if ($prefix9 === '112.100.2') {
            return 'E';
        }

        if (in_array($prefix7, ['121.101', '121.102'], true)) {
            return 'F';
        }

        if ($prefix7 === '121.103') {
            return 'G';
        }

        if ($prefix7 === '121.201') {
            return 'H';
        }

        if ($prefix7 === '121.202') {
            return 'I';
        }

        if ($prefix7 === '211.100') {
            return 'J';
        }

        if ($prefix7 === '211.300') {
            return 'K';
        }

        if ($prefix7 === '212.100') {
            return 'L';
        }

        if ($prefix7 === '311.100') {
            return 'M';
        }

        if (in_array($prefix7, ['312.000', '313.000', '314.000'], true)) {
            return 'N';
        }

        if ($prefix7 === '221.100') {
            return 'O';
        }

        return 'z';
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

    private function groupBySection(array $rows, string $periodStart, string $periodEnd): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $ac1 = (string) ($row['AccountCode1'] ?? '');
            $an1 = (string) ($row['AccountName1'] ?? '');
            $ending = (float) ($row['Ending'] ?? 0);
            $ending = round($ending, 2);
            $periodDate = (string) ($row['PeriodDate'] ?? '');

            $groupLetter = $this->computeGroupTotal($ac1);
            if ($groupLetter === 'z') {
                continue;
            }

            $periodMonth = '';
            try {
                $periodMonth = Carbon::parse($periodDate)->format('Y-m');
            } catch (Throwable) {
                continue;
            }

            $isStartPeriod = $periodMonth === $periodStart;
            $isEndPeriod = $periodMonth === $periodEnd;

            if (! $isStartPeriod && ! $isEndPeriod) {
                continue;
            }

            $key = $ac1;

            if (! isset($groups[$groupLetter])) {
                $groups[$groupLetter] = [
                    'letter' => $groupLetter,
                    'items' => [],
                ];
            }

            if (! isset($groups[$groupLetter]['items'][$key])) {
                $groups[$groupLetter]['items'][$key] = [
                    'account_code' => $ac1,
                    'account_name' => $an1,
                    'amount_start' => 0.0,
                    'amount_end' => 0.0,
                ];
            }

            if ($isStartPeriod) {
                $groups[$groupLetter]['items'][$key]['amount_start'] += $ending;
            }

            if ($isEndPeriod) {
                $groups[$groupLetter]['items'][$key]['amount_end'] += $ending;
            }
        }

        foreach ($groups as $letter => &$group) {
            uasort($group['items'], fn ($a, $b) => strcmp($a['account_code'], $b['account_code']));
            $group['items'] = array_values($group['items']);
        }

        return $groups;
    }
}
