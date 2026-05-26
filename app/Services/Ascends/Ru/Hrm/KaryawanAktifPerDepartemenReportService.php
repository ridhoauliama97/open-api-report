<?php

namespace App\Services\Ascends\Ru\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;
use Throwable;

class KaryawanAktifPerDepartemenReportService
{
    private const TITLE = 'Laporan Karyawan Aktif Per Departemen (RU)';

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
    private const EDUCATION_LABELS = [
        'SMP' => 'SMP',
        'SMA' => 'SMA',
        'D3' => 'D3',
        'S1' => 'S1',
        'S2' => 'S2',
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
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'karyawan_aktif_per_departemen');

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
            'karyawan_aktif_per_departemen',
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

        usort($rows, static fn (array $left, array $right): int => [
            (string) ($left['Departemen'] ?? ''),
            (string) ($left['Tanggal Masuk Sort'] ?? ''),
            (string) ($left['Nama'] ?? ''),
        ] <=> [
            (string) ($right['Departemen'] ?? ''),
            (string) ($right['Tanggal Masuk Sort'] ?? ''),
            (string) ($right['Nama'] ?? ''),
        ]);

        $groupedRows = self::groupRows($rows);
        $grandSummary = self::buildSummary($rows);

        $headers = [
            'No',
            'Nama',
            'Status',
            'L/P',
            'Jabatan',
            'Level',
            'Strata Pend',
            'Tanggal Masuk',
        ];

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
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
     */
    private static function shouldIncludeRow(array $row): bool
    {
        $employeeCode = trim((string) ($row['Kode Karyawan'] ?? ''));

        return strcasecmp(trim((string) ($row['Status Aktif'] ?? '')), 'Active') === 0
            && ! str_starts_with(strtoupper($employeeCode), 'SPECIAL');
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function shapeRow(array $row): array
    {
        $joinDate = trim((string) ($row['Tanggal Masuk'] ?? ''));

        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'Status' => strtoupper(trim((string) ($row['Status'] ?? ''))),
            'L/P' => self::formatGender($row),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Level' => trim((string) ($row['Level'] ?? '')),
            'Level Summary' => self::formatLevel((string) ($row['Level'] ?? '')),
            'Strata Pend' => self::formatEducation((string) ($row['Strata Pend'] ?? '')),
            'Tanggal Masuk' => self::formatDate($joinDate),
            'Tanggal Masuk Sort' => self::dateSortKey($joinDate),
            'Departemen' => trim((string) ($row['Departemen'] ?? '')),
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array{label: string, rows: array<int, array<string, string>>, summary: array<string, mixed>}>
     */
    private static function groupRows(array $rows): array
    {
        $departmentRows = [];

        foreach ($rows as $row) {
            $department = trim((string) ($row['Departemen'] ?? ''));
            $departmentKey = $department !== '' ? $department : 'Tanpa Departemen';
            $departmentRows[$departmentKey][] = $row;
        }

        ksort($departmentRows, SORT_NATURAL | SORT_FLAG_CASE);

        $groupedRows = [];
        foreach ($departmentRows as $department => $rowsInDepartment) {
            $groupedRows[] = [
                'label' => 'Departemen : '.$department,
                'rows' => array_map(static fn (array $row): array => self::publicRow($row), $rowsInDepartment),
                'summary' => self::buildSummary($rowsInDepartment),
            ];
        }

        return $groupedRows;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, mixed>
     */
    private static function buildSummary(array $rows): array
    {
        return [
            'subtotal' => count($rows),
            'gender' => self::countWithPercent($rows, 'L/P', self::GENDER_LABELS),
            'status' => self::countWithPercent($rows, 'Status', self::STATUS_LABELS),
            'education' => self::countWithPercent($rows, 'Strata Pend', self::EDUCATION_LABELS),
            'level' => self::countWithPercent($rows, 'Level Summary', self::LEVEL_LABELS),
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
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

            if (! array_key_exists($value, $counts)) {
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
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function publicRow(array $row): array
    {
        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'Status' => (string) ($row['Status'] ?? ''),
            'L/P' => (string) ($row['L/P'] ?? ''),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Level' => (string) ($row['Level'] ?? ''),
            'Strata Pend' => (string) ($row['Strata Pend'] ?? ''),
            'Tanggal Masuk' => (string) ($row['Tanggal Masuk'] ?? ''),
            'Departemen' => (string) ($row['Departemen'] ?? ''),
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
        $identityGender = self::genderFromIdentityNo((string) ($row['No Identitas'] ?? ''));
        if ($identityGender !== '') {
            return $identityGender;
        }

        return match (strtolower(trim((string) ($row['L/P'] ?? '')))) {
            'male', 'l', 'laki-laki', 'pria' => 'L',
            'female', 'p', 'perempuan', 'wanita' => 'P',
            default => trim((string) ($row['L/P'] ?? '')),
        };
    }

    private static function genderFromIdentityNo(string $identityNo): string
    {
        $digits = preg_replace('/\D/', '', $identityNo) ?? '';
        if (strlen($digits) < 8) {
            return '';
        }

        $birthDay = (int) substr($digits, 6, 2);

        if ($birthDay >= 41 && $birthDay <= 71) {
            return 'P';
        }

        if ($birthDay >= 1 && $birthDay <= 31) {
            return 'L';
        }

        return '';
    }

    private static function formatEducation(string $education): string
    {
        $education = strtoupper(trim($education));

        if ($education === '') {
            return '';
        }

        if (str_contains($education, 'SMA') || str_contains($education, 'SMK')) {
            return 'SMA';
        }

        foreach (array_keys(self::EDUCATION_LABELS) as $label) {
            if ($education === $label) {
                return $label;
            }
        }

        return $education;
    }

    private static function formatLevel(string $level): string
    {
        $level = trim($level);
        if ($level === '') {
            return '';
        }

        if (preg_match('/(\d+)/', $level, $matches) === 1) {
            return 'Level '.((int) $matches[1]);
        }

        return $level;
    }

    private static function formatDate(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        try {
            return Carbon::parse($date)->locale('id')->translatedFormat('d-M-Y');
        } catch (Throwable) {
            return $date;
        }
    }

    private static function dateSortKey(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (Throwable) {
            return $date;
        }
    }

    private static function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
    }
}
