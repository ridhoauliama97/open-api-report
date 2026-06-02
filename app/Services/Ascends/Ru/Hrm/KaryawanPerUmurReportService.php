<?php

namespace App\Services\Ascends\Ru\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;
use Throwable;

class KaryawanPerUmurReportService
{
    private const TITLE = 'Laporan Karyawan Per Umur (RU)';

    /**
     * @var array<int, array{key: string, label: string, min: int, max: int|null}>
     */
    private const AGE_BUCKETS = [
        ['key' => '17_20', 'label' => '17 - 20 Tahun', 'min' => 17, 'max' => 20],
        ['key' => '21_30', 'label' => '21 - 30 Tahun', 'min' => 21, 'max' => 30],
        ['key' => '31_40', 'label' => '31 - 40 Tahun', 'min' => 31, 'max' => 40],
        ['key' => '41_50', 'label' => '41 - 50 Tahun', 'min' => 41, 'max' => 50],
        ['key' => '51_60', 'label' => '51 - 60 Tahun', 'min' => 51, 'max' => 60],
        ['key' => '60_plus', 'label' => '60 Tahun ++', 'min' => 61, 'max' => null],
    ];

    /**
     * @var array<string, string>
     */
    private const GENDER_LABELS = [
        'L' => 'Laki - Laki',
        'P' => 'Perempuan',
    ];

    /**
     * @var array<string, string>
     */
    private const STATUS_LABELS = [
        'BR' => 'BR',
        'KK' => 'KK',
        'KT' => 'KT',
        'ST' => 'ST',
    ];

