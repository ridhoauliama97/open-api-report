<?php

namespace App\Services\Ascends\Shared\Hrm\CustomReports;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LemburBulananReportService
{
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $parsed = $this->parseRows($xmlContents, $sourceLabel);
        $allRows = $parsed['data_rows'];
        $allDepartments = $parsed['all_departments'];

        if ($allRows === []) {
            throw new RuntimeException('Data lembur tidak ditemukan pada XML.');
        }

        $period = $this->resolvePeriod($allRows, $filters);

        $typeCodes = array_unique(array_map(
            static fn (array $row): string => strtoupper(trim((string) ($row['DailyWorkerTypeCode'] ?? ''))),
            $allRows
        ));

        $typeLabel = match (true) {
            count($typeCodes) === 1 && $typeCodes[0] === 'ST' => 'Staff',
            default => 'KK/KT',
        };

        $employees = $this->aggregateEmployees($allRows);
        $groupedRows = $this->groupByDepartment($employees);
        $grandSummary = $this->grandSummary($groupedRows, $allRows);
        $departmentLegends = $this->buildDepartmentLegends($allDepartments, $groupedRows);

        $title = 'Laporan Lembur Bulanan Per Departemen ('.$typeLabel.')';

        return [
            'title' => $title,
            'headerTitle' => $title,
            'type_label' => $typeLabel,
            'subtitle' => 'Periode : '.$period['label'],
            'grouped_rows' => $groupedRows,
            'grand_summary' => $grandSummary,
            'department_legends' => $departmentLegends,
            'period' => $period,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
        ];
    }

    /**
     * @return array{data_rows: array<int, array<string, string>>, all_departments: array<int, string>}
     */
    private function parseRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML wajib dikirim.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA)) {
            throw new RuntimeException("File XML tidak valid ({$sourceLabel}).");
        }

        $dataRows = [];
        $allDepartments = [];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'table') {
                continue;
            }

            $nodeXml = $reader->readOuterXml();
            if (! is_string($nodeXml) || trim($nodeXml) === '') {
                continue;
            }

            $node = simplexml_load_string($nodeXml);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $row[$key] = trim((string) $value);
            }

            $deptName = $row['DepartmentName'] ?? '';
            if ($deptName !== '' && ! in_array($deptName, $allDepartments, true)) {
                $allDepartments[] = $deptName;
            }

            if (($row['EmployeeID'] ?? '') !== '') {
                $dataRows[] = $row;
            }
        }

        $reader->close();

        return [
            'data_rows' => $dataRows,
            'all_departments' => $allDepartments,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, mixed>  $filters
     * @return array{start_date: string, end_date: string, label: string}
     */
    private function resolvePeriod(array $rows, array $filters): array
    {
        $startDate = trim((string) ($filters['AttendanceDate.StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['AttendanceDate.EndDate'] ?? $filters['end_date'] ?? ''));

        if ($startDate !== '' && $endDate !== '') {
            try {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);

                return [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'label' => 'Dari '.$start->locale('id')->translatedFormat('d-M-y').' s/d '.$end->locale('id')->translatedFormat('d-M-y'),
                ];
            } catch (Throwable) {
            }
        }

        $dates = array_filter(array_map(
            fn (array $row): ?Carbon => $this->parseDate($row['Date'] ?? ''),
            $rows
        ));

        if ($dates === []) {
            $today = Carbon::today();

            return [
                'start_date' => $today->copy()->startOfMonth()->toDateString(),
                'end_date' => $today->toDateString(),
                'label' => $today->locale('id')->translatedFormat('F Y'),
            ];
        }

        $min = min($dates);
        $max = max($dates);

        return [
            'start_date' => $min->toDateString(),
            'end_date' => $max->toDateString(),
            'label' => 'Dari '.$min->locale('id')->translatedFormat('d-M-y').' s/d '.$max->locale('id')->translatedFormat('d-M-y'),
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, array{code: string, nama: string, sex: string, jabatan: string, jam: float, total_hari: int, total_lemburan: float}>
     */
    private function aggregateEmployees(array $rows): array
    {
        $employees = [];

        foreach ($rows as $row) {
            $code = trim((string) ($row['EmployeeCode'] ?? ''));
            if ($code === '') {
                continue;
            }

            if (! isset($employees[$code])) {
                $employees[$code] = [
                    'code' => $code,
                    'nama' => trim((string) ($row['FullName'] ?? '')),
                    'sex' => trim((string) ($row['Sex'] ?? '')),
                    'jabatan' => trim((string) ($row['JobTitle'] ?? '')),
                    'department' => trim((string) ($row['DepartmentName'] ?? '')),
                    'jam' => 0.0,
                    'total_hari' => 0,
                    'total_lemburan' => 0.0,
                ];
            }

            $employees[$code]['jam'] += (float) ($row['ActualHours'] ?? 0);
            $employees[$code]['total_hari']++;
            $employees[$code]['total_lemburan'] += (float) ($row['Total'] ?? 0);
        }

        return $employees;
    }

    /**
     * @param  array<string, array>  $employees
     * @return array<int, array{department: string, rows: array<int, array>, sub_total: int, akumulasi_lp: array, akumulasi_lembur: array, akumulasi_persen: array}>
     */
    private function groupByDepartment(array $employees): array
    {
        $byDept = [];

        foreach ($employees as $emp) {
            $dept = $emp['department'];
            if (! isset($byDept[$dept])) {
                $byDept[$dept] = ['department' => $dept, 'rows' => []];
            }

            $byDept[$dept]['rows'][] = $emp;
        }

        $departmentNames = array_keys($byDept);
        sort($departmentNames, SORT_NATURAL | SORT_FLAG_CASE);

        $grouped = [];

        foreach ($departmentNames as $dept) {
            $rows = $byDept[$dept]['rows'];

            usort($rows, static fn (array $a, array $b): int => strnatcasecmp(
                (string) ($a['nama'] ?? ''),
                (string) ($b['nama'] ?? '')
            ));

            $totalLembur = array_sum(array_column($rows, 'total_lemburan'));
            $totalLaki = 0;
            $totalPerempuan = 0;
            $lemburValues = [];
            $persenValues = [];

            foreach ($rows as &$row) {
                $row['persen'] = $totalLembur > 0 ? round(($row['total_lemburan'] / $totalLembur) * 100, 1) : 0.0;
                $row['sex_label'] = strtoupper(trim((string) ($row['sex'] ?? ''))) === 'MALE' ? 'L' : 'P';

                if ($row['sex_label'] === 'L') {
                    $totalLaki++;
                } else {
                    $totalPerempuan++;
                }

                $lemburValues[] = (int) $row['total_lemburan'];
                $persenValues[] = $row['persen'];
            }
            unset($row);

            $totalEmployees = count($rows);
            $grouped[] = [
                'department' => $dept,
                'rows' => $rows,
                'sub_total' => $totalEmployees,
                'akumulasi_lp' => [
                    'L' => $totalLaki,
                    'P' => $totalPerempuan,
                    'L_persen' => $totalEmployees > 0 ? round(($totalLaki / $totalEmployees) * 100) : 0,
                    'P_persen' => $totalEmployees > 0 ? round(($totalPerempuan / $totalEmployees) * 100) : 0,
                ],
                'akumulasi_lembur' => [
                    'min' => $lemburValues !== [] ? (int) min($lemburValues) : 0,
                    'max' => $lemburValues !== [] ? (int) max($lemburValues) : 0,
                    'avg' => $lemburValues !== [] ? (int) round(array_sum($lemburValues) / count($lemburValues)) : 0,
                ],
                'akumulasi_persen' => [
                    'min' => $persenValues !== [] ? round(min($persenValues), 1) : 0.0,
                    'max' => $persenValues !== [] ? round(max($persenValues), 1) : 0.0,
                    'avg' => $persenValues !== [] ? round(array_sum($persenValues) / count($persenValues), 1) : 0.0,
                ],
            ];
        }

        return $grouped;
    }

    /**
     * @param  array<int, array>  $groupedRows
     * @param  array<int, array<string, string>>  $allRows
     * @return array{grand_total_employees: int, department_totals: array<int, array>, grand_total_lembur: int}
     */
    private function grandSummary(array $groupedRows, array $allRows): array
    {
        $grandTotalEmployees = 0;
        $departmentTotals = [];
        $grandTotalLembur = 0;

        foreach ($groupedRows as $group) {
            $totalLembur = (int) array_sum(array_column($group['rows'], 'total_lemburan'));
            $departmentTotals[] = [
                'department' => $group['department'],
                'total_lembur' => $totalLembur,
            ];
            $grandTotalEmployees += $group['sub_total'];
            $grandTotalLembur += $totalLembur;
        }

        return [
            'grand_total_employees' => $grandTotalEmployees,
            'department_totals' => $departmentTotals,
            'grand_total_lembur' => $grandTotalLembur,
        ];
    }

    /**
     * @param  array<int, string>  $allDepartments
     * @param  array<int, array>  $groupedRows
     * @return array<string, string>
     */
    private function buildDepartmentLegends(array $allDepartments, array $groupedRows): array
    {
        $deptsWithData = array_map(static fn (array $g): string => $g['department'], $groupedRows);
        $legends = [];

        foreach ($allDepartments as $dept) {
            if (! in_array($dept, $deptsWithData, true)) {
                $legends[$dept] = 'Tidak Ada Lembur';
            }
        }

        ksort($legends, SORT_NATURAL | SORT_FLAG_CASE);

        return $legends;
    }

    private function parseDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
