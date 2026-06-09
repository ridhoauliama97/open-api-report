<?php

namespace App\Services\Ascends\Ru\Hrm;

use App\Services\XmlDataSourceService;

class EmployeeListReportService
{
    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'employee_list');

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
            'employee_list',
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
        $rawRows = $reportData['rows'] ?? [];
        $printedBy = self::resolvePrintedBy($rawRows);
        $rows = array_map(
            static fn(array $row): array => self::shapeEmployeeListRow($row),
            $rawRows
        );
        $rows = array_values(array_filter(
            $rows,
            static fn(array $row): bool => trim((string) ($row['Departemen'] ?? '')) !== ''
        ));
        $headers = [
            'Nama',
            'Jenis Kelamin',
            'Usia',
            'Jabatan',
            'Lama Bekerja',
            'Keterangan',
            'Nama Tempat Ibadah',
            'Lemari',
        ];

        usort($rows, static function (array $left, array $right): int {
            return [
                (string) ($left['Departemen'] ?? ''),
                (string) ($left['Nama'] ?? ''),
            ] <=> [
                (string) ($right['Departemen'] ?? ''),
                (string) ($right['Nama'] ?? ''),
            ];
        });

        $departmentSummary = [];
        $genderSummary = [];
        $groupedRows = [];

        foreach ($rows as $row) {
            $department = trim((string) ($row['Departemen'] ?? ''));
            $gender = trim((string) ($row['Jenis Kelamin'] ?? ''));
            $genderKey = $gender !== '' ? $gender : '-';

            $groupedRows[$department][] = $row;
            $departmentSummary[$department] = ($departmentSummary[$department] ?? 0) + 1;
            $genderSummary[$genderKey] = ($genderSummary[$genderKey] ?? 0) + 1;
        }

        arsort($departmentSummary);
        ksort($genderSummary);

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? 'List Karyawan RU',
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'headers' => $headers,
            'rows' => $rows,
            'grouped_rows' => $groupedRows,
            'total_rows' => count($rows),
            'summary' => [
                'department_count' => count($departmentSummary),
                'gender_summary' => $genderSummary,
                'top_departments' => array_slice($departmentSummary, 0, 10, true),
            ],
        ]);
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function shapeEmployeeListRow(array $row): array
    {
        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'Jenis Kelamin' => self::formatGender((string) ($row['Jenis Kelamin'] ?? '')),
            'Usia' => self::formatAge((string) ($row['Usia'] ?? '')),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Lama Bekerja' => self::formatWorkingPeriod(
                (string) ($row['Lama Bekerja Tahun'] ?? ''),
                (string) ($row['Lama Bekerja Bulan'] ?? '')
            ),
            'Keterangan' => (string) ($row['Keterangan'] ?? ''),
            'Nama Tempat Ibadah' => (string) ($row['Nama Tempat Ibadah'] ?? ''),
            'Lemari' => (string) ($row['Lemari'] ?? ''),
            'Departemen' => (string) ($row['Departemen'] ?? ''),
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

    private static function formatGender(string $gender): string
    {
        return match (strtolower(trim($gender))) {
            'male' => 'Pria',
            'female' => 'Wanita',
            default => trim($gender),
        };
    }

    private static function formatAge(string $age): string
    {
        return is_numeric($age) && (int) $age > 0 ? ((int) $age) . ' Thn' : trim($age);
    }

    private static function formatWorkingPeriod(string $years, string $months): string
    {
        $parts = [];

        if (is_numeric($years) && (int) $years > 0) {
            $parts[] = ((int) $years) . ' Thn';
        }

        if (is_numeric($months) && (int) $months > 0) {
            $parts[] = ((int) $months) . ' Bln';
        }

        return implode(' ', $parts);
    }
}
