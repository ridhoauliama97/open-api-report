<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PersentaseKehadiranMingguanPerDepartemenReportService
{
    private const TITLE = 'Laporan Persentase Kehadiran Mingguan Per Departemen';

    /**
     * @var array<string, string>
     */
    private const GENDER_LABELS = [
        'L' => 'Laki-Laki',
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

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rawRows = $this->parseAttendanceRows($xmlContents, $sourceLabel);
        $period = self::resolvePeriod($rawRows, $filters);
        $company = strtoupper(trim((string) ($filters['company'] ?? '')));
        $employees = self::aggregateEmployees($rawRows, $period, $company);
        $rows = self::shapeRows($employees);

        usort($rows, static fn (array $left, array $right): int => [
            (string) ($left['Departemen'] ?? ''),
            -1 * (int) ($left['Persentase'] ?? 0),
            (string) ($left['Nama'] ?? ''),
        ] <=> [
            (string) ($right['Departemen'] ?? ''),
            -1 * (int) ($right['Persentase'] ?? 0),
            (string) ($right['Nama'] ?? ''),
        ]);

        $groupedRows = self::groupRows($rows);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => self::resolvePrintedBy($rawRows),
            'headers' => ['No', 'Nama', 'L/P', 'Jabatan', 'Status', 'Level', '%'],
            'rows' => array_map(static fn (array $row): array => self::publicRow($row), $rows),
            'grouped_rows' => $groupedRows,
            'grand_summary' => self::buildSummary($rows),
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
            if (! is_string($recordXml) || trim($recordXml) === '') {
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
     * @param  array<int, array<string, string>>  $rows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<string, array<string, mixed>>
     */
    private static function aggregateEmployees(array $rows, array $period, string $company): array
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
                || ! $date->betweenIncluded($period['start'], $period['end'])
            ) {
                continue;
            }

            $department = trim((string) ($row['Department_x0020_Name'] ?? ''));
            $key = $employeeCode;
            if (! isset($employees[$key])) {
                $employees[$key] = [
                    'code' => $employeeCode,
                    'name' => trim((string) ($row['Full_x0020_Name'] ?? '')),
                    'gender' => self::formatGender((string) ($row['Sex'] ?? '')),
                    'job_title' => trim((string) ($row['Job_x0020_Title'] ?? '')),
                    'status' => strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? ''))),
                    'level' => self::formatLevel((string) ($row['Level'] ?? '')),
                    'level_summary' => self::formatLevelSummary((string) ($row['Level'] ?? '')),
                    'department' => $department,
                    'attendance_credit_days' => 0,
                    'valid_present_days' => 0,
                    'total_days' => 0,
                ];
            }

            $employees[$key]['total_days']++;
            if (self::isValidPresent($row, $company)) {
                $employees[$key]['valid_present_days']++;
            }

            if (self::hasAttendanceCredit($row, $holidayDates, $company)) {
                $employees[$key]['attendance_credit_days']++;
            }
        }

        return $employees;
    }

    /**
     * @param  array<string, mixed>  $employee
     * @return array<int, array<string, string|int>>
     */
    private static function shapeRows(array $employees): array
    {
        $rows = [];

        foreach ($employees as $employee) {
            $score = (int) ($employee['attendance_credit_days'] ?? 0);
            $maxim = (int) ($employee['total_days'] ?? 0);
            $percentageRaw = $score > 0 && $maxim > 0
                ? min(100, max(0, ($score / $maxim) * 100))
                : 0.0;
            $percentage = (int) round($percentageRaw);

            $rows[] = [
                'Nama' => (string) ($employee['name'] ?? ''),
                'L/P' => (string) ($employee['gender'] ?? ''),
                'Jabatan' => (string) ($employee['job_title'] ?? ''),
                'Status' => (string) ($employee['status'] ?? ''),
                'Level' => (string) ($employee['level'] ?? ''),
                'Level Summary' => (string) ($employee['level_summary'] ?? ''),
                'Persentase' => $percentage,
                'Persentase Raw' => $percentageRaw,
                'Persentase Text' => $percentage.'%',
                'Valid Present Days' => (int) ($employee['valid_present_days'] ?? 0),
                'Departemen' => (string) ($employee['department'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return array<int, array{label: string, rows: array<int, array<string, string>>, summary: array<string, mixed>}>
     */
    private static function groupRows(array $rows): array
    {
        $departmentRows = [];
        foreach ($rows as $row) {
            $department = trim((string) ($row['Departemen'] ?? ''));
            $departmentRows[$department][] = $row;
        }

        ksort($departmentRows, SORT_NATURAL | SORT_FLAG_CASE);

        $groups = [];
        foreach ($departmentRows as $department => $rowsInDepartment) {
            $groups[] = [
                'label' => 'Departemen : '.$department,
                'rows' => array_map(static fn (array $row): array => self::publicRow($row), $rowsInDepartment),
                'summary' => self::buildSummary($rowsInDepartment),
            ];
        }

        return $groups;
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
            '%' => (string) ($row['Persentase Text'] ?? ''),
        ];
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return array<string, mixed>
     */
    private static function buildSummary(array $rows): array
    {
        $percentages = array_map(static fn (array $row): int => (int) ($row['Persentase'] ?? 0), $rows);
        $rawPercentages = array_map(static fn (array $row): float => (float) ($row['Persentase Raw'] ?? $row['Persentase'] ?? 0), $rows);
        $minimumRows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => ! (
                strtoupper((string) ($row['Status'] ?? '')) === 'BR'
                && (int) ($row['Valid Present Days'] ?? 0) <= 1
            )
        ));
        $minimumPercentages = $minimumRows === []
            ? $percentages
            : array_map(static fn (array $row): int => (int) ($row['Persentase'] ?? 0), $minimumRows);

        return [
            'subtotal' => count($rows),
            'gender' => self::countWithPercent($rows, 'L/P', self::GENDER_LABELS),
            'status' => self::countWithPercent($rows, 'Status', self::STATUS_LABELS),
            'level' => self::countWithPercent($rows, 'Level Summary', self::LEVEL_LABELS),
            'attendance_percentage' => [
                'min' => $minimumPercentages === [] ? 0 : min($minimumPercentages),
                'max' => $percentages === [] ? 0 : max($percentages),
                'avg' => $rawPercentages === [] ? 0 : (int) round(array_sum($rawPercentages) / count($rawPercentages)),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @param  array<string, string>  $defaultLabels
     * @return array<string, array{label: string, count: int, percent: int}>
     */
    private static function countWithPercent(array $rows, string $field, array $defaultLabels): array
    {
        $total = count($rows);
        $result = [];

        foreach ($defaultLabels as $key => $label) {
            $count = count(array_filter(
                $rows,
                static fn (array $row): bool => strtoupper((string) ($row[$field] ?? '')) === strtoupper($key)
            ));
            $result[$key] = [
                'label' => $label,
                'count' => $count,
                'percent' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        return $result;
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
        if (! self::isPresent($row)) {
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
            if ($date === null || ! $date->betweenIncluded($period['start'], $period['end'])) {
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

    private static function formatGender(string $sex): string
    {
        $value = strtoupper(trim($sex));

        return match ($value) {
            'MALE', 'L' => 'L',
            'FEMALE', 'P' => 'P',
            default => trim($sex),
        };
    }

    private static function formatLevel(string $level): string
    {
        $value = trim($level);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^level\s*(.+)$/i', $value, $matches) === 1) {
            return trim($matches[1]);
        }

        return $value;
    }

    private static function formatLevelSummary(string $level): string
    {
        $formatted = self::formatLevel($level);

        return $formatted !== '' ? 'Level '.$formatted : 'Level ';
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
