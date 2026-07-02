<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class RekapitulasiPengabaianKeterlambatanTahunanReportService
{
    private const TITLE = 'Laporan Rekapitulasi Pengabaian Keterlambatan Tahunan';

    private const MONTH_LABELS = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];

    private const EXCLUDED_EMPLOYEE_CODE_PREFIXES = [
        '120543',
        '110104',
        '110131',
        '110159',
        '120422',
        '120523',
        '130673',
        '130891',
        '131060',
        '131107',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $status = $this->resolveStatus($filters);
        $requestedPeriod = $this->resolvePeriodFromFilters($filters);
        $scan = $this->scanAttendanceRows($xmlContents, $sourceLabel, $requestedPeriod, $status);
        $period = $scan['period'];
        $months = $this->resolveMonths($period);
        $rows = $this->buildRows($scan['employees'], $months);
        $monthTotals = $this->buildMonthTotals($rows, $months);
        $grandTotal = array_sum($monthTotals);

        return [
            'title' => self::TITLE,
            'status' => $status,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => $scan['printed_by'],
            'headers' => array_merge(['No', 'Nama'], array_map(static fn (int $month): string => self::MONTH_LABELS[$month] ?? str_pad((string) $month, 2, '0', STR_PAD_LEFT), $months), ['Total']),
            'months' => $months,
            'month_labels' => array_intersect_key(self::MONTH_LABELS, array_flip($months)),
            'rows' => $rows,
            'month_totals' => $monthTotals,
            'grand_total' => $grandTotal,
            'total_rows' => count($rows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon}|null  $requestedPeriod
     * @return array{employees: array<string, array<string, mixed>>, period: array{start: Carbon, end: Carbon}, printed_by: string}
     */
    private function scanAttendanceRows(string $xmlContents, string $sourceLabel, ?array $requestedPeriod, string $status): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Attendance Full kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Attendance Full tidak valid: {$sourceLabel}");
        }

        $hasAttendanceRecord = false;
        $employees = [];
        $minDate = null;
        $maxDate = null;
        $printedBy = '';

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'attendance') {
                continue;
            }

            $hasAttendanceRecord = true;
            $row = $this->readAttendanceRow($reader);
            if ($row === []) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = $this->resolvePrintedByFromRow($row);
            }

            $date = $this->parseDate((string) ($row['Date'] ?? ''));
            if ($date !== null) {
                if ($minDate === null || $date->lessThan($minDate)) {
                    $minDate = $date->copy();
                }
                if ($maxDate === null || $date->greaterThan($maxDate)) {
                    $maxDate = $date->copy();
                }
            }

            if (
                $date === null
                || ($requestedPeriod !== null && ! $date->betweenIncluded($requestedPeriod['start'], $requestedPeriod['end']))
            ) {
                continue;
            }

            $this->aggregateEmployeeRow($employees, $row, $date, $status);
        }

        $reader->close();

        if (! $hasAttendanceRecord) {
            throw new RuntimeException('XML Attendance Full tidak memiliki record Attendance.');
        }

        return [
            'employees' => $employees,
            'period' => $requestedPeriod ?? $this->periodFromXmlDates($minDate, $maxDate),
            'printed_by' => $printedBy,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function readAttendanceRow(XMLReader $reader): array
    {
        $recordXml = $reader->readOuterXML();
        if (! is_string($recordXml) || trim($recordXml) === '') {
            return [];
        }

        $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($node === false) {
            return [];
        }

        $row = json_decode(json_encode($node), true) ?: [];

        return array_map(
            static fn (mixed $value): string => is_array($value) ? '' : trim((string) $value),
            $row
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start: Carbon, end: Carbon}|null
     */
    private function resolvePeriodFromFilters(array $filters): ?array
    {
        $startDate = trim((string) ($filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['end_date'] ?? ''));

        if ($startDate !== '' || $endDate !== '') {
            $start = $this->parseDate($startDate) ?? $this->parseDate($endDate);
            $end = $this->parseDate($endDate) ?? $this->parseDate($startDate);

            if ($start !== null && $end !== null) {
                if ($end->lessThan($start)) {
                    [$start, $end] = [$end, $start];
                }

                return ['start' => $start->startOfDay(), 'end' => $end->endOfDay()];
            }
        }

        return null;
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function periodFromXmlDates(?Carbon $minDate, ?Carbon $maxDate): array
    {
        if ($minDate === null || $maxDate === null) {
            $today = Carbon::today();

            return ['start' => $today->copy()->startOfYear()->startOfDay(), 'end' => $today->copy()->endOfDay()];
        }

        return [
            'start' => $minDate->copy()->startOfMonth()->startOfDay(),
            'end' => $maxDate->copy()->endOfDay(),
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<int, int>
     */
    private function resolveMonths(array $period): array
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
     * @param  array<string, array<string, mixed>>  $employees
     * @param  array<string, string>  $row
     */
    private function aggregateEmployeeRow(array &$employees, array $row, Carbon $date, string $status): void
    {
        $employeeCode = trim((string) ($row['Employee_x0020_Code'] ?? ''));
        if (
            $employeeCode === ''
            || str_starts_with(strtoupper($employeeCode), 'SPECIAL')
            || $this->hasExcludedEmployeeCodePrefix($employeeCode)
            || ! $this->matchesStatus($row, $status)
            || trim((string) ($row['Last_x0020_Modified_x0020_By'] ?? '')) === ''
        ) {
            return;
        }

        if (! isset($employees[$employeeCode])) {
            $employees[$employeeCode] = [
                'code' => $employeeCode,
                'name' => trim((string) ($row['Full_x0020_Name'] ?? '')),
                'months' => [],
            ];
        }

        $month = (int) $date->month;
        $employees[$employeeCode]['months'][$month] = (int) ($employees[$employeeCode]['months'][$month] ?? 0) + 1;
    }

    private function hasExcludedEmployeeCodePrefix(string $employeeCode): bool
    {
        foreach (self::EXCLUDED_EMPLOYEE_CODE_PREFIXES as $prefix) {
            if (str_starts_with($employeeCode, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, array<string, mixed>>  $employees
     * @param  array<int, int>  $months
     * @return array<int, array<string, mixed>>
     */
    private function buildRows(array $employees, array $months): array
    {
        $rows = [];
        foreach ($employees as $employee) {
            $row = [
                'Nama' => (string) ($employee['name'] ?? ''),
                'Total' => 0,
            ];

            foreach ($months as $month) {
                $count = (int) ($employee['months'][$month] ?? 0);
                $row[(string) $month] = $count > 0 ? $count : '-';
                $row['Total'] += $count;
            }

            if ((int) $row['Total'] > 0) {
                $rows[] = $row;
            }
        }

        usort($rows, static fn (array $left, array $right): int => strnatcasecmp((string) ($left['Nama'] ?? ''), (string) ($right['Nama'] ?? '')));

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $months
     * @return array<int, int>
     */
    private function buildMonthTotals(array $rows, array $months): array
    {
        $totals = array_fill_keys($months, 0);

        foreach ($rows as $row) {
            foreach ($months as $month) {
                $value = $row[(string) $month] ?? 0;
                $totals[$month] += is_numeric($value) ? (int) $value : 0;
            }
        }

        return $totals;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function matchesStatus(array $row, string $status): bool
    {
        $workerType = strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? '')));

        if ($status === 'Staff') {
            return $workerType === 'ST';
        }

        return str_starts_with($workerType, 'KT')
            || str_starts_with($workerType, 'KK');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveStatus(array $filters): string
    {
        $value = trim((string) ($filters['Pilih Status'] ?? ''));

        return str_contains(strtoupper($value), 'STAFF') ? 'Staff' : 'KK/KT';
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolvePrintedByFromRow(array $row): string
    {
        foreach (['Created_x0020_By', 'Last_x0020_Modified_x0020_By'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
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
