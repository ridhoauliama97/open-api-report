<?php

namespace App\Services\Ascends\Shared\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;
use Throwable;

class KaryawanPerMasaKerjaReportService
{
    /**
     * @var array<int, array{key: string, label: string, min: int, max: int|null}>
     */
    private const WORK_PERIOD_BUCKETS = [
        ['key' => '0_6_bulan', 'label' => 'Masa Kerja : 0 - 6 Bulan', 'min' => 0, 'max' => 6],
        ['key' => '6_12_bulan', 'label' => 'Masa Kerja : 6 - 12 Bulan', 'min' => 6, 'max' => 12],
        ['key' => '1_2_tahun', 'label' => 'Masa Kerja : 1 - 2 Tahun', 'min' => 12, 'max' => 24],
        ['key' => '2_3_tahun', 'label' => 'Masa Kerja : 2 - 3 Tahun', 'min' => 24, 'max' => 36],
        ['key' => '3_tahun_lebih', 'label' => 'Masa Kerja : 3 Tahun Lebih', 'min' => 36, 'max' => null],
    ];

    /**
     * @var array<string, string>
     */
    private const GENDER_SUMMARY_LABELS = [
        'L' => 'Laki-Laki',
        'P' => 'Perempuan',
    ];

    /**
     * @var array<string, string>
     */
    private const LEVEL_SUMMARY_LABELS = [
        'Level 1' => 'Level 1',
        'Level 2' => 'Level 2',
        'Level 3' => 'Level 3',
        'Level 4' => 'Level 4',
        'Level 5' => 'Level 5',
        'Level 6' => 'Level 6',
        'Level 7' => 'Level 7',
    ];

