<?php

namespace App\Services\Ascends\Ru\Hrm;

use App\Services\XmlDataSourceService;
use Illuminate\Support\Carbon;

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
        $records = $this->xmlDataSourceService->loadModuleRecords('RU', 'hrm');
        $rows = [];

        $departmentSummary = [];
        $genderSummary = [];

        foreach ($records as $record) {
            if (($record['Active'] ?? '') !== 'Active') {
                continue;
            }

            $department = trim((string) ($record['Department_x0020_Name'] ?? ''));
            $gender = $this->formatGender((string) ($record['Sex'] ?? ''));

            $rows[] = [
                'department' => $department !== '' ? $department : 'Tanpa Departemen',
                'name' => trim((string) ($record['Full_x0020_Name'] ?? '')),
                'gender' => $gender,
                'age' => $this->formatAge((string) ($record['Age'] ?? '')),
                'job_title' => trim((string) ($record['Job_x0020_Title'] ?? '')),
                'working_period' => $this->formatWorkingPeriod(
                    (string) ($record['Working_x0020_Years'] ?? ''),
                    (string) ($record['Working_x0020_Months'] ?? '')
                ),
                'remarks' => trim((string) ($record['Employee_x0020_Remarks'] ?? '')),
                'place_of_worship' => $this->mapPlaceOfWorship((string) ($record['Religion'] ?? '')),
                'locker' => trim((string) ($record['Locker'] ?? '')),
            ];

            $departmentKey = $department !== '' ? $department : 'Tanpa Departemen';
            $genderKey = $gender !== '' ? $gender : '-';

            $departmentSummary[$departmentKey] = ($departmentSummary[$departmentKey] ?? 0) + 1;
            $genderSummary[$genderKey] = ($genderSummary[$genderKey] ?? 0) + 1;
        }

        usort($rows, static function (array $left, array $right): int {
            return [$left['department'], $left['name']] <=> [$right['department'], $right['name']];
        });

        $groupedRows = [];
        foreach ($rows as $row) {
            $groupedRows[$row['department']][] = $row;
        }

        arsort($departmentSummary);
        ksort($genderSummary);

        return [
            'printed_at' => Carbon::now()->translatedFormat('d F Y'),
            'company' => 'RU',
            'module' => 'hrm',
            'sub_report' => 'employee_list',
            'label' => 'List Karyawan RU',
            'title' => 'List Karyawan RU',
            'source_file' => 'storage/app/xml_sources/RU/hrm/AnlReports.HRM.EmployeeList.xml',
            'headers' => [
                'No',
                'Nama',
                'Jenis Kelamin',
                'Usia',
                'Jabatan',
                'Lama Bekerja',
                'Keterangan',
                'Nama Tempat Ibadah',
                'Lemari',
            ],
            'rows' => $rows,
            'grouped_rows' => $groupedRows,
            'total_rows' => count($rows),
            'summary' => [
                'department_count' => count($departmentSummary),
                'gender_summary' => $genderSummary,
                'top_departments' => array_slice($departmentSummary, 0, 10, true),
            ],
        ];
    }

    private function formatGender(string $value): string
    {
        return match (strtolower(trim($value))) {
            'male' => 'Pria',
            'female' => 'Wanita',
            default => trim($value),
        };
    }

    private function formatAge(string $value): string
    {
        $age = trim($value);

        return $age !== '' ? $age . ' Thn' : '';
    }

    private function formatWorkingPeriod(string $years, string $months): string
    {
        $yearValue = trim($years) !== '' ? (int) $years : 0;
        $monthValue = trim($months) !== '' ? (int) $months : 0;

        return sprintf('%d Thn %d Bln', $yearValue, $monthValue);
    }

    private function mapPlaceOfWorship(string $religion): string
    {
        return match (strtolower(trim($religion))) {
            'islam' => 'Masjid',
            'kristen', 'katolik', 'protestan' => 'Gereja',
            'buddha' => 'Vihara',
            'hindu' => 'Pura',
            'konghucu' => 'Kelenteng',
            default => '',
        };
    }
}
