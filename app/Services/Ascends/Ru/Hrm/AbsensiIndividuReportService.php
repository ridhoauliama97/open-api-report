<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class AbsensiIndividuReportService
{
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
        $employeeCode = strtoupper(trim((string) ($filters['employee_code'] ?? '')));
        $employeeName = strtoupper(trim((string) ($filters['employee_name'] ?? '')));

        $periodRows = array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->shouldIncludeRow($row, $period['start'], $period['end'])
        ));

        if ($periodRows === []) {
            throw new RuntimeException('Data Attendance tidak ditemukan pada periode yang dipilih.');
        }

        $employeeGroups = $this->buildEmployeeGroups($periodRows, $employeeCode, $employeeName);

        return [
            'title' => 'Laporan Absensi Individu',
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'printed_by' => $this->resolvePrintedBy($rows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y')
                    .' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
            'headers' => ['Hari', 'Absen Masuk', 'Absen Keluar', 'Waktu Bekerja'],
            'employees' => $employeeGroups,
            'rows' => $employeeGroups,
            'total_employees' => count($employeeGroups),
            'total_rows' => array_sum(array_map(static fn (array $employee): int => count($employee['rows'] ?? []), $employeeGroups)),
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
                'Job Title' => $row['Job_x0020_Title'] ?? '',
                'Date' => $row['Date'] ?? '',
                'Date Sort' => substr((string) ($row['Date'] ?? ''), 0, 10),
                'Day' => $row['Day'] ?? '',
                'Sign In' => $row['Sign_x0020_In'] ?? '',
                'Sign In Time' => $row['Sign_x0020_In_x0020__x0028_Time_x0029_'] ?? '',
                'Sign In Sort' => $row['Sign_x0020_In'] ?? $row['Sign_x0020_In_x0020__x0028_Time_x0029_'] ?? '',
                'Sign Out' => $row['Sign_x0020_Out'] ?? '',
                'Sign Out Time' => $row['Sign_x0020_Out_x0020__x0028_Time_x0029_'] ?? '',
                'Present Absent' => $row['Present_x002F_Absent'] ?? '',
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
            $rows
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
        $date = $this->parseDate((string) ($row['Date'] ?? ''));
        if ($date === null || ! $date->betweenIncluded($startDate, $endDate)) {
            return false;
        }

        $employeeCode = trim((string) ($row['Employee Code'] ?? ''));

        return $employeeCode !== '' && ! str_starts_with(strtoupper($employeeCode), 'SPECIAL');
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildEmployeeGroups(array $rows, string $employeeCode, string $employeeName): array
    {
        $filteredRows = [];
        foreach ($rows as $row) {
            $rowCode = strtoupper((string) ($row['Employee Code'] ?? ''));
            $rowName = strtoupper((string) ($row['Full Name'] ?? ''));

            if ($employeeCode !== '' && $rowCode !== $employeeCode) {
                continue;
            }

            if ($employeeName !== '' && $rowName !== $employeeName && ! str_contains($rowName, $employeeName)) {
                continue;
            }

            $filteredRows[] = $row;
        }

        if ($filteredRows === []) {
            throw new RuntimeException('Data karyawan tidak ditemukan pada XML/periode yang dipilih.');
        }

        $groups = [];
        foreach ($filteredRows as $row) {
            $code = strtoupper((string) ($row['Employee Code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $groups[$code]['employee'] ??= [
                'code' => (string) ($row['Employee Code'] ?? ''),
                'name' => (string) ($row['Full Name'] ?? ''),
                'job_title' => (string) ($row['Job Title'] ?? ''),
            ];
            $groups[$code]['source_rows'][] = $row;
        }

        uasort($groups, static fn (array $left, array $right): int => [
            (string) ($left['employee']['name'] ?? ''),
            (string) ($left['employee']['code'] ?? ''),
        ] <=> [
            (string) ($right['employee']['name'] ?? ''),
            (string) ($right['employee']['code'] ?? ''),
        ]);

        $result = [];
        foreach ($groups as $group) {
            $employeeRows = $group['source_rows'] ?? [];
            usort($employeeRows, fn (array $left, array $right): int => [
                (string) ($left['Date Sort'] ?? ''),
                (string) ($left['Sign In Sort'] ?? ''),
            ] <=> [
                (string) ($right['Date Sort'] ?? ''),
                (string) ($right['Sign In Sort'] ?? ''),
            ]);

            $shapedRows = array_map(fn (array $row): array => $this->shapeRow($row), $employeeRows);
            $summary = $this->buildSummary($shapedRows);

            $result[] = [
                'employee' => $group['employee'] ?? [],
                'rows' => $shapedRows,
                'summary' => $summary,
                'total_work_seconds' => $summary['total_seconds'] ?? 0,
            ];
        }

        usort($result, static fn (array $left, array $right): int => [
            -1 * (int) ($left['total_work_seconds'] ?? 0),
            (string) ($left['employee']['name'] ?? ''),
            (string) ($left['employee']['code'] ?? ''),
        ] <=> [
            -1 * (int) ($right['total_work_seconds'] ?? 0),
            (string) ($right['employee']['name'] ?? ''),
            (string) ($right['employee']['code'] ?? ''),
        ]);

        return $result;
    }

    /**
     * @return array<string, string|int>
     */
    private function shapeRow(array $row): array
    {
        $signIn = $this->parseDateTime((string) ($row['Sign In'] ?? ''));
        $signOut = $this->parseDateTime((string) ($row['Sign Out'] ?? ''));
        $workSeconds = $this->workSeconds($signIn, $signOut, (string) ($row['Day'] ?? ''));

        return [
            'Hari' => $this->formatDay((string) ($row['Day'] ?? ''), (string) ($row['Date'] ?? '')),
            'Absen Masuk' => $this->formatDateTime($signIn),
            'Absen Keluar' => $this->formatDateTime($signOut),
            'Waktu Bekerja' => $workSeconds > 0 ? $this->formatDuration($workSeconds) : '',
            'work_seconds' => $workSeconds,
        ];
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return array<string, string>
     */
    private function buildSummary(array $rows): array
    {
        $durations = array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['work_seconds'] ?? 0),
            $rows
        )));

        if ($durations === []) {
            return [
                'total' => '0 Jam 0 Menit',
                'total_seconds' => 0,
                'min' => '',
                'max' => '',
                'avg' => '',
            ];
        }

        $total = array_sum($durations);
        $avg = (int) round($total / count($durations));

        return [
            'total' => $this->formatTotalMinutes($total),
            'total_seconds' => $total,
            'min' => $this->formatHourMinute(min($durations)),
            'max' => $this->formatHourMinute(max($durations)),
            'avg' => $this->formatHourMinute($avg),
        ];
    }

    private function workSeconds(?Carbon $signIn, ?Carbon $signOut, string $day): int
    {
        if ($signIn === null || $signOut === null || $signOut->lessThan($signIn)) {
            return 0;
        }

        $seconds = $signIn->diffInSeconds($signOut);
        if ($seconds >= 5 * 3600 && ! in_array(strtoupper(trim($day)), ['SATURDAY', 'SUNDAY'], true)) {
            $seconds -= 3600;
        }

        return max(0, (int) $seconds);
    }

    private function formatDay(string $day, string $date): string
    {
        $map = [
            'MONDAY' => 'Senin',
            'TUESDAY' => 'Selasa',
            'WEDNESDAY' => 'Rabu',
            'THURSDAY' => 'Kamis',
            'FRIDAY' => 'Jumat',
            'SATURDAY' => 'Sabtu',
            'SUNDAY' => 'Minggu',
        ];

        $normalized = strtoupper(trim($day));
        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        $parsedDate = $this->parseDate($date);

        return $parsedDate?->locale('id')->translatedFormat('EEEE') ?? '';
    }

    private function formatDateTime(?Carbon $dateTime): string
    {
        return $dateTime?->locale('id')->translatedFormat('d-M-y H:i:s') ?? '';
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    private function formatHourMinute(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = (int) round(($seconds % 3600) / 60);
        if ($minutes === 60) {
            $hours++;
            $minutes = 0;
        }

        return sprintf('%d:%02d', $hours, $minutes);
    }

    private function formatTotalMinutes(int $seconds): string
    {
        $totalMinutes = intdiv($seconds, 60);

        return intdiv($totalMinutes, 60).' Jam '.($totalMinutes % 60).' Menit';
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
