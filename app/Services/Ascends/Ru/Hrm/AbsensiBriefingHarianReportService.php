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

        $reportDate = $this->resolveReportDate($filters, $rows);
        $group = $this->resolveGroup($filters);
        $filteredRows = array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->shouldIncludeRow($row, $reportDate, $group)
        ));

        usort($filteredRows, static fn (array $left, array $right): int => [
            (string) ($left['Full Name'] ?? ''),
            (string) ($left['Employee Code'] ?? ''),
        ] <=> [
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

    private function shouldIncludeRow(array $row, Carbon $reportDate, string $group): bool
    {
        $date = $this->parseDate((string) ($row['Date'] ?? ''));
        if ($date === null || $date->format('Y-m-d') !== $reportDate->format('Y-m-d')) {
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

        if ($group === 'VKD') {
            return $department === 'VACUUM & K/D';
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
            'Alfa' => $isAbsent && $leave === '' ? 'V' : '',
        ];
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
