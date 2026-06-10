<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class KehadiranKruBahanBakuReportService
{
    private const TITLE = 'Laporan Kehadiran Kru Bahan Baku';

    private const TARGET_DAILY_WORKER_TYPE = 'BR';

    private const TARGET_JOB_TITLE = 'KRU BORONGAN PENERIMAAN BAHAN BAKU';

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rows = $this->parseAttendanceRows($xmlContents, $sourceLabel);
        if ($rows === []) {
            throw new RuntimeException('Data Attendance tidak ditemukan pada XML.');
        }

        $period = $this->resolveReportPeriod($filters, $rows);
        $periodRows = array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->shouldIncludeRow($row, $period['start'], $period['end'])
        ));

        if ($periodRows === []) {
            return $this->emptyReportData($sourceLabel, $period, $rows);
        }

        $dateColumns = $this->buildDateColumns($period['start'], $period['end']);
        $employees = $this->buildEmployeeRows($periodRows, $dateColumns, $period['end']);

        if ($employees === []) {
            return $this->emptyReportData($sourceLabel, $period, $rows);
        }

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'printed_by' => $this->resolvePrintedBy($rows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y')
                    .' Sampai '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
            'date_columns' => $dateColumns,
            'headers' => ['Karyawan', 'Nama', 'Tanggal Masuk', 'Masa Kerja', 'Jabatan'],
            'rows' => $employees,
            'date_totals' => $this->buildDateTotals($employees, $dateColumns),
            'total_employees' => count($employees),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseAttendanceRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML wajib dikirim dari Ascend saat request print PDF.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid ({$sourceLabel}).");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'attendance') {
                continue;
            }

            $nodeXml = $reader->readOuterXml();
            if (! is_string($nodeXml) || trim($nodeXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($nodeXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $row[$key] = trim((string) $value);
            }

            $rows[] = [
                'Employee Code' => $row['Employee_x0020_Code'] ?? '',
                'Full Name' => $row['Full_x0020_Name'] ?? '',
                'Join Date' => $row['Join_x0020_Date'] ?? '',
                'Working Years' => $row['Working_x0020_Years'] ?? '',
                'Working Months' => $row['Working_x0020_Months'] ?? '',
                'Working Days' => $row['Working_x0020_Days'] ?? '',
                'Job Title' => $row['Job_x0020_Title'] ?? '',
                'Workgroup' => $row['Workgroup'] ?? '',
                'Daily Worker Type Code' => $row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? '',
                'Date' => $row['Date'] ?? '',
                'Date Sort' => substr((string) ($row['Date'] ?? ''), 0, 10),
                'Sign In' => $row['Sign_x0020_In'] ?? '',
                'Sign In Time' => $row['Sign_x0020_In_x0020__x0028_Time_x0029_'] ?? '',
                'Sign Out' => $row['Sign_x0020_Out'] ?? '',
                'Sign Out Time' => $row['Sign_x0020_Out_x0020__x0028_Time_x0029_'] ?? '',
                'HK' => $row['HK'] ?? '',
                'Created By' => $row['Created_x0020_By'] ?? '',
            ];
        }

        $reader->close();

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, array<string, string>>  $rows
     * @return array{start: Carbon, end: Carbon}
     */
    private function resolveReportPeriod(array $filters, array $rows): array
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
            array_filter($rows, fn (array $row): bool => $this->isTargetKruRow($row))
        )));

        if ($dates === []) {
            $today = Carbon::today();

            return ['start' => $today->copy()->startOfDay(), 'end' => $today->copy()->endOfDay()];
        }

        usort($dates, static fn (Carbon $left, Carbon $right): int => $left <=> $right);

        return [
            'start' => $dates[0]->copy()->startOfDay(),
            'end' => $dates[count($dates) - 1]->copy()->endOfDay(),
        ];
    }

    private function shouldIncludeRow(array $row, Carbon $startDate, Carbon $endDate): bool
    {
        if (! $this->isTargetKruRow($row)) {
            return false;
        }

        $date = $this->parseDate((string) ($row['Date'] ?? ''));
        if ($date === null || ! $date->betweenIncluded($startDate, $endDate)) {
            return false;
        }

        $employeeCode = trim((string) ($row['Employee Code'] ?? ''));

        return $employeeCode !== '' && ! str_starts_with(strtoupper($employeeCode), 'SPECIAL');
    }

    private function isTargetKruRow(array $row): bool
    {
        return strtoupper(trim((string) ($row['Daily Worker Type Code'] ?? ''))) === self::TARGET_DAILY_WORKER_TYPE
            && strtoupper(trim((string) ($row['Job Title'] ?? ''))) === self::TARGET_JOB_TITLE;
    }

    /**
     * @param  array{start: Carbon, end: Carbon}  $period
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, mixed>
     */
    private function emptyReportData(string $sourceLabel, array $period, array $rows): array
    {
        $dateColumns = $this->buildDateColumns($period['start'], $period['end']);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'printed_by' => $this->resolvePrintedBy($rows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y')
                    .' Sampai '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
            'date_columns' => $dateColumns,
            'headers' => ['Karyawan', 'Nama', 'Tanggal Masuk', 'Masa Kerja', 'Jabatan'],
            'rows' => [],
            'date_totals' => [],
            'total_employees' => 0,
        ];
    }

    /**
     * @return array<int, array{date: string, label: string}>
     */
    private function buildDateColumns(Carbon $startDate, Carbon $endDate): array
    {
        $dates = [];
        $current = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        while ($current->lessThanOrEqualTo($end)) {
            $dates[] = [
                'date' => $current->toDateString(),
                'label' => $current->locale('id')->translatedFormat('d-M-y'),
            ];
            $current->addDay();
        }

        return $dates;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<int, array{date: string, label: string}>  $dateColumns
     * @return array<int, array<string, mixed>>
     */
    private function buildEmployeeRows(array $rows, array $dateColumns, Carbon $periodEnd): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row['Employee Code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $groups[$code]['employee'] ??= [
                'employee_code' => $code,
                'name' => (string) ($row['Full Name'] ?? ''),
                'join_date' => $this->formatDate((string) ($row['Join Date'] ?? '')),
                'year_of_service' => $this->formatYearOfService($row, $periodEnd),
                'job_title' => (string) ($row['Job Title'] ?? ''),
            ];

            $date = substr((string) ($row['Date Sort'] ?? ''), 0, 10);
            if ($date !== '') {
                $groups[$code]['attendance'][$date][] = $row;
            }
        }

        ksort($groups, SORT_NATURAL);

        $result = [];
        foreach ($groups as $group) {
            $attendance = [];
            $hkTotal = 0.0;
            foreach ($dateColumns as $dateColumn) {
                $date = $dateColumn['date'];
                $dateRows = $group['attendance'][$date] ?? [];
                $attendance[$date] = [
                    'in' => $this->earliestTime($dateRows, 'Sign In', 'Sign In Time'),
                    'out' => $this->latestTime($dateRows, 'Sign Out', 'Sign Out Time'),
                ];

                foreach ($dateRows as $row) {
                    $hkTotal += $this->numericValue((string) ($row['HK'] ?? ''));
                }
            }

            if ($hkTotal <= 0) {
                $hkTotal = count(array_filter(
                    $attendance,
                    static fn (array $value): bool => trim((string) ($value['in'] ?? '')) !== ''
                        || trim((string) ($value['out'] ?? '')) !== ''
                ));
            }

            $result[] = [
                'employee' => $group['employee'] ?? [],
                'attendance' => $attendance,
                'hk' => $this->formatNumber($hkTotal),
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $employees
     * @param  array<int, array{date: string, label: string}>  $dateColumns
     * @return array<string, int>
     */
    private function buildDateTotals(array $employees, array $dateColumns): array
    {
        $totals = [];
        foreach ($dateColumns as $dateColumn) {
            $date = $dateColumn['date'];
            $totals[$date] = 0;

            foreach ($employees as $employee) {
                $attendance = $employee['attendance'][$date] ?? [];
                if (trim((string) ($attendance['in'] ?? '')) !== '' || trim((string) ($attendance['out'] ?? '')) !== '') {
                    $totals[$date]++;
                }
            }
        }

        return $totals;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function earliestTime(array $rows, string $dateTimeKey, string $timeKey): string
    {
        $values = array_values(array_filter(array_map(
            fn (array $row): ?Carbon => $this->parseDateTime((string) ($row[$dateTimeKey] ?? '')),
            $rows
        )));

        if ($values !== []) {
            usort($values, static fn (Carbon $left, Carbon $right): int => $left <=> $right);

            return $values[0]->format('H:i');
        }

        $times = array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row[$timeKey] ?? '')),
            $rows
        )));

        sort($times, SORT_NATURAL);

        return $times[0] ?? '';
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function latestTime(array $rows, string $dateTimeKey, string $timeKey): string
    {
        $values = array_values(array_filter(array_map(
            fn (array $row): ?Carbon => $this->parseDateTime((string) ($row[$dateTimeKey] ?? '')),
            $rows
        )));

        if ($values !== []) {
            usort($values, static fn (Carbon $left, Carbon $right): int => $right <=> $left);

            return $values[0]->format('H:i');
        }

        $times = array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row[$timeKey] ?? '')),
            $rows
        )));

        rsort($times, SORT_NATURAL);

        return $times[0] ?? '';
    }

    private function formatDate(string $value): string
    {
        return $this->parseDate($value)?->locale('id')->translatedFormat('d-M-y') ?? '';
    }

    private function formatYearOfService(array $row, Carbon $periodEnd): string
    {
        $joinDate = $this->parseDate((string) ($row['Join Date'] ?? ''));
        if ($joinDate !== null) {
            $diff = $joinDate->diff($periodEnd);

            return $this->formatWorkDuration((string) $diff->y, (string) $diff->m, (string) $diff->d);
        }

        $years = trim((string) ($row['Working Years'] ?? ''));
        $months = trim((string) ($row['Working Months'] ?? ''));
        $days = trim((string) ($row['Working Days'] ?? ''));
        if ($years !== '' || $months !== '' || $days !== '') {
            return $this->formatWorkDuration($years, $months, $days);
        }

        return '';
    }

    private function formatWorkDuration(string $years, string $months, string $days): string
    {
        return ($years !== '' ? $years : '0').' Thn '
            .($months !== '' ? $months : '0').' Bln '
            .($days !== '' ? $days : '0').' Hr';
    }

    private function numericValue(string $value): float
    {
        $normalized = str_replace(',', '.', trim($value));

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function formatNumber(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? (string) (int) $value
            : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function resolvePrintedBy(array $rows): string
    {
        foreach ($rows as $row) {
            $value = trim((string) ($row['Created By'] ?? ''));
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

    private function parseDateTime(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
