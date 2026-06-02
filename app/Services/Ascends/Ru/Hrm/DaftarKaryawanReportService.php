<?php

namespace App\Services\Ascends\Ru\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;
use Throwable;

class DaftarKaryawanReportService
{
    private const TITLE = 'Laporan Daftar Karyawan (RU)';

    /**
     * @var array<string, string>
     */
    private const GENDER_LABELS = [
        'L' => 'L',
        'P' => 'P',
    ];

    /**
     * @var array<string, string>
     */
    private const STATUS_LABELS = [
        'ST' => 'ST',
        'KT' => 'KT',
        'KK' => 'KK',
    ];

    /**
     * @var array<string, string>
     */
    private const EDUCATION_LABELS = [
        'SMP' => 'SMP',
        'SMA/SMK' => 'SMA/SMK',
        'D3' => 'D3',
        'S1' => 'S1',
        'S2' => 'S2',
    ];

    /**
     * @var array<string, string>
     */
    private const LEVEL_LABELS = [
        'Lvl 1' => 'Lvl 1',
        'Lvl 2' => 'Lvl 2',
        'Lvl 3' => 'Lvl 3',
        'Lvl 4' => 'Lvl 4',
        'Lvl 5' => 'Lvl 5',
        'Lvl 6' => 'Lvl 6',
        'Lvl 7' => 'Lvl 7',
    ];

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'daftar_karyawan');

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
            'daftar_karyawan',
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

        $groupedRows = self::groupRows($rows);
        $grandSummary = self::buildSummary($rows);

        $headers = [
            'No',
            'Nama',
            'Jabatan',
            'Tp',
            'Level',
            'Tgn',
            'Perusahaan Sebelumnya',
            'LastEdu',
            'Tgl Masuk',
        ];

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'headers' => $headers,
            'rows' => $rows,
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
        return strcasecmp(trim((string) ($row['Status Aktif'] ?? '')), 'Active') === 0;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function shapeRow(array $row): array
    {
        $tp = strtoupper(trim((string) ($row['Tp'] ?? '')));

        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Tp' => $tp,
            'Level' => trim((string) ($row['Level'] ?? '')),
            'Tgn' => (string) ($row['Tgn'] ?? ''),
            'Perusahaan Sebelumnya' => (string) ($row['Perusahaan Sebelumnya'] ?? ''),
            'LastEdu' => trim((string) ($row['LastEdu'] ?? '')),
            'Tgl Masuk' => self::formatDate((string) ($row['Tgl Masuk'] ?? '')),
            'Department' => trim((string) ($row['Department'] ?? '')),
            'L/P' => self::formatGender($row),
            'Status Summary' => in_array($tp, array_keys(self::STATUS_LABELS), true) ? $tp : '',
            'Education Summary' => self::formatEducation((string) ($row['LastEdu'] ?? '')),
            'Level Summary' => self::formatLevel((string) ($row['Level'] ?? '')),
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
            $department = trim((string) ($row['Department'] ?? ''));
            $departmentKey = $department !== '' ? $department : 'Tanpa Departemen';
            $departmentRows[$departmentKey][] = self::publicRow($row);
        }

        ksort($departmentRows, SORT_NATURAL | SORT_FLAG_CASE);

        $groupedRows = [];
        foreach ($departmentRows as $department => $rowsInDepartment) {
            $groupedRows[] = [
                'label' => 'Department : '.$department,
                'rows' => $rowsInDepartment,
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
            'status' => self::countWithPercent($rows, 'Status Summary', self::STATUS_LABELS, true),
            'education' => self::countWithPercent($rows, 'Education Summary', self::EDUCATION_LABELS, true),
            'level' => self::countWithPercent($rows, 'Level Summary', self::LEVEL_LABELS),
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, string>  $defaultLabels
     * @return array<string, array{label: string, count: int, percent: int}>
     */
    private static function countWithPercent(array $rows, string $field, array $defaultLabels, bool $useMatchedTotal = false): array
    {
        $counts = array_fill_keys(array_keys($defaultLabels), 0);
        $matchedTotal = 0;

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
            $matchedTotal++;
        }

        $total = $useMatchedTotal ? $matchedTotal : count($rows);
        $summary = [];

        foreach ($counts as $value => $count) {
            $summary[$value] = [
                'label' => $defaultLabels[$value] ?? $value,
                'count' => $count,
                'percent' => self::percent($count, $total),
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
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Tp' => (string) ($row['Tp'] ?? ''),
            'Level' => (string) ($row['Level'] ?? ''),
            'Tgn' => (string) ($row['Tgn'] ?? ''),
            'Perusahaan Sebelumnya' => (string) ($row['Perusahaan Sebelumnya'] ?? ''),
            'LastEdu' => (string) ($row['LastEdu'] ?? ''),
            'Tgl Masuk' => (string) ($row['Tgl Masuk'] ?? ''),
            'Department' => (string) ($row['Department'] ?? ''),
            'L/P' => (string) ($row['L/P'] ?? ''),
            'Status Summary' => (string) ($row['Status Summary'] ?? ''),
            'Education Summary' => (string) ($row['Education Summary'] ?? ''),
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

    private static function formatEducation(string $education): string
    {
        $education = strtoupper(trim($education));

        if ($education === '') {
            return '';
        }

        if (str_contains($education, 'SMA') || str_contains($education, 'SMK')) {
            return 'SMA/SMK';
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
            return 'Lvl '.((int) $matches[1]);
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

    private static function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
    }
}