    /**
     * @var array<string, string>
     */
    private const STATUS_SUMMARY_LABELS = [
        'BR' => 'BR',
        'KK' => 'KK',
        'KT' => 'KT',
        'ST' => 'ST',
    ];

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'karyawan_per_masa_kerja');

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
            'karyawan_per_masa_kerja',
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
            static fn (array $row): bool => self::shouldIncludeRow($row)
        ));
        $printedBy = self::resolvePrintedBy($rawRows);
        $rows = array_map(
            static fn (array $row): array => self::shapeRow($row),
            $rawRows
        );

        usort($rows, static function (array $left, array $right): int {
            return [
                (int) ($left['Total Masa Kerja Bulan'] ?? 0),
                (string) ($left['Nama'] ?? ''),
            ] <=> [
                (int) ($right['Total Masa Kerja Bulan'] ?? 0),
                (string) ($right['Nama'] ?? ''),
            ];
        });

        $groupedRows = self::groupRows($rows);
        $grandSummary = self::buildSummary($rows);
        $grandSummary['work_period'] = self::buildWorkPeriodSummary($rows);

        $headers = [
            'No',
            'Nama',
            'L/P',
            'Jabatan',
            'Status',
            'Level',
            'Tanggal Masuk',
            'Masa Kerja',
        ];

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? 'Laporan Karyawan Per Masa Kerja (RU)',
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'headers' => $headers,
            'rows' => array_map(static fn (array $row): array => self::publicRow($row), $rows),
            'grouped_rows' => $groupedRows,
            'grand_summary' => $grandSummary,
            'total_rows' => count($rows),
        ]);
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string|int>
     */
    private static function shapeRow(array $row): array
    {
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

        $totalMonths = ($years * 12) + $months;

        $status = self::resolveStatus($row);

        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'L/P' => self::formatGender((string) ($row['L/P'] ?? '')),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Status' => $status,
            'Status Summary' => $status,
            'Level' => (string) ($row['Level'] ?? ''),
            'Level Summary' => self::formatLevelSummary((string) ($row['Level'] ?? '')),
            'Tanggal Masuk' => self::formatDate((string) ($row['Tanggal Masuk'] ?? '')),
            'Masa Kerja' => self::formatWorkingPeriod($years, $months, $days),
            'Masa Kerja Key' => self::workPeriodBucketKey($totalMonths),
            'Total Masa Kerja Bulan' => $totalMonths,
        ];
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function shouldIncludeRow(array $row): bool
    {
        return trim((string) ($row['Salary Security Code'] ?? '')) !== '';
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function resolveStatus(array $row): string
    {
        $statusCode = trim((string) ($row['Status Kode'] ?? ''));

        return self::formatStatus($statusCode !== '' ? $statusCode : (string) ($row['Status'] ?? ''));
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return array<string, array{label: string, rows: array<int, array<string, string>>, summary: array<string, mixed>}>
     */
    private static function groupRows(array $rows): array
    {
        $groupedRows = [];

        foreach (self::WORK_PERIOD_BUCKETS as $bucket) {
            $bucketRows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => ($row['Masa Kerja Key'] ?? '') === $bucket['key']
            ));

            if ($bucketRows === []) {
                continue;
            }

            $groupedRows[$bucket['key']] = [
                'label' => $bucket['label'],
                'rows' => array_map(static fn (array $row): array => self::publicRow($row), $bucketRows),
                'summary' => self::buildSummary($bucketRows),
            ];
        }

        return $groupedRows;
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return array<string, mixed>
     */
    private static function buildSummary(array $rows): array
    {
        return [
            'subtotal' => count($rows),
            'gender' => self::countWithPercent($rows, 'L/P', self::GENDER_SUMMARY_LABELS),
            'level' => self::countWithPercent($rows, 'Level Summary', self::LEVEL_SUMMARY_LABELS),
            'status' => self::countWithPercent($rows, 'Status Summary', self::STATUS_SUMMARY_LABELS),
        ];
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return array<string, array{label: string, count: int, percent: int}>
     */
    private static function buildWorkPeriodSummary(array $rows): array
    {
        $summary = [];
        $total = count($rows);

        foreach (self::WORK_PERIOD_BUCKETS as $bucket) {
            $count = count(array_filter(
                $rows,
                static fn (array $row): bool => ($row['Masa Kerja Key'] ?? '') === $bucket['key']
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
     * @param  array<int, array<string, string|int>>  $rows
     * @param  array<string, string>  $defaultLabels
     * @return array<string, array{count: int, percent: int}>
     */
    private static function countWithPercent(array $rows, string $field, array $defaultLabels = []): array
    {
        $total = count($rows);
        $counts = array_fill_keys(array_keys($defaultLabels), 0);

        foreach ($rows as $row) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value === '') {
                continue;
            }

            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        ksort($counts, SORT_NATURAL);

        $summary = [];
        foreach ($counts as $value => $count) {
            $summary[$value] = [
                'count' => $count,
                'percent' => self::percent($count, $total),
            ];
        }

        return $summary;
    }

    /**
     * @param  array<string, string|int>  $row
     * @return array<string, string>
     */
    private static function publicRow(array $row): array
    {
        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'L/P' => (string) ($row['L/P'] ?? ''),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Status' => (string) ($row['Status'] ?? ''),
            'Level' => (string) ($row['Level'] ?? ''),
            'Tanggal Masuk' => (string) ($row['Tanggal Masuk'] ?? ''),
            'Masa Kerja' => (string) ($row['Masa Kerja'] ?? ''),
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

    private static function workPeriodBucketKey(int $totalMonths): string
    {
        foreach (self::WORK_PERIOD_BUCKETS as $bucket) {
            if ($totalMonths >= $bucket['min'] && ($bucket['max'] === null || $totalMonths < $bucket['max'])) {
                return $bucket['key'];
            }
        }

        return '3_tahun_lebih';
    }

    private static function formatGender(string $gender): string
    {
        return match (strtolower(trim($gender))) {
            'male', 'l', 'laki-laki', 'pria' => 'L',
            'female', 'p', 'perempuan', 'wanita' => 'P',
            default => trim($gender),
        };
    }

    private static function formatLevelSummary(string $level): string
    {
        $level = trim($level);
        if ($level === '') {
            return '';
        }

        if (preg_match('/(\d+)/', $level, $matches) === 1) {
            $levelNumber = (int) $matches[1];

            return $levelNumber > 0 ? 'Level '.$levelNumber : $level;
        }

        return $level;
    }

    private static function formatStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'borongan' => 'BR',
            'contract', 'kontrak', 'karyawan kontrak' => 'KK',
            'permanent', 'tetap', 'karyawan tetap' => 'KT',
            'staff', 'staff tetap' => 'ST',
            default => strtoupper(trim($status)),
        };
    }

    private static function formatDate(string $date): string
    {
        $carbon = self::parseDate($date);

        return $carbon?->locale('id')->translatedFormat('d-M-y') ?? trim($date);
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

    private static function formatWorkingPeriod(int $years, int $months, int $days): string
    {
        $parts = [];

        if ($years > 0) {
            $parts[] = $years.' Thn';
        }

        if ($months > 0) {
            $parts[] = $months.' Bln';
        }

        if ($days > 0) {
            $parts[] = $days.' Hari';
        }

        return implode(' ', $parts);
    }

    private static function numericValue(string $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
    }
}
