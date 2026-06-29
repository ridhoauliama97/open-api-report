<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LemburBulananReportService
{
    private const TITLE = 'Laporan Lembur Bulanan';

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $parsed = $this->parseXml($xmlContents, $sourceLabel);
        $rows = $parsed['rows'];
        $period = self::resolvePeriod($rows, $parsed['periods'], $filters);
        $type = self::resolveType($filters);
        $employees = self::aggregateEmployees($rows, $period, $type);
        $groupedRows = self::groupRows($employees);
        $flatRows = array_merge(...array_map(static fn (array $group): array => $group['rows'], $groupedRows));
        $grandSummary = self::grandSummary($groupedRows, $flatRows);

        return [
            'title' => self::TITLE,
            'type' => $type,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => self::resolvePrintedBy($rows),
            'headers' => ['Nama', 'L/P', 'Jabatan', 'Jam', 'Total Hari', 'Total Lemburan', '%'],
            'rows' => $flatRows,
            'grouped_rows' => $groupedRows,
            'grand_summary' => $grandSummary,
            'total_rows' => count($flatRows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d/m/Y').' Sampai '.$period['end']->locale('id')->translatedFormat('d/m/Y'),
            ],
        ];
    }

    /**
     * @return array{rows: array<int, array<string, string>>, periods: array<int, array<string, string>>}
     */
    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Overtime kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Overtime tidak valid: {$sourceLabel}");
        }

        $rows = [];
        $periods = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || ! in_array(strtolower($reader->name), ['overtime', 'table1'], true)) {
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

            $row = array_map(
                static fn (mixed $value): string => is_array($value) ? '' : trim((string) $value),
                json_decode(json_encode($node), true) ?: []
            );

            if (strtolower($reader->name) === 'table1') {
                $periods[] = $row;
            } else {
                $rows[] = $row;
            }
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('XML Overtime tidak memiliki record Overtime.');
        }

        return ['rows' => $rows, 'periods' => $periods];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<int, array<string, string>>  $periods
     * @param  array<string, mixed>  $filters
     * @return array{start: Carbon, end: Carbon}
     */
    private static function resolvePeriod(array $rows, array $periods, array $filters): array
    {
        $startDate = self::filterValue($filters, ['start_date', 'StartDate', 'TglAwal', 'AttendanceDate.StartDate']);
        $endDate = self::filterValue($filters, ['end_date', 'EndDate', 'TglAkhir', 'AttendanceDate.EndDate']);

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

        $firstPeriod = self::parsePeriod((string) ($periods[0]['FirstPeriod'] ?? ''));
        $lastPeriod = self::parsePeriod((string) ($periods[0]['LastPeriod'] ?? ''));
        if ($firstPeriod !== null && $lastPeriod !== null) {
            return [
                'start' => $firstPeriod->copy()->startOfMonth()->startOfDay(),
                'end' => $lastPeriod->copy()->endOfMonth()->endOfDay(),
            ];
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
     */
    private static function resolveType(array $filters): string
    {
        $value = self::filterValue($filters, ['Pilih Tipe', 'Pilih_x0020_Tipe', 'pilih_tipe', 'pilihTipe', 'Pilih Type', 'Pilih_x0020_Type', 'pilih_type', 'type', 'Type', 'tipe', 'Tipe']);

        return str_contains(strtoupper($value), 'STAFF') || strtoupper($value) === 'ST' ? 'ST' : 'KK/KT';
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<string, array<string, mixed>>
     */
    private static function aggregateEmployees(array $rows, array $period, string $type): array
    {
        $employees = [];
        foreach ($rows as $row) {
            $employeeCode = trim((string) ($row['Employee_x0020_Code'] ?? ''));
            $date = self::parseDate((string) ($row['Date'] ?? ''));
            $workHours = self::toFloat($row['Original_x0020_Hours'] ?? 0);
            $overtimeHours = self::toFloat($row['Overtime_x002F_Hours'] ?? 0);

            if (
                $employeeCode === ''
                || $date === null
                || ! $date->betweenIncluded($period['start'], $period['end'])
                || ($workHours <= 0 && $overtimeHours <= 0)
                || ! self::matchesType($row, $type)
            ) {
                continue;
            }

            if (! isset($employees[$employeeCode])) {
                $employees[$employeeCode] = [
                    'code' => $employeeCode,
                    'name' => trim((string) ($row['Full_x0020_Name'] ?? '')),
                    'job_title' => trim((string) ($row['Job_x0020_Title'] ?? '')),
                    'department' => trim((string) ($row['Department_x0020_Name'] ?? '')),
                    'department_code' => trim((string) ($row['Department_x0020_Code'] ?? '')),
                    'level' => trim((string) ($row['Level'] ?? '')),
                    'sex' => self::sexCode((string) ($row['Sex'] ?? '')),
                    'work_hours' => 0.0,
                    'overtime_hours' => 0.0,
                    'dates' => [],
                ];
            }

            $employees[$employeeCode]['work_hours'] += $workHours;
            $employees[$employeeCode]['overtime_hours'] += $overtimeHours;
            $employees[$employeeCode]['dates'][$date->toDateString()] = true;
        }

        return $employees;
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function matchesType(array $row, string $type): bool
    {
        $workerType = strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? '')));
        $typeKar = in_array($workerType, ['KK', 'KT', 'BR'], true) ? 'KK/KT' : 'ST';

        return $type === 'ST' ? $typeKar === 'ST' : $typeKar === 'KK/KT';
    }

    /**
     * @param  array<string, array<string, mixed>>  $employees
     * @return array<int, array<string, mixed>>
     */
    private static function groupRows(array $employees): array
    {
        uasort($employees, static function (array $left, array $right): int {
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
            $workHours = (float) ($employee['work_hours'] ?? 0);
            $overtimeHours = (float) ($employee['overtime_hours'] ?? 0);
            $percentage = $workHours > 0 ? ($overtimeHours / $workHours) * 100 : 0.0;

            $groups[$department]['department'] = $department;
            $groups[$department]['department_code'] ??= (string) ($employee['department_code'] ?? '');
            $groups[$department]['rows'][] = [
                'Nama' => (string) ($employee['name'] ?? ''),
                'L/P' => (string) ($employee['sex'] ?? ''),
                'Jabatan' => (string) ($employee['job_title'] ?? ''),
                'Jam' => self::formatNumber($workHours, 0),
                'Total Hari' => count((array) ($employee['dates'] ?? [])),
                'Total Lemburan' => self::formatNumber($overtimeHours, 0),
                '%' => self::formatPercent($percentage),
                'work_hours_value' => $workHours,
                'overtime_hours_value' => $overtimeHours,
                'percentage_value' => $percentage,
            ];
            $groups[$department]['summary']['subtotal'] = (int) ($groups[$department]['summary']['subtotal'] ?? 0) + 1;
            $groups[$department]['summary']['male_count'] = (int) ($groups[$department]['summary']['male_count'] ?? 0) + ((string) ($employee['sex'] ?? '') === 'L' ? 1 : 0);
            $groups[$department]['summary']['female_count'] = (int) ($groups[$department]['summary']['female_count'] ?? 0) + ((string) ($employee['sex'] ?? '') === 'P' ? 1 : 0);
            $groups[$department]['summary']['work_hours'] = (float) ($groups[$department]['summary']['work_hours'] ?? 0) + $workHours;
            $groups[$department]['summary']['overtime_hours'] = (float) ($groups[$department]['summary']['overtime_hours'] ?? 0) + $overtimeHours;
            $groups[$department]['summary']['overtime_values'][] = $overtimeHours;
            $groups[$department]['summary']['percentage_values'][] = $percentage;
        }

        foreach ($groups as &$group) {
            $summary = $group['summary'] ?? [];
            $subtotal = (int) ($summary['subtotal'] ?? 0);
            $maleCount = (int) ($summary['male_count'] ?? 0);
            $femaleCount = (int) ($summary['female_count'] ?? 0);
            $overtimeStats = self::stats((array) ($summary['overtime_values'] ?? []));
            $percentageStats = self::stats((array) ($summary['percentage_values'] ?? []));

            $group['summary']['male_percent'] = $subtotal > 0 ? round(($maleCount / $subtotal) * 100) : 0;
            $group['summary']['female_percent'] = $subtotal > 0 ? round(($femaleCount / $subtotal) * 100) : 0;
            $group['summary']['overtime_min_text'] = self::formatNumber($overtimeStats['min'], 0);
            $group['summary']['overtime_max_text'] = self::formatNumber($overtimeStats['max'], 0);
            $group['summary']['overtime_avg_text'] = self::formatNumber($overtimeStats['avg'], 0);
            $group['summary']['percentage_min_text'] = self::formatPercent($percentageStats['min']);
            $group['summary']['percentage_max_text'] = self::formatPercent($percentageStats['max']);
            $group['summary']['percentage_avg_text'] = self::formatPercent($percentageStats['avg']);
            $group['summary']['department_overtime_text'] = self::formatNumber((float) ($summary['overtime_hours'] ?? 0), 0);
            $group['summary']['department_percentage_text'] = self::formatPercent(((float) ($summary['work_hours'] ?? 0)) > 0 ? ((float) ($summary['overtime_hours'] ?? 0) / (float) $summary['work_hours']) * 100 : 0.0);
        }
        unset($group);

        return array_values($groups);
    }

    /**
     * @param  array<int, array<string, mixed>>  $groupedRows
     * @param  array<int, array<string, mixed>>  $flatRows
     * @return array<string, mixed>
     */
    private static function grandSummary(array $groupedRows, array $flatRows): array
    {
        $departmentTotals = [];
        foreach ($groupedRows as $group) {
            $summary = (array) ($group['summary'] ?? []);
            $departmentTotals[] = [
                'department' => (string) ($group['department'] ?? ''),
                'total_lembur' => (string) ($summary['department_overtime_text'] ?? '0'),
                'percentage' => (string) ($summary['department_percentage_text'] ?? '0.0%'),
            ];
        }

        return [
            'subtotal' => count($flatRows),
            'department_totals' => $departmentTotals,
        ];
    }

    private static function resolvePrintedBy(array $rows): string
    {
        foreach ($rows as $row) {
            $value = trim((string) ($row['Created_x0020_By'] ?? $row['Last_x0020_Modified_x0020_By'] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function sexCode(string $value): string
    {
        $value = strtoupper(trim($value));

        return match ($value) {
            'MALE', 'L' => 'L',
            'FEMALE', 'P' => 'P',
            default => trim($value),
        };
    }

    /**
     * @param  array<int, float>  $values
     * @return array{min: float, max: float, avg: float}
     */
    private static function stats(array $values): array
    {
        $values = array_values($values);
        if ($values === []) {
            return ['min' => 0.0, 'max' => 0.0, 'avg' => 0.0];
        }

        return [
            'min' => min($values),
            'max' => max($values),
            'avg' => array_sum($values) / count($values),
        ];
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
        return strtolower(str_replace([' ', '_x0020_', '_x002e_', '.', '_', '-'], '', $key));
    }

    private static function parsePeriod(string $value): ?Carbon
    {
        if (! preg_match('/^\d{6}$/', $value)) {
            return null;
        }

        return Carbon::createFromFormat('Ym', $value) ?: null;
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
                continue;
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private static function toFloat(mixed $value): float
    {
        return (float) str_replace(',', '.', (string) $value);
    }

    private static function formatNumber(float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, '.', ',');
    }

    private static function formatPercent(float $value): string
    {
        return number_format($value, 1, '.', ',').'%';
    }
}
