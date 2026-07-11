<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalance;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class RingkasanHutangBankReportService
{
    private const TITLE = 'Laporan Ringkasan Hutang Bank';

    private const GROUP_ORDER = [
        'RINGKASAN HUTANG BANK',
        'RINGKASAN HUTANG ISTIMEWA',
        'RINGKASAN HUTANG LAIN-LAIN',
        'RINGKASAN PINJAMAN KARYAWAN',
        'RINGKASAN PIUTANG UC - GSU',
        'RINGKASAN PIUTANG UC - RU',
        'RINGKASAN PIUTANG UC - PSU',
        'RINGKASAN PIUTANG UC - PU',
        'RINGKASAN PIUTANG UC - CU',
    ];

    private const BANK_ACCOUNTS = [
        '221.100.202', '221.100.203', '221.100.204',
        '221.100.208', '221.100.209', '221.100.210',
        '221.100.303', '221.100.304', '221.100.306',
        '221.100.307', '221.100.308',
    ];

    private const ISTIMEWA_ACCOUNTS = [
        '221.100.201', '221.100.205', '221.100.206',
        '221.100.207', '221.100.301', '221.100.302',
    ];

    private const LAIN_ACCOUNTS = [
        '211.300.011', '211.300.180', '211.300.081',
        '211.300.027', '211.300.171', '211.300.211',
        '212.100.216', '212.100.217', '212.100.219',
        '212.100.220', '212.100.401', '212.100.402',
        '212.100.403',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $dateStart = trim((string) ($filters['DateStart'] ?? ''));
        $dateEnd = trim((string) ($filters['DateEnd'] ?? ''));
        $periodLabel = $this->resolvePeriodLabel($dateStart, $dateEnd);

        $filtered = $this->applySelectionFormula($allRows);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data Ringkasan Hutang Bank.');
        }

        $grouped = $this->groupByGroup($filtered);

        $groups = [];
        $grandBeginning = 0.0;
        $grandDebit = 0.0;
        $grandCredit = 0.0;
        $grandEnding = 0.0;

        foreach (self::GROUP_ORDER as $groupName) {
            if (! isset($grouped[$groupName])) {
                continue;
            }

            $items = $grouped[$groupName]['items'];
            $subtotalBeginning = array_sum(array_column($items, 'beginning'));
            $subtotalDebit = array_sum(array_column($items, 'debit'));
            $subtotalCredit = array_sum(array_column($items, 'credit'));
            $subtotalEnding = array_sum(array_column($items, 'ending'));

            $groups[] = [
                'name' => $groupName,
                'items' => $items,
                'subtotal_beginning' => round($subtotalBeginning, 2),
                'subtotal_debit' => round($subtotalDebit, 2),
                'subtotal_credit' => round($subtotalCredit, 2),
                'subtotal_ending' => round($subtotalEnding, 2),
            ];

            $grandBeginning += $subtotalBeginning;
            $grandDebit += $subtotalDebit;
            $grandCredit += $subtotalCredit;
            $grandEnding += $subtotalEnding;
        }

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'groups' => $groups,
            'grand_beginning' => round($grandBeginning, 2),
            'grand_debit' => round($grandDebit, 2),
            'grand_credit' => round($grandCredit, 2),
            'grand_ending' => round($grandEnding, 2),
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

            if ($ac1 === '') {
                return false;
            }

            $group = $this->getGroup($ac1);

            return $group !== 'A';
        }));
    }

    private function getGroup(string $ac1): string
    {
        if (in_array($ac1, self::BANK_ACCOUNTS, true)) {
            return 'RINGKASAN HUTANG BANK';
        }

        if (in_array($ac1, self::ISTIMEWA_ACCOUNTS, true)) {
            return 'RINGKASAN HUTANG ISTIMEWA';
        }

        if (in_array($ac1, self::LAIN_ACCOUNTS, true)) {
            return 'RINGKASAN HUTANG LAIN-LAIN';
        }

        if (str_starts_with($ac1, '112.100.3') || str_starts_with($ac1, '112.100.9')) {
            return 'RINGKASAN PINJAMAN KARYAWAN';
        }

        if (in_array($ac1, ['111.200.101', '112.100.111', '112.100.112'], true)) {
            return 'RINGKASAN PIUTANG UC - GSU';
        }

        if ($ac1 === '111.200.102' || str_starts_with($ac1, '112.100.12')) {
            return 'RINGKASAN PIUTANG UC - RU';
        }

        if ($ac1 === '111.200.103' || str_starts_with($ac1, '112.100.13')) {
            return 'RINGKASAN PIUTANG UC - PSU';
        }

        if ($ac1 === '111.200.104' || str_starts_with($ac1, '112.100.15')) {
            return 'RINGKASAN PIUTANG UC - PU';
        }

        if ($ac1 === '111.200.105' || str_starts_with($ac1, '112.100.14')) {
            return 'RINGKASAN PIUTANG UC - CU';
        }

        return 'A';
    }

    private function resolvePeriodLabel(string $dateStart, string $dateEnd): string
    {
        try {
            $start = $dateStart !== '' ? Carbon::parse($dateStart) : null;
            $end = $dateEnd !== '' ? Carbon::parse($dateEnd) : null;

            $startLabel = $start ? $start->locale('id')->isoFormat('DD-MMM-YY') : '';
            $endLabel = $end ? $end->locale('id')->isoFormat('DD-MMM-YY') : '';

            if ($startLabel !== '' && $endLabel !== '') {
                return 'Dari '.$startLabel.' s/d '.$endLabel;
            }

            return $endLabel !== '' ? 'Per Tanggal '.$endLabel : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function groupByGroup(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $ac1 = (string) ($row['AccountCode1'] ?? '');
            $an1 = (string) ($row['AccountName1'] ?? '');
            $debit = (float) ($row['Mutation Debit'] ?? 0);
            $credit = (float) ($row['Mutation Credit'] ?? 0);
            $beginning = (float) ($row['Beginning'] ?? 0);
            $ending = (float) ($row['Ending'] ?? 0);

            $groupName = $this->getGroup($ac1);

            if (! isset($grouped[$groupName])) {
                $grouped[$groupName] = ['items' => []];
            }

            $key = $ac1;
            if (! isset($grouped[$groupName]['items'][$key])) {
                $grouped[$groupName]['items'][$key] = [
                    'account_code' => $ac1,
                    'account_name' => $an1,
                    'debit' => 0.0,
                    'credit' => 0.0,
                    'beginning' => 0.0,
                    'ending' => 0.0,
                ];
            }

            $grouped[$groupName]['items'][$key]['debit'] += $debit;
            $grouped[$groupName]['items'][$key]['credit'] += $credit;
            $grouped[$groupName]['items'][$key]['beginning'] += $beginning;
            $grouped[$groupName]['items'][$key]['ending'] += $ending;
        }

        foreach ($grouped as $groupName => &$group) {
            uasort($group['items'], fn ($a, $b) => strcmp($a['account_code'], $b['account_code']));

            foreach ($group['items'] as &$item) {
                $item['debit'] = round($item['debit'], 2);
                $item['credit'] = round($item['credit'], 2);
                $item['beginning'] = round($item['beginning'], 2);
                $item['ending'] = round($item['ending'], 2);
            }

            $group['items'] = array_values($group['items']);
        }

        return $grouped;
    }
}
