<?php

namespace App\Services\Ascends\Ru\Hrm;

use App\Services\XmlDataSourceService;

class EmployeeListReportService
{
    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

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
        $rows = $reportData['rows'] ?? [];

        usort($rows, static function (array $left, array $right): int {
            return [
                (string) ($left['Departemen'] ?? ''),
                (string) ($left['Nama Lengkap'] ?? ''),
            ] <=> [
                (string) ($right['Departemen'] ?? ''),
                (string) ($right['Nama Lengkap'] ?? ''),
            ];
        });

        $departmentSummary = [];
        $genderSummary = [];
        $groupedRows = [];

        foreach ($rows as $row) {
            $department = trim((string) ($row['Departemen'] ?? ''));
            $departmentKey = $department !== '' ? $department : 'Tanpa Departemen';
            $gender = trim((string) ($row['JK'] ?? ''));
            $genderKey = $gender !== '' ? $gender : '-';

            $groupedRows[$departmentKey][] = $row;
            $departmentSummary[$departmentKey] = ($departmentSummary[$departmentKey] ?? 0) + 1;
            $genderSummary[$genderKey] = ($genderSummary[$genderKey] ?? 0) + 1;
        }

        arsort($departmentSummary);
        ksort($genderSummary);

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? 'List Karyawan RU',
            'source_file' => $sourceLabel,
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
}
