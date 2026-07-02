<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class DurasiDendaKeterlambatanReportService
{
    private const TITLE = 'Laporan Durasi & Denda Keterlambatan';

    private const START_DATE_ALIASES = [
        'start_date',
        'StartDate',
        'startDate',
        'date_start',
        'DateStart',
        'from_date',
        'FromDate',
        'TglAwal',
        'TanggalAwal',
        'AttendanceDate.StartDate',
        'AttendanceDate_StartDate',
        'AttendanceDate_x002e_StartDate',
        'AttendanceDate_x0020_StartDate',
    ];

    private const END_DATE_ALIASES = [
        'end_date',
        'EndDate',
        'endDate',
        'date_end',
        'DateEnd',
        'to_date',
        'ToDate',
        'TglAkhir',
        'TanggalAkhir',
        'AttendanceDate.EndDate',
        'AttendanceDate_EndDate',
        'AttendanceDate_x002e_EndDate',
        'AttendanceDate_x0020_EndDate',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rawRows = $this->parseAbsenRows($xmlContents, $sourceLabel);
        $period = self::resolvePeriod($rawRows, $filters);
        $type = self::resolveType($filters);
        $dateInput = self::resolveDateInput($filters, $period);
        $employees = self::aggregateEmployees($rawRows, $period, $type, $dateInput);
        $groupedRows = self::groupRows($employees);
        $rows = array_merge(...array_map(static fn (array $group): array => $group['rows'], $groupedRows));

        return [
            'title' => self::TITLE,
            'type' => $type,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => self::resolvePrintedBy($rawRows),
            'headers' => ['No', 'Nama', 'Jabatan', 'Level', 'Total Menit', 'Durasi', 'Denda'],
            'rows' => $rows,
            'grouped_rows' => $groupedRows,
            'grand_summary' => [
                'total_minutes' => array_sum(array_map(static fn (array $row): int => (int) ($row['total_minutes_value'] ?? 0), $rows)),
                'total_nominal' => array_sum(array_map(static fn (array $row): int => (int) ($row['nominal_value'] ?? 0), $rows)),
            ],
            'total_rows' => count($rows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
            'date_input' => $dateInput->toDateString(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseAbsenRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML LateEarly kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML LateEarly tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'absen') {
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
            throw new RuntimeException('XML LateEarly tidak memiliki record absen.');
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
        $startDate = self::filterValue($filters, self::START_DATE_ALIASES);
        $endDate = self::filterValue($filters, self::END_DATE_ALIASES);

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
            'start' => $dates[0]->copy()->startOfMonth()->startOfDay(),
            'end' => $dates[count($dates) - 1]->copy()->endOfMonth()->endOfDay(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{start: Carbon, end: Carbon}  $period
     */
    private static function resolveDateInput(array $filters, array $period): Carbon
    {
        $value = self::filterValue($filters, ['DateInput', 'date_input', 'TglInput', 'tanggal_input', 'effective_date']);

        return self::parseDate($value) ?? $period['start']->copy()->subDay()->startOfDay();
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
     * @param  array<int, array<string, string>>  $rows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<string, array<string, mixed>>
     */
    private static function aggregateEmployees(array $rows, array $period, string $type, Carbon $dateInput): array
    {
        $employees = [];

        foreach ($rows as $row) {
            $employeeCode = trim((string) ($row['Employee_x0020_Code'] ?? ''));
            $date = self::parseDate((string) ($row['Date'] ?? ''));
            $lateMinutes = (int) ($row['Late_x0020_Sign_x0020_In'] ?? 0);
            $ignoreLate = trim((string) ($row['Ignore_x0020_Late'] ?? ''));
            $department = trim((string) ($row['Department_x0020_Name'] ?? ''));
            $departmentCode = trim((string) ($row['Department_x0020_Code'] ?? ''));

            if (
                $employeeCode === ''
                || $date === null
                || ! $date->betweenIncluded($period['start'], $period['end'])
                || strcasecmp($ignoreLate, 'Ignore') === 0
                || $lateMinutes <= 0
                || ! self::matchesType($row, $type)
                || str_starts_with($departmentCode, '0101')
                || str_starts_with(strtoupper($department), 'MANAGEMENT')
                || ($type === 'Staff' && self::isWithoutDepartment($department))
            ) {
                continue;
            }

            if (! isset($employees[$employeeCode])) {
                $employees[$employeeCode] = [
                    'code' => $employeeCode,
                    'name' => trim((string) ($row['Full_x0020_Name'] ?? '')),
                    'job_title' => trim((string) ($row['Job_x0020_Title'] ?? '')),
                    'department' => $department,
                    'level' => trim((string) ($row['Level'] ?? '')),
                    'worker_type' => self::typeKar($row),
                    'total_minutes' => 0,
                    'nominal' => 0,
                    'details' => [],
                ];
            }

            $nominal = self::nominal($row, $date, $lateMinutes, $dateInput);
            $employees[$employeeCode]['total_minutes'] += $lateMinutes;
            $employees[$employeeCode]['nominal'] += $nominal;
            $employees[$employeeCode]['details'][] = [
                'date' => $date,
                'sign_in' => self::formatDateTime((string) ($row['Sign_x0020_In'] ?? '')),
                'late_minutes' => $lateMinutes,
                'nominal' => $nominal,
            ];
        }

        return $employees;
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function matchesType(array $row, string $type): bool
    {
        $typeKar = self::typeKar($row);

        return $type === 'Staff' ? $typeKar === 'ST' : $typeKar === 'KK/KT';
    }

    private static function isWithoutDepartment(string $department): bool
    {
        $department = trim($department);

        return $department === '' || strcasecmp($department, 'Tanpa Departemen') === 0;
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function typeKar(array $row): string
    {
        $workerType = strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? '')));

        return in_array($workerType, ['KK', 'KT', 'BR'], true) ? 'KK/KT' : 'ST';
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function nominal(array $row, Carbon $date, int $lateMinutes, Carbon $dateInput): int
    {
        $workerType = strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? '')));
        if ($workerType === 'ST') {
            return ($date->greaterThan($dateInput) ? 1500 : 1000) * $lateMinutes;
        }

        return ($date->greaterThan($dateInput) ? 400 : 250) * $lateMinutes;
    }

    /**
     * @param  array<string, array<string, mixed>>  $employees
     * @return array<int, array<string, mixed>>
     */
    private static function groupRows(array $employees): array
    {
        uasort($employees, static function (array $left, array $right): int {
            $minutesCompare = (int) ($right['total_minutes'] ?? 0) <=> (int) ($left['total_minutes'] ?? 0);
            if ($minutesCompare !== 0) {
                return $minutesCompare;
            }

            return [
                (string) ($left['department'] ?? ''),
                (string) ($left['name'] ?? ''),
                (string) ($left['code'] ?? ''),
            ] <=> [
                (string) ($right['department'] ?? ''),
                (string) ($right['name'] ?? ''),
                (string) ($right['code'] ?? ''),
            ];
        });

        $groups = [];
        foreach ($employees as $employee) {
            $department = trim((string) ($employee['department'] ?? '')) ?: 'Tanpa Departemen';
            $totalMinutes = (int) ($employee['total_minutes'] ?? 0);
            $hours = intdiv($totalMinutes, 60);
            $minutes = $totalMinutes % 60;
            $nominal = (int) ($employee['nominal'] ?? 0);

            $groups[$department]['department'] = $department;
            $groups[$department]['rows'][] = [
                'Nama' => (string) ($employee['name'] ?? ''),
                'Jabatan' => (string) ($employee['job_title'] ?? ''),
                'Level' => (string) ($employee['level'] ?? ''),
                'Total Menit' => $totalMinutes,
                'Durasi' => trim("{$hours} Jam {$minutes} Menit"),
                'Denda' => 'Rp '.number_format($nominal, 0, '.', ','),
                'details' => self::sortDetails((array) ($employee['details'] ?? [])),
                'total_minutes_value' => $totalMinutes,
                'nominal_value' => $nominal,
            ];
            $groups[$department]['summary']['total_minutes'] = (int) ($groups[$department]['summary']['total_minutes'] ?? 0) + $totalMinutes;
            $groups[$department]['summary']['total_nominal'] = (int) ($groups[$department]['summary']['total_nominal'] ?? 0) + $nominal;
        }

        uasort($groups, static function (array $left, array $right): int {
            $minutesCompare = (int) ($right['summary']['total_minutes'] ?? 0) <=> (int) ($left['summary']['total_minutes'] ?? 0);
            if ($minutesCompare !== 0) {
                return $minutesCompare;
            }

            return (string) ($left['department'] ?? '') <=> (string) ($right['department'] ?? '');
        });

        $grandTotalMinutes = array_sum(array_map(static fn (array $group): int => (int) ($group['summary']['total_minutes'] ?? 0), $groups));
        foreach ($groups as &$group) {
            $minutes = (int) ($group['summary']['total_minutes'] ?? 0);
            $group['summary']['duration'] = self::durationText($minutes);
            $group['summary']['percent'] = $grandTotalMinutes > 0 ? (int) round($minutes / $grandTotalMinutes * 100) : 0;
        }
        unset($group);

        return array_values($groups);
    }

    /**
     * @param  array<int, array<string, mixed>>  $details
     * @return array<int, array<string, mixed>>
     */
    private static function sortDetails(array $details): array
    {
        usort($details, static fn (array $left, array $right): int => ($left['date'] ?? null) <=> ($right['date'] ?? null));

        return $details;
    }

    private static function durationText(int $totalMinutes): string
    {
        return intdiv($totalMinutes, 60).' Jam '.($totalMinutes % 60).' Menit';
    }

    private static function formatDateTime(string $value): string
    {
        $date = self::parseDateTime($value);

        return $date?->locale('id')->translatedFormat('d-M-y H:i:s') ?? '';
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private static function resolvePrintedBy(array $rows): string
    {
        foreach ($rows as $row) {
            foreach (['Sys_Username', 'Sys_UserName', 'Printed_x0020_By', 'Created_x0020_By'] as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach ([DATE_ATOM, 'Y-m-d\TH:i:sP', 'Y-m-d H:i:s', 'Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->startOfDay();
                }
            } catch (Throwable) {
                // Try next format.
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private static function parseDateTime(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach ([DATE_ATOM, 'Y-m-d\TH:i:sP', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date;
                }
            } catch (Throwable) {
                // Try next format.
            }
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