    /**
     * @var array<string, string>
     */
    private const LEVEL_LABELS = [
        'Level 1' => 'Level 1',
        'Level 2' => 'Level 2',
        'Level 3' => 'Level 3',
        'Level 4' => 'Level 4',
        'Level 5' => 'Level 5',
        'Level 6' => 'Level 6',
        'Level 7' => 'Level 7',
    ];

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'karyawan_per_umur');

        return $this->shapeReportData($reportData, 'storage/app/xml_sources/RU/hrm/AnlReports.HRM.EmployeeList.xml');
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload'): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReportFromXmlContents(
            'RU',
            'hrm',
            'karyawan_per_umur',
            $xmlContents,
            $sourceLabel
        );

        return $this->shapeReportData($reportData, $sourceLabel);
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array<string, mixed>
     */
    private function shapeReportData(array $reportData, string $sourceLabel): array
    {
        $rawRows = array_values(array_filter(
            $reportData['rows'] ?? [],
            static fn(array $row): bool => self::shouldIncludeRow($row)
        ));
        $printedBy = self::resolvePrintedBy($rawRows);
        $rows = array_map(
            static fn(array $row): array => self::shapeRow($row),
            $rawRows
        );

        usort($rows, static fn(array $left, array $right): int => [
            self::ageBucketSortValue((string) ($left['Umur Key'] ?? '')),
            (int) ($left['Umur Sort'] ?? 0),
            (string) ($left['Nama'] ?? ''),
        ] <=> [
            self::ageBucketSortValue((string) ($right['Umur Key'] ?? '')),
            (int) ($right['Umur Sort'] ?? 0),
            (string) ($right['Nama'] ?? ''),
        ]);

        $groupedRows = self::groupRows($rows);
        $grandSummary = self::buildSummary($rows);
        $grandSummary['age'] = self::buildAgeSummary($rows);

        $headers = [
            'No',
            'Nama',
            'Jabatan',
            'L/P',
            'Status',
            'Umur',
            'Masa Kerja',
            'Level',
        ];

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'headers' => $headers,
            'rows' => array_map(static fn(array $row): array => self::publicRow($row), $rows),
            'grouped_rows' => $groupedRows,
            'grand_summary' => $grandSummary,
            'total_rows' => count($rows),
        ]);
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function shouldIncludeRow(array $row): bool
    {
        $employeeCode = trim((string) ($row['Kode Karyawan'] ?? ''));

        return strcasecmp(trim((string) ($row['Status Aktif'] ?? '')), 'Active') === 0
            && !str_starts_with(strtoupper($employeeCode), 'SPECIAL');
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private static function shapeRow(array $row): array
    {
        $age = self::numericValue((string) ($row['Umur'] ?? ''));
        $years = self::numericValue((string) ($row['Masa Kerja Tahun'] ?? ''));
        $months = self::numericValue((string) ($row['Masa Kerja Bulan'] ?? ''));
        $days = self::numericValue((string) ($row['Masa Kerja Hari'] ?? ''));
        $joinDate = self::parseDate((string) ($row['Tanggal Masuk'] ?? ''));

        if ($joinDate !== null && $days === 0 && trim((string) ($row['Masa Kerja Hari'] ?? '')) === '') {
            $period = $joinDate->diff(Carbon::now());
            $years = $period->y;
            $months = $period->m;
            $days = $period->d;
        }

        $level = trim((string) ($row['Level'] ?? ''));

        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'L/P' => self::formatGender($row),
            'Status' => strtoupper(trim((string) ($row['Status'] ?? ''))),
            'Umur' => $age > 0 ? (string) $age : '',
            'Umur Sort' => $age,
            'Umur Key' => self::ageBucketKey($age),
            'Masa Kerja' => self::formatWorkingPeriod($years, $months, $days),
            'Level' => $level,
            'Level Summary' => self::formatLevel($level),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{label: string, rows: array<int, array<string, string>>, summary: array<string, mixed>}>
     */
    private static function groupRows(array $rows): array
    {
        $groupedRows = [];

        foreach (self::AGE_BUCKETS as $bucket) {
            $bucketRows = array_values(array_filter(
                $rows,
                static fn(array $row): bool => ($row['Umur Key'] ?? '') === $bucket['key']
            ));

            if ($bucketRows === []) {
                continue;
            }

            $groupedRows[] = [
                'label' => 'Umur : ' . $bucket['label'],
                'rows' => array_map(static fn(array $row): array => self::publicRow($row), $bucketRows),
                'summary' => self::buildSummary($bucketRows),
            ];
        }

        return $groupedRows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private static function buildSummary(array $rows): array
    {
        return [
            'subtotal' => count($rows),
            'gender' => self::countWithPercent($rows, 'L/P', self::GENDER_LABELS),
            'status' => self::countWithPercent($rows, 'Status', self::STATUS_LABELS),
            'level' => self::countWithPercent($rows, 'Level Summary', self::LEVEL_LABELS),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array{label: string, count: int, percent: int}>
     */
    private static function buildAgeSummary(array $rows): array
    {
        $total = count($rows);
        $summary = [];

        foreach (self::AGE_BUCKETS as $bucket) {
            $count = count(array_filter(
                $rows,
                static fn(array $row): bool => ($row['Umur Key'] ?? '') === $bucket['key']
            ));

            $summary[$bucket['key']] = [
                'label' => $bucket['label'],
                'count' => $count,
                'percent' => self::percent($count, $total),
            ];
        }

        return $summary;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $defaultLabels
     * @return array<string, array{label: string, count: int, percent: int}>
     */
    private static function countWithPercent(array $rows, string $field, array $defaultLabels): array
    {
        $counts = array_fill_keys(array_keys($defaultLabels), 0);

        foreach ($rows as $row) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value === '') {
                continue;
            }

            if (!array_key_exists($value, $counts)) {
                $counts[$value] = 0;
                $defaultLabels[$value] = $value;
            }

            $counts[$value]++;
        }

        $summary = [];
        foreach ($counts as $value => $count) {
            $summary[$value] = [
                'label' => $defaultLabels[$value] ?? $value,
                'count' => $count,
                'percent' => self::percent($count, count($rows)),
            ];
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private static function publicRow(array $row): array
    {
        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'L/P' => (string) ($row['L/P'] ?? ''),
            'Status' => (string) ($row['Status'] ?? ''),
            'Umur' => (string) ($row['Umur'] ?? ''),
            'Masa Kerja' => (string) ($row['Masa Kerja'] ?? ''),
            'Level' => (string) ($row['Level'] ?? ''),
            'Level Summary' => (string) ($row['Level Summary'] ?? ''),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private static function resolvePrintedBy(array $rows): string
    {
        $candidateKeys = [
            'Nama User',
            'User Name',
            'Printed By',
            'Created By',
        ];

        foreach ($rows as $row) {
            foreach ($candidateKeys as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function formatGender(array $row): string
    {
        $sexGender = match (strtolower(trim((string) ($row['L/P'] ?? '')))) {
            'male', 'l', 'laki-laki', 'pria' => 'L',
            'female', 'p', 'perempuan', 'wanita' => 'P',
            default => '',
        };

        if ($sexGender !== '') {
            return $sexGender;
        }

        return trim((string) ($row['L/P'] ?? ''));
    }

    private static function formatLevel(string $level): string
    {
        $level = trim($level);
        if ($level === '') {
            return '';
        }

        if (preg_match('/(\d+)/', $level, $matches) === 1) {
            return 'Level ' . ((int) $matches[1]);
        }

        return $level;
    }

    private static function ageBucketKey(int $age): string
    {
        foreach (self::AGE_BUCKETS as $bucket) {
            $max = $bucket['max'];
            if ($age >= $bucket['min'] && ($max === null || $age <= $max)) {
                return $bucket['key'];
            }
        }

        return '';
    }

    private static function ageBucketSortValue(string $key): int
    {
        foreach (self::AGE_BUCKETS as $index => $bucket) {
            if ($bucket['key'] === $key) {
                return $index;
            }
        }

        return 999;
    }

    private static function formatWorkingPeriod(int $years, int $months, int $days): string
    {
        return $years . ' Thn ' . $months . ' Bln ' . $days . ' Hari';
    }

    private static function parseDate(string $date): ?Carbon
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (Throwable) {
            return null;
        }
    }

    private static function numericValue(string $value): int
    {
        return is_numeric(trim($value)) ? (int) trim($value) : 0;
    }

    private static function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
    }
}
