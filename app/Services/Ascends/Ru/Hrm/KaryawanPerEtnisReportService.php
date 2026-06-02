<?php

namespace App\Services\Ascends\Ru\Hrm;

use App\Services\XmlDataSourceService;

class KaryawanPerEtnisReportService
{
    private const TITLE = 'Laporan Karyawan Per Etnis (RU)';

    /**
     * @var array<string, string>
     */
    private const GENDER_LABELS = [
        'L' => 'Laki-Laki',
        'P' => 'Perempuan',
    ];

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'karyawan_per_etnis');

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
            'karyawan_per_etnis',
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
            (string) ($left['Etnis'] ?? ''),
            (string) ($left['Nama'] ?? ''),
        ] <=> [
            (string) ($right['Etnis'] ?? ''),
            (string) ($right['Nama'] ?? ''),
        ]);

        $groupedRows = self::groupRows($rows);
        $grandSummary = self::buildSummary($rows);
        $grandSummary['ethnicity'] = self::countWithPercent($rows, 'Etnis', self::ethnicityLabels($rows));

        $headers = [
            'No',
            'NIK',
            'Nama',
            'Jabatan',
            'Umur',
            'Agama',
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
        $employeeCode = trim((string) ($row['NIK'] ?? ''));

        return strcasecmp(trim((string) ($row['Status Aktif'] ?? '')), 'Active') === 0
            && ! str_starts_with(strtoupper($employeeCode), 'SPECIAL');
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function shapeRow(array $row): array
    {
        return [
            'NIK' => (string) ($row['NIK'] ?? ''),
            'Nama' => (string) ($row['Nama'] ?? ''),
            'L/P' => self::formatGender($row),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Umur' => self::formatAge((string) ($row['Umur'] ?? '')),
            'Agama' => self::formatReligion((string) ($row['Agama'] ?? '')),
            'Etnis' => trim((string) ($row['Etnis'] ?? '')),
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array{label: string, rows: array<int, array<string, string>>, summary: array<string, mixed>}>
     */
    private static function groupRows(array $rows): array
    {
        $ethnicityRows = [];

        foreach ($rows as $row) {
            $ethnicity = trim((string) ($row['Etnis'] ?? ''));
            $ethnicityRows[$ethnicity][] = $row;
        }

        ksort($ethnicityRows, SORT_NATURAL | SORT_FLAG_CASE);

        $groupedRows = [];
        foreach ($ethnicityRows as $ethnicity => $rowsInEthnicity) {
            $groupedRows[] = [
                'label' => 'Etnis : '.trim((string) $ethnicity),
                'rows' => array_map(static fn (array $row): array => self::publicRow($row), $rowsInEthnicity),
                'summary' => self::buildSummary($rowsInEthnicity),
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
            if ($field === 'Etnis' && $value === '') {
                $value = 'Tanpa Etnis';
            }

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
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, string>
     */
    private static function ethnicityLabels(array $rows): array
    {
        $labels = [];

        foreach ($rows as $row) {
            $ethnicity = trim((string) ($row['Etnis'] ?? ''));
            $labels[$ethnicity !== '' ? $ethnicity : 'Tanpa Etnis'] = $ethnicity !== '' ? $ethnicity : 'Tanpa Etnis';
        }

        ksort($labels, SORT_NATURAL | SORT_FLAG_CASE);

        return $labels;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function publicRow(array $row): array
    {
        return [
            'NIK' => (string) ($row['NIK'] ?? ''),
            'Nama' => (string) ($row['Nama'] ?? ''),
            'L/P' => (string) ($row['L/P'] ?? ''),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Umur' => (string) ($row['Umur'] ?? ''),
            'Agama' => (string) ($row['Agama'] ?? ''),
            'Etnis' => (string) ($row['Etnis'] ?? ''),
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

    private static function formatAge(string $age): string
    {
        $age = trim($age);
        if ($age === '') {
            return '';
        }

        return preg_match('/tahun/i', $age) === 1 ? $age : $age.' Tahun';
    }

    private static function formatReligion(string $religion): string
    {
        $religion = trim($religion);

        return strcasecmp($religion, 'Budha') === 0 ? 'Buddha' : $religion;
    }

    private static function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
    }
}
