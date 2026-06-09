<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class RekapitulasiKehadiranKurang93TahunanReportService
{
    private const TITLE = 'Laporan Rekapitulasi Kehadiran < 93 % Tahunan';

    private const WORKING_DAYS_BY_MONTH = [
        1 => 24,
        2 => 21,
        3 => 26,
        4 => 24,
        5 => 18,
        6 => 25,
        7 => 24,
        8 => 26,
        9 => 25,
        10 => 25,
        11 => 26,
        12 => 23,
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rawRows = $this->parseAttendanceRows($xmlContents, $sourceLabel);
        $period = $this->resolvePeriod($rawRows, $filters);
        $status = $this->resolveStatus($filters);
        $months = $this->resolveMonths($period);
        $employees = $this->aggregateEmployees($rawRows, $period, $status);
        $rows = $this->buildRows($employees, $months);

        return [
            'title' => self::TITLE,
            'status' => $status,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $this->resolvePrintedBy($rawRows),
            'headers' => array_merge(['Nama'], array_map(static fn (int $month): string => str_pad((string) $month, 2, '0', STR_PAD_LEFT), $months), ['Total']),
            'months' => $months,
            'rows' => $rows,
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
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
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
    private function resolvePeriod(array $rows, array $filters): array
    {
        $startDate = trim((string) ($filters['start_date'] ?? $filters['TglAwal'] ?? ''));
        $endDate = trim((string) ($filters['end_date'] ?? $filters['TglAkhir'] ?? ''));

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

        $dates = array_values(array_filter(array_map(
            fn (array $row): ?Carbon => $this->parseDate((string) ($row['Date'] ?? '')),
            $rows
        )));

        if ($dates === []) {
            $today = Carbon::today();

            return ['start' => $today->copy()->startOfYear()->startOfDay(), 'end' => $today->copy()->endOfDay()];
        }

        usort($dates, static fn (Carbon $left, Carbon $right): int => $left <=> $right);

        return [
            'start' => $dates[0]->copy()->startOfMonth()->startOfDay(),
            'end' => $dates[count($dates) - 1]->copy()->endOfDay(),
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
     * @param  array<int, array<string, string>>  $rows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<string, array<string, mixed>>
     */
    private function aggregateEmployees(array $rows, array $period, string $status): array
    {
        $employees = [];

        foreach ($rows as $row) {
            $employeeCode = trim((string) ($row['Employee_x0020_Code'] ?? ''));
            $date = $this->parseDate((string) ($row['Date'] ?? ''));
            if (
                $employeeCode === ''
                || str_starts_with(strtoupper($employeeCode), 'SPECIAL')
                || $date === null
                || ! $date->betweenIncluded($period['start'], $period['end'])
                || ! $this->matchesStatus($row, $status)
            ) {
                continue;
            }

            if (! isset($employees[$employeeCode])) {
                $employees[$employeeCode] = [
                    'code' => $employeeCode,
                    'name' => trim((string) ($row['Full_x0020_Name'] ?? '')),
                    'months' => [],
                ];
            }

            if ($this->isDeductionRow($row)) {
                $month = (int) $date->month;
                $employees[$employeeCode]['months'][$month] = (int) ($employees[$employeeCode]['months'][$month] ?? 0) + 1;
            }
        }

        return $employees;
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
                $deductions = (int) ($employee['months'][$month] ?? 0);
                $percentage = $this->percentageForMonth($month, $deductions);
                $row[(string) $month] = $percentage === null ? '-%' : $percentage.'%';

                if ($percentage !== null) {
                    $row['Total']++;
                }
            }

            if ((int) $row['Total'] > 0) {
                $rows[] = $row;
            }
        }

        usort($rows, static fn (array $left, array $right): int => strnatcasecmp((string) ($left['Nama'] ?? ''), (string) ($right['Nama'] ?? '')));

        return $rows;
    }

    private function percentageForMonth(int $month, int $deductions): ?int
    {
        $totalWorkingDays = self::WORKING_DAYS_BY_MONTH[$month] ?? 0;
        if ($totalWorkingDays <= 0 || $deductions <= 0) {
            return null;
        }

        $percentage = (int) round((($totalWorkingDays - $deductions) / $totalWorkingDays) * 100);

        return $percentage > 0 && $percentage < 93 ? $percentage : null;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function matchesStatus(array $row, string $status): bool
    {
        $workerType = strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? '')));

        if ($status === 'Staff') {
            return str_starts_with($workerType, 'ST');
        }

        return str_starts_with($workerType, 'KT')
            || str_starts_with($workerType, 'KK')
            || str_starts_with($workerType, 'BR');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function isDeductionRow(array $row): bool
    {
        $leaveType = strtoupper(trim((string) ($row['Leave_x0020_Type_x0020_Code'] ?? '')));

        if (str_contains($leaveType, 'SKD')) {
            return false;
        }

        return str_contains($leaveType, 'I')
            || $leaveType === 'S'
            || str_contains($leaveType, 'M')
            || str_contains($leaveType, 'A');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveStatus(array $filters): string
    {
        $value = $this->filterValue($filters, [
            'Pilih Status',
            'Pilih_x0020_Status',
            'pilih_status',
            'pilihStatus',
            'status',
            'Status',
            'category',
            'Category',
            'kategori',
            'Kategori',
        ]);

        return str_contains(strtoupper($value), 'STAFF') ? 'Staff' : 'KK/KT';
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $aliases
     */
    private function filterValue(array $filters, array $aliases): string
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
     */
    private function resolvePrintedBy(array $rows): string
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
