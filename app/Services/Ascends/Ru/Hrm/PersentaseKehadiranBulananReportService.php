<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PersentaseKehadiranBulananReportService
{
    private const TITLE = 'Laporan Persentase Kehadiran Bulanan';

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rawRows = $this->parseAttendanceRows($xmlContents, $sourceLabel);
        $period = self::resolvePeriod($rawRows, $filters);
        $company = strtoupper(trim((string) ($filters['company'] ?? '')));
        $type = self::resolveType($filters);
        $months = self::resolveMonths($period);
        $monthLabels = self::resolveMonthLabels($period);
        $employees = self::aggregateEmployees($rawRows, $period, $company, $type);
        $groupedRows = self::groupRows($employees, $months, $period);
        $rows = array_merge(...array_map(static fn (array $group): array => $group['rows'], $groupedRows));

        return [
            'title' => self::TITLE,
            'type' => $type,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => self::resolvePrintedBy($rawRows),
            'headers' => array_merge(['Nama', 'Jabatan', 'Masa Kerja'], array_values($monthLabels), ['Total < 93%']),
            'months' => $months,
            'month_labels' => $monthLabels,
            'rows' => $rows,
            'grouped_rows' => $groupedRows,
            'grand_summary' => [
                'subtotal' => array_sum(array_map(static fn (array $group): int => (int) ($group['summary']['subtotal'] ?? 0), $groupedRows)),
            ],
            'total_rows' => count($rows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseAttendanceRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Attendance Full kosong.');
        }

        $reader = new XMLReader;
        $opened = @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET);
        if ($opened === false) {
            throw new RuntimeException("XML Attendance Full tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'attendance') {
                continue;
            }

            $recordXml = $reader->readOuterXML();
            if (!is_string($recordXml) || trim($recordXml) === '') {
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
            throw new RuntimeException('XML Attendance Full tidak memiliki record Attendance.');
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, mixed>  $filters
     * @return array{start: Carbon, end: Carbon}
     */
    private static function resolvePeriod(array $rows, array $filters): array
    {
        $startDate = trim((string) ($filters['start_date'] ?? $filters['TglAwal'] ?? ''));
        $endDate = trim((string) ($filters['end_date'] ?? $filters['TglAkhir'] ?? ''));

        if ($startDate !== '' || $endDate !== '') {
            $start = self::parseDate($startDate) ?? self::parseDate($endDate);
            $end = self::parseDate($endDate) ?? self::parseDate($startDate);

            if ($start !== null && $end !== null) {
                if ($end->lessThan($start)) {
                    [$start, $end] = [$end, $start];
                }

                return ['start' => $start->startOfDay(), 'end' => $end->endOfDay()];
            }
        }

        $dates = array_values(array_filter(array_map(
            static fn (array $row): ?Carbon => self::parseDate((string) ($row['Date'] ?? '')),
            $rows
        )));

        if ($dates === []) {
            $now = Carbon::now()->startOfMonth();

            return ['start' => $now->copy()->startOfDay(), 'end' => $now->copy()->endOfMonth()->endOfDay()];
        }

        usort($dates, static fn (Carbon $left, Carbon $right): int => $left <=> $right);

        return [
            'start' => $dates[0]->copy()->startOfDay(),
            'end' => $dates[count($dates) - 1]->copy()->endOfDay(),
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<int, int>
     */
    private static function resolveMonths(array $period): array
    {
        $months = [];
        $cursor = $period['start']->copy()->startOfMonth();
        $end = $period['end']->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            $months[] = (int) $cursor->month;
            $cursor->addMonthNoOverflow();
        }

        return $months;
    }

    /**
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<int, string>
     */
    private static function resolveMonthLabels(array $period): array
    {
        $labels = [];
        $cursor = $period['start']->copy()->startOfMonth();
        $end = $period['end']->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            $labels[(int) $cursor->month] = $cursor->locale('id')->translatedFormat('M-y');
            $cursor->addMonthNoOverflow();
        }

        return $labels;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<string, array<string, mixed>>
     */
    private static function aggregateEmployees(array $rows, array $period, string $company, string $type): array
    {
        $employees = [];
        $holidayDates = self::resolveHolidayDates($rows, $period);

        foreach ($rows as $row) {
            $employeeCode = trim((string) ($row['Employee_x0020_Code'] ?? ''));
            $date = self::parseDate((string) ($row['Date'] ?? ''));
            if (
                $employeeCode === ''
                || str_starts_with(strtoupper($employeeCode), 'SPECIAL')
                || $date === null
                || !$date->betweenIncluded($period['start'], $period['end'])
            ) {
                continue;
            }

            $department = trim((string) ($row['Department_x0020_Name'] ?? ''));
            if (!self::matchesType($row, $department, $type)) {
                continue;
            }

            if (!isset($employees[$employeeCode])) {
                $employees[$employeeCode] = [
                    'code' => $employeeCode,
                    'name' => trim((string) ($row['Full_x0020_Name'] ?? '')),
                    'job_title' => trim((string) ($row['Job_x0020_Title'] ?? '')),
                    'department' => $department,
                    'join_date' => self::parseDate((string) ($row['Join_x0020_Date'] ?? '')),
                    'months' => [],
                ];
            }

            $month = (int) $date->month;
            $employees[$employeeCode]['months'][$month]['total_days'] = (int) ($employees[$employeeCode]['months'][$month]['total_days'] ?? 0) + 1;

            if (self::hasAttendanceCredit($row, $holidayDates, $company)) {
                $employees[$employeeCode]['months'][$month]['attendance_credit_days'] = (int) ($employees[$employeeCode]['months'][$month]['attendance_credit_days'] ?? 0) + 1;
            }
        }

        return $employees;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function resolveType(array $filters): string
    {
        $value = self::filterValue($filters, ['Pilih Type', 'Pilih_x0020_Type', 'pilih_type', 'pilihType', 'type', 'Type']);

        return str_contains(strtoupper($value), 'STAFF') ? 'Staff' : 'KK/KT';
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $aliases
     */
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

    /**
     * @param  array<string, string>  $row
     */
    private static function matchesType(array $row, string $department, string $type): bool
    {
        $departmentUpper = strtoupper(trim($department));
        if (str_starts_with($departmentUpper, 'ODP') || str_starts_with($departmentUpper, 'MANAGEMENT')) {
            return false;
        }

        $status = strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? '')));

        if ($type === 'Staff') {
            return str_starts_with($status, 'ST');
        }

        return str_starts_with($status, 'KK')
            || str_starts_with($status, 'KT')
            || str_starts_with($status, 'BR');
    }

    /**
     * @param  array<string, array<string, mixed>>  $employees
     * @param  array<int, int>  $months
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<int, array{department: string, rows: array<int, array<string, mixed>>, summary: array<string, int>}>
     */
    private static function groupRows(array $employees, array $months, array $period): array
    {
        $departmentEmployees = [];
        foreach ($employees as $employee) {
            $department = trim((string) ($employee['department'] ?? ''));
            $departmentEmployees[$department][] = $employee;
        }

        ksort($departmentEmployees, SORT_NATURAL | SORT_FLAG_CASE);

        $groups = [];
        foreach ($departmentEmployees as $department => $employeesInDepartment) {
            usort($employeesInDepartment, static fn (array $left, array $right): int => (string) ($left['name'] ?? '') <=> (string) ($right['name'] ?? ''));

            $rows = [];
            foreach ($employeesInDepartment as $employee) {
                $row = self::monthlyRow($employee, $months, $period);
                if ((int) ($row['Total < 93%'] ?? 0) > 0) {
                    $rows[] = $row;
                }
            }

            $groups[] = [
                'department' => $department,
                'rows' => $rows,
                'summary' => ['subtotal' => count($rows)],
            ];
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $employee
     * @param  array<int, int>  $months
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<string, mixed>
     */
    private static function monthlyRow(array $employee, array $months, array $period): array
    {
        $row = [
            'Nama' => (string) ($employee['name'] ?? ''),
            'Jabatan' => (string) ($employee['job_title'] ?? ''),
            'Masa Kerja' => self::formatWorkingPeriod($employee['join_date'] ?? null, $period['end']),
            'Total < 93%' => 0,
        ];

        foreach ($months as $month) {
            $stats = $employee['months'][$month] ?? [];
            $score = (int) ($stats['attendance_credit_days'] ?? 0);
            $total = (int) ($stats['total_days'] ?? 0);
            $percentage = $score > 0 && $total > 0
                ? (int) round(min(100, max(0, ($score / $total) * 100)))
                : null;

            $row[(string) $month] = $percentage === null ? '' : $percentage.'%';
            if ($percentage !== null && $percentage < 93) {
                $row['Total < 93%']++;
            }
        }

        return $row;
    }

    private static function formatWorkingPeriod(mixed $joinDate, Carbon $endDate): string
    {
        if (!$joinDate instanceof Carbon) {
            return '';
        }

        $diff = $joinDate->diff($endDate);

        return $diff->y.' Thn '.$diff->m.' Bln';
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, true>  $holidayDates
     */
    private static function hasAttendanceCredit(array $row, array $holidayDates, string $company): bool
    {
        $scheduledShift = strtoupper(trim((string) ($row['Scheduled_x0020_Shift'] ?? '')));
        if (self::isValidPresent($row, $company)) {
            return true;
        }
        if (self::isPresent($row)) {
            $date = self::parseDate((string) ($row['Date'] ?? ''));

            return $company !== 'GSU'
                && $date !== null
                && $scheduledShift === 'OFF'
                && $date->isSunday();
        }

        $date = self::parseDate((string) ($row['Date'] ?? ''));
        if ($date === null || isset($holidayDates[$date->toDateString()])) {
            return false;
        }

        return $scheduledShift === 'OFF' && $date->isSunday();
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function isPresent(array $row): bool
    {
        return strcasecmp((string) ($row['Present_x002F_Absent'] ?? ''), 'Present') === 0;
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function isValidPresent(array $row, string $company): bool
    {
        if (!self::isPresent($row)) {
            return false;
        }

        $scheduledShift = strtoupper(trim((string) ($row['Scheduled_x0020_Shift'] ?? '')));
        $shift = strtoupper(trim((string) ($row['Shift'] ?? '')));
        if ($company === 'GSU' && $scheduledShift === 'OFF' && $shift === 'OFF') {
            return false;
        }

        return trim((string) ($row['Sign_x0020_In'] ?? '')) !== '';
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<string, true>
     */
    private static function resolveHolidayDates(array $rows, array $period): array
    {
        $holidayDates = [];
        $dateStats = [];

        foreach ($rows as $row) {
            $date = self::parseDate((string) ($row['Date'] ?? ''));
            if ($date === null || !$date->betweenIncluded($period['start'], $period['end'])) {
                continue;
            }

            $dateKey = $date->toDateString();
            $dateStats[$dateKey]['total'] = (int) ($dateStats[$dateKey]['total'] ?? 0) + 1;
            if (strtoupper(trim((string) ($row['Scheduled_x0020_Shift'] ?? ''))) === 'OFF') {
                $dateStats[$dateKey]['scheduled_off'] = (int) ($dateStats[$dateKey]['scheduled_off'] ?? 0) + 1;
            }

            if (trim((string) ($row['Holiday'] ?? '')) !== '') {
                $holidayDates[$date->toDateString()] = true;
            }
        }

        foreach ($dateStats as $dateKey => $stats) {
            $date = self::parseDate($dateKey);
            if ($date === null || $date->isSunday()) {
                continue;
            }

            if (
                (int) ($stats['total'] ?? 0) > 0
                && (int) ($stats['scheduled_off'] ?? 0) === (int) ($stats['total'] ?? 0)
            ) {
                $holidayDates[$dateKey] = true;
            }
        }

        return $holidayDates;
    }

    private static function parseDate(string $value): ?Carbon
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

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private static function resolvePrintedBy(array $rows): string
    {
        foreach ($rows as $row) {
            foreach (['Created_x0020_By', 'Last_x0020_Modified_x0020_By'] as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }
}
