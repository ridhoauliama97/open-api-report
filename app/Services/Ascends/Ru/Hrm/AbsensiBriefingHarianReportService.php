<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class AbsensiBriefingHarianReportService
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
        $reportDate = $period['end'];
        $group = $this->resolveGroup($filters);
        $filteredRows = array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->shouldIncludeRow($row, $period['start'], $period['end'], $group)
        ));

        usort($filteredRows, fn (array $left, array $right): int => [
            (string) ($left['Date'] ?? ''),
            $this->isAbsent($left) ? 1 : 0,
            (string) ($left['Full Name'] ?? ''),
            (string) ($left['Employee Code'] ?? ''),
        ] <=> [
            (string) ($right['Date'] ?? ''),
            $this->isAbsent($right) ? 1 : 0,
            (string) ($right['Full Name'] ?? ''),
            (string) ($right['Employee Code'] ?? ''),
        ]);

        $shapedRows = array_map(
            fn (array $row): array => $this->shapeRow($row),
            $filteredRows
        );

        return [
            'title' => 'Laporan Absensi Briefing Harian',
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'printed_by' => $this->resolvePrintedBy($rows),
            'group' => $group,
            'division' => $group,
            'responsible_person' => $this->resolveResponsiblePerson($filters, $group),
            'theme' => trim((string) ($filters['tema'] ?? $filters['theme'] ?? '')),
            'report_date' => $reportDate->locale('id')->translatedFormat('d-M-y'),
            'report_date_sort' => $reportDate->format('Y-m-d'),
            'start_date' => $period['start']->locale('id')->translatedFormat('d-M-y'),
            'period_text' => $period['start']->format('Y-m-d') === $period['end']->format('Y-m-d')
                ? $period['end']->locale('id')->translatedFormat('d-M-y')
                : $period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            'summary' => $this->buildSummary($shapedRows),
            'headers' => ['No', 'Nama', 'Jam Masuk', 'Briefing', 'Telat', 'Sakit', 'Izin', 'Alfa'],
            'rows' => $shapedRows,
            'total_rows' => count($shapedRows),
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
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA)) {
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

            $node = simplexml_load_string($nodeXml);
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
                'Department' => $row['Department_x0020_Name'] ?? '',
                'Division' => $row['Division_x0020_Name'] ?? '',
                'Workgroup' => $row['Workgroup'] ?? '',
                'Job Title' => $row['Job_x0020_Title'] ?? '',
                'Date' => $row['Date'] ?? '',
                'Sign In Time' => $row['Sign_x0020_In_x0020__x0028_Time_x0029_'] ?? '',
                'Sign In Diff' => $row['Sign_x0020_In_x0020_Diff.'] ?? $row['Sign_x0020_In_x0020_Diff._x0020__x0028_Mins_x0029_'] ?? '',
                'Present Absent' => $row['Present_x002F_Absent'] ?? '',
                'Leave Code' => $row['Leave_x0020_Type_x0020_Code'] ?? '',
                'Leave Type' => $row['Leave_x0020_Type'] ?? '',
                'Remarks' => $row['Remarks'] ?? '',
                'Created By' => $row['Created_x0020_By'] ?? '',
            ];
        }

        $reader->close();

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, array<string, string>>  $rows
     */
    private function resolveReportDate(array $filters, array $rows): Carbon
    {
        $date = trim((string) ($filters['report_date'] ?? $filters['tanggal'] ?? $filters['date'] ?? ''));
        if ($date !== '') {
            return $this->parseDate($date) ?? throw new RuntimeException("Format tanggal tidak valid: {$date}.");
        }

        $dates = array_filter(array_map(
            fn (array $row): string => (string) ($row['Date'] ?? ''),
            $rows
        ));

        rsort($dates);

        return $this->parseDate((string) ($dates[0] ?? '')) ?? Carbon::today();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveGroup(array $filters): string
    {
        $group = trim((string) ($filters['group'] ?? $filters['division'] ?? $filters['divisi'] ?? ''));

        return $group !== '' ? strtoupper($group) : 'VKD';
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

        $date = $this->resolveReportDate($filters, $rows);

        return ['start' => $date->copy()->startOfDay(), 'end' => $date->copy()->endOfDay()];
    }

    private function shouldIncludeRow(array $row, Carbon $startDate, Carbon $endDate, string $group): bool
    {
        $date = $this->parseDate((string) ($row['Date'] ?? ''));
        if ($date === null || ! $date->betweenIncluded($startDate, $endDate)) {
            return false;
        }

        $status = strtolower(trim((string) ($row['Present Absent'] ?? '')));
        if ($status === '') {
            return false;
        }

        return $this->matchesGroup($row, $group);
    }

    private function matchesGroup(array $row, string $group): bool
    {
        $group = strtoupper(trim($group));
        $department = strtoupper(trim((string) ($row['Department'] ?? '')));
        $division = strtoupper(trim((string) ($row['Division'] ?? '')));
        $workgroup = strtoupper(trim((string) ($row['Workgroup'] ?? '')));
        $jobTitle = strtoupper(trim((string) ($row['Job Title'] ?? '')));

        if ($group === 'VKD') {
            return $department === 'VACUUM & K/D'
                && ! str_contains($jobTitle, 'OPERATOR FORKLIFT');
        }

        foreach ([$department, $division, $workgroup] as $value) {
            if ($value === $group || str_contains($value, $group)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function shapeRow(array $row): array
    {
        $leave = strtoupper(trim(((string) ($row['Leave Code'] ?? '')).' '.((string) ($row['Leave Type'] ?? ''))));
        $present = strtoupper(trim((string) ($row['Present Absent'] ?? '')));
        $signInDiff = (float) str_replace(',', '.', (string) ($row['Sign In Diff'] ?? '0'));
        $hasSignIn = trim((string) ($row['Sign In Time'] ?? '')) !== '';
        $isAbsent = $present === 'ABSENT';

        return [
            'Nama' => (string) ($row['Full Name'] ?? ''),
            'Jam Masuk' => (string) ($row['Sign In Time'] ?? ''),
            'Briefing' => '',
            'Telat' => $hasSignIn && $signInDiff > 0 ? 'V' : '',
            'Sakit' => str_contains($leave, 'SICK') || str_contains($leave, 'SAKIT') ? 'V' : '',
            'Izin' => str_contains($leave, 'IZIN') || str_contains($leave, 'PERMISSION') || str_contains($leave, 'LEAVE') ? 'V' : '',
            'Alfa' => '',
            'has_sign_in' => $hasSignIn ? '1' : '0',
            'is_late' => $hasSignIn && $signInDiff > 0 ? '1' : '0',
            'is_not_present' => $isAbsent ? '1' : '0',
        ];
    }

    private function isAbsent(array $row): bool
    {
        return strtoupper(trim((string) ($row['Present Absent'] ?? ''))) === 'ABSENT';
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, array{count: int, percent: int}>
     */
    private function buildSummary(array $rows): array
    {
        $total = count($rows);
        $presentNoLate = count(array_filter(
            $rows,
            static fn (array $row): bool => ($row['has_sign_in'] ?? '') === '1' && ($row['is_late'] ?? '') !== '1'
        ));
        $late = count(array_filter($rows, static fn (array $row): bool => ($row['is_late'] ?? '') === '1'));
        $notPresent = count(array_filter($rows, static fn (array $row): bool => ($row['is_not_present'] ?? '') === '1'));

        return [
            'present_no_late' => ['count' => $presentNoLate, 'percent' => $this->percent($presentNoLate, $total)],
            'late' => ['count' => $late, 'percent' => $this->percent($late, $total)],
            'not_present' => ['count' => $notPresent, 'percent' => $this->percent($notPresent, $total)],
        ];
    }

    private function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveResponsiblePerson(array $filters, string $group): string
    {
        $value = trim((string) ($filters['penanggung_jawab'] ?? $filters['responsible_person'] ?? ''));
        if ($value !== '') {
            return $value;
        }

        return $group === 'VKD' ? 'SRO,' : '';
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
}
