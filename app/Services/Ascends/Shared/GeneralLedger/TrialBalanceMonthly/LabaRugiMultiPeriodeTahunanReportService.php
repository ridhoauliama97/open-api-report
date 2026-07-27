<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalanceMonthly;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LabaRugiMultiPeriodeTahunanReportService
{
    private const TITLE = 'Laporan Laba Rugi Multi Periode (Tahunan)';

    private const EXCLUDED_PREFIX = '399.999.999';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload'): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $filtered = $this->applySelectionFormula($allRows);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        [$minDate, $maxDate] = $this->resolveDateRange($filtered);
        $periodLabel = 'Periode '.$minDate->locale('id')->isoFormat('MMM-YY').' s/d '.$maxDate->locale('id')->isoFormat('MMM-YY');

        $grouped = $this->groupByAccount($filtered);
        $sections = $this->buildSections($grouped);

        $grandTotal = 0;
        foreach ($sections as $section) {
            $grandTotal += $section['subtotal'];
        }

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'sections' => $sections,
            'grand_total' => $grandTotal,
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

            return ! str_starts_with($ac1, self::EXCLUDED_PREFIX);
        }));
    }

    private function resolveDateRange(array $rows): array
    {
        $min = null;
        $max = null;

        foreach ($rows as $row) {
            $dateStr = (string) ($row['PeriodDate'] ?? '');
            if ($dateStr === '') {
                continue;
            }
            try {
                $d = Carbon::parse($dateStr);
                if ($min === null || $d->lessThan($min)) {
                    $min = $d;
                }
                if ($max === null || $d->greaterThan($max)) {
                    $max = $d;
                }
            } catch (Throwable) {
            }
        }

        return [$min ?? now(), $max ?? now()];
    }

    private function groupByAccount(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $ac1 = (string) ($row['AccountCode1'] ?? '');
            $an1 = (string) ($row['AccountName1'] ?? '');
            $an5 = (string) ($row['AccountName5'] ?? '');
            $balance = (float) ($row['Beginning'] ?? 0) + (float) ($row['Mutation Credit'] ?? 0) - (float) ($row['Mutation Debit'] ?? 0);

            $displayName = $this->resolveDisplayName($an1, $an5);

            $key = "{$ac1}|||{$displayName}";
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'section_code' => $ac1,
                    'section_name' => $an1,
                    'display_name' => $displayName,
                    'total' => 0.0,
                ];
            }

            $groups[$key]['total'] += $balance;
        }

        return array_values($groups);
    }

    private function resolveDisplayName(string $an1, string $an5): string
    {
        return $an5 !== '' ? $an5 : $an1;
    }

    private function buildSections(array $grouped): array
    {
        $sections = [];

        $prefixGroups = [];
        foreach ($grouped as $g) {
            $code = $g['section_code'];
            $prefix = substr($code, 0, 3);
            $prefixGroups[$prefix][] = $g;
        }

        $sortedPrefixes = array_keys($prefixGroups);
        sort($sortedPrefixes);

        foreach ($sortedPrefixes as $prefix) {
            $groups = $prefixGroups[$prefix];

            $codeLengths = array_map(fn ($g) => strlen($g['section_code']), $groups);
            $uniqueLengths = array_unique($codeLengths);

            if (count($uniqueLengths) === 1) {
                $sectionCode = $groups[0]['section_code'];
                $sectionName = $groups[0]['section_name'];
                $subtotal = 0.0;
                $items = [];

                usort($groups, fn ($a, $b) => strcmp($a['display_name'], $b['display_name']));

                foreach ($groups as $g) {
                    $items[] = [
                        'account_name' => $g['display_name'],
                        'amount' => $g['total'],
                    ];
                    $subtotal += $g['total'];
                }

                $sections[] = [
                    'section_code' => $sectionCode,
                    'section_name' => $sectionName,
                    'items' => $items,
                    'subtotal' => $subtotal,
                ];
            } else {
                $header = null;

                foreach ($groups as $g) {
                    $codeLen = strlen($g['section_code']);
                    if ($header === null || $codeLen < strlen($header['section_code'])) {
                        $header = $g;
                    }
                }

                $details = [];
                foreach ($groups as $g) {
                    if ($g['section_code'] === $header['section_code']) {
                        continue;
                    }
                    $details[] = $g;
                }

                usort($details, fn ($a, $b) => strcmp($a['display_name'], $b['display_name']));

                $items = [];
                foreach ($details as $d) {
                    $items[] = [
                        'account_name' => $d['display_name'],
                        'amount' => $d['total'],
                    ];
                }

                $sections[] = [
                    'section_code' => $header['section_code'],
                    'section_name' => $header['section_name'],
                    'items' => $items,
                    'subtotal' => $header['total'],
                ];
            }
        }

        return $sections;
    }
}
