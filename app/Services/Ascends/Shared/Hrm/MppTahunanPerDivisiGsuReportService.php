<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class MppTahunanPerDivisiGsuReportService
{
    private const TITLE = 'Laporan Tahunan Per Divisi';

    private const DEFAULT_MPP = 80;

    private const DIVISI_ALIASES = [
        'Pilih Divisi',
        'Pilih_x0020_Divisi',
        'pilih_divisi',
        'pilihDivisi',
        'divisi',
        'Divisi',
        'division',
        'Department_x0020_Name',
        'department_name',
    ];

    private const MONTH_LABELS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function __construct() {}

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rawRows = $this->parseXml($xmlContents, $sourceLabel);
        $divisi = self::resolveDivisi($filters);
        $filteredRows = self::filterByDivision($rawRows, $divisi);
        $period = self::resolveReportPeriod();
        $mpp = self::lookupMpp($divisi);
        $mppValues = array_fill(1, 12, $mpp);
        $years = [
            $period['year'] - 1,
            $period['year'],
        ];

        $yearlyRows = [];
        foreach ($years as $year) {
            $monthLimit = $year === $period['year'] ? $period['month'] : 12;
            $rows = self::buildMonthlyRows($filteredRows, $year, $mppValues, $monthLimit);
            $yearlyRows[] = [
                'year' => $year,
                'rows' => $rows,
                'summary' => [
                    'joined' => self::seriesSummary(array_column($rows, 'Karyawan Masuk')),
                    'terminated' => self::seriesSummary(array_column($rows, 'Karyawan Keluar')),
                    'total' => self::seriesSummary(array_column($rows, 'Total Karyawan'), true),
                ],
            ];
        }

        $headers = [
            'Bulan',
            'MPP Karyawan',
            'Karyawan Masuk',
            'Karyawan Masuk (%)',
            'Karyawan Keluar',
            'Karyawan Keluar (%)',
            'Total Karyawan',
            'Total Karyawan (%)',
            'GAP',
            'GAP (%)',
            'Keterangan',
        ];

        return [
            'title' => self::TITLE,
            'divisi' => $divisi,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => self::resolvePrintedBy($rawRows),
            'per_date' => $period['date']->toDateString(),
            'headers' => $headers,
            'yearly_rows' => $yearlyRows,
            'rows' => array_merge(...array_map(static fn (array $year): array => $year['rows'], $yearlyRows)),
            'total_rows' => count($filteredRows),
            'mpp' => $mpp,
        ];
    }

    private static function lookupMpp(string $divisi): int
    {
        $map = config('reports.mpp_per_divisi', []);

        return (int) ($map[$divisi] ?? self::DEFAULT_MPP);
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Employee List kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Employee List tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'employees') {
                continue;
            }

            $recordXml = $reader->readOuterXML();
            if (! is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }

            $row = json_decode(json_encode($node), true) ?: [];
            $rows[] = array_map(
                static fn (mixed $value): string => is_array($value) ? '' : trim((string) $value),
                $row
            );
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('XML Employee List tidak memiliki record.');
        }

        return $rows;
    }

    private static function resolveDivisi(array $filters): string
    {
        return self::filterValue($filters, self::DIVISI_ALIASES);
    }

    private static function filterByDivision(array $rows, string $divisi): array
    {
        if ($divisi === '') {
            return $rows;
        }

        return array_values(array_filter($rows, static function (array $row) use ($divisi): bool {
            $rowDivisi = trim((string) ($row['Department_x0020_Name'] ?? ''));

            return str_contains(strtolower($rowDivisi), strtolower($divisi));
        }));
    }

    /**
     * @return array{year: int, month: int, date: Carbon}
     */
    private static function resolveReportPeriod(): array
    {
        $now = Carbon::now()->locale('id');

        return [
            'year' => (int) $now->year,
            'month' => (int) $now->month,
            'date' => $now,
        ];
    }

    /**
     * @param  array<int, int>  $mppValues
     * @return array<int, array<string, mixed>>
     */
    private static function buildMonthlyRows(array $rows, int $year, array $mppValues, int $monthLimit = 12): array
    {
        $monthlyRows = [];

        for ($month = 1; $month <= $monthLimit; $month++) {
            $mpp = $mppValues[$month] ?? self::DEFAULT_MPP;
            $total = self::totalAtMonthEnd($rows, $year, $month);
            $joined = self::countByYearMonth(
                $rows,
                'Join_x0020_Date_x0020__x0028_Year_x0029_',
                'Join_x0020_Date_x0020__x0028_Month_x0029_',
                $year,
                $month
            );
            $terminated = self::countByYearMonth(
                $rows,
                'Termination_x0020_Date_x0020__x0028_Year_x0029_',
                'Termination_x0020_Date_x0020__x0028_Month_x0029_',
                $year,
                $month
            );
            $gap = $total - $mpp;

            $monthlyRows[] = [
                'Bulan' => self::MONTH_LABELS[$month],
                'MPP Karyawan' => $mpp,
                'Karyawan Masuk' => $joined,
                'Karyawan Masuk (%)' => self::percentText($joined, $mpp),
                'Karyawan Keluar' => $terminated,
                'Karyawan Keluar (%)' => self::percentText($terminated, $mpp),
                'Total Karyawan' => $total,
                'Total Karyawan (%)' => self::percentText($total, $mpp),
                'GAP' => $gap,
                'GAP (%)' => self::percentText($gap, $mpp),
                'Keterangan' => '',
            ];
        }

        return $monthlyRows;
    }

    private static function totalAtMonthEnd(array $rows, int $year, int $month): int
    {
        return count(array_filter($rows, static function (array $row) use ($year, $month): bool {
            $joinYear = (int) ($row['Join_x0020_Date_x0020__x0028_Year_x0029_'] ?? 0);
            $joinMonth = (int) ($row['Join_x0020_Date_x0020__x0028_Month_x0029_'] ?? 0);
            $termYear = (int) ($row['Termination_x0020_Date_x0020__x0028_Year_x0029_'] ?? 0);
            $termMonth = (int) ($row['Termination_x0020_Date_x0020__x0028_Month_x0029_'] ?? 0);

            return self::yearMonthLessThanOrEqual($joinYear, $joinMonth, $year, $month)
                && self::yearMonthGreaterThan($termYear, $termMonth, $year, $month);
        }));
    }

    private static function countByYearMonth(array $rows, string $yearKey, string $monthKey, int $year, int $month): int
    {
        return count(array_filter($rows, static fn (array $row): bool => (int) ($row[$yearKey] ?? 0) === $year
            && (int) ($row[$monthKey] ?? 0) === $month));
    }

    private static function yearMonthLessThanOrEqual(int $leftYear, int $leftMonth, int $rightYear, int $rightMonth): bool
    {
        return $leftYear > 0
            && $leftMonth > 0
            && ($leftYear < $rightYear || ($leftYear === $rightYear && $leftMonth <= $rightMonth));
    }

    private static function yearMonthGreaterThan(int $leftYear, int $leftMonth, int $rightYear, int $rightMonth): bool
    {
        return $leftYear <= 0
            || $leftMonth <= 0
            || $leftYear > $rightYear
            || ($leftYear === $rightYear && $leftMonth > $rightMonth);
    }

    /**
     * @param  array<int, int|string>  $values
     * @return array{min: int, min_percent: string, max: int, max_percent: string, avg: int, avg_percent: string}
     */
    private static function seriesSummary(array $values, bool $percentAgainstAverage = false): array
    {
        $numbers = array_map(static fn (mixed $value): int => (int) $value, $values);
        if ($numbers === []) {
            return [
                'min' => 0,
                'min_percent' => '0.0%',
                'max' => 0,
                'max_percent' => '0.0%',
                'avg' => 0,
                'avg_percent' => '0.0%',
            ];
        }

        $min = min($numbers);
        $max = max($numbers);
        $avg = (int) round(array_sum($numbers) / count($numbers));
        $denominator = $percentAgainstAverage ? $avg : array_sum($numbers);

        return [
            'min' => $min,
            'min_percent' => self::percentText($min, $denominator),
            'max' => $max,
            'max_percent' => self::percentText($max, $denominator),
            'avg' => $avg,
            'avg_percent' => self::percentText($avg, $denominator),
        ];
    }

    private static function percentText(int $value, int $total): string
    {
        if ($total === 0) {
            return '0.0%';
        }

        return number_format(($value / $total) * 100, 1, '.', '').'%';
    }

    private static function resolvePrintedBy(array $rows): string
    {
        foreach ($rows as $row) {
            foreach (['Sys_Username', 'Sys_UserName', 'Printed_x0020_By', 'Created_x0020_By'] as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private static function filterValue(array $filters, array $aliases): string
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $filters)) {
                $value = trim((string) $filters[$alias]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        $normalizedAliases = array_map(static fn (string $alias): string => self::normalizeKey($alias), $aliases);
        foreach ($filters as $key => $value) {
            if (in_array(self::normalizeKey((string) $key), $normalizedAliases, true)) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private static function normalizeKey(string $key): string
    {
        return strtolower(str_replace([' ', '_x0020_', '_', '-'], '', $key));
    }
}
