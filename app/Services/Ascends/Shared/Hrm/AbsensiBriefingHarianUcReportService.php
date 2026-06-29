<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class AbsensiBriefingHarianUcReportService
{
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

        usort($filteredRows, fn (array $left, array $right): int => $this->sortKey($left, $group) <=> $this->sortKey($right, $group));

        $shapedRows = array_map(
            fn (array $row): array => $this->shapeRow($row, $group),
            $filteredRows
        );
        $shapedRows = $this->appendCrystalVirtualRows($shapedRows, $group);

        return [
            'title' => 'Laporan Absensi Briefing Harian',
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'printed_by' => $this->resolvePrintedBy($rows),
            'group' => $this->initialDivision($group),
            'selected_group' => $group,
            'division' => $this->initialDivision($group),
            'responsible_person' => $this->resolveResponsiblePerson($filters, $group, $filteredRows),
            'theme' => trim((string) ($filters['tema'] ?? $filters['theme'] ?? '')),
            'guests' => trim((string) ($filters['tamu'] ?? $filters['guests'] ?? '')),
            'time' => trim((string) ($filters['jam'] ?? $filters['time'] ?? '')),
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
                'Sub Division' => $row['Sub-Division_x0020_Name'] ?? '',
                'Workgroup' => $row['Workgroup'] ?? '',
                'Job Title' => $row['Job_x0020_Title'] ?? '',
                'Scheduled Shift' => $row['Scheduled_x0020_Shift'] ?? $row['Shift'] ?? '',
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

    private function resolveReportDate(array $filters, array $rows): Carbon
    {
        $date = trim((string) (
            $filters['AttendanceDate.EndDate']
            ?? $filters['AttendanceDate_EndDate']
            ?? $filters['AttendanceDate_x0020_EndDate']
            ?? $filters['attendance_date']
            ?? $filters['report_date']
            ?? $filters['tanggal']
            ?? $filters['date']
            ?? ''
        ));
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

    private function resolveGroup(array $filters): string
    {
        $group = trim((string) (
            $filters['Pilih Group']
            ?? $filters['Pilih_Group']
            ?? $filters['Pilih_x0020_Group']
            ?? $filters['group']
            ?? $filters['division']
            ?? $filters['divisi']
            ?? ''
        ));

        return $group !== '' ? $group : 'Security Pagi';
    }

    private function resolveReportPeriod(array $filters, array $rows): array
    {
        $startDate = trim((string) (
            $filters['AttendanceDate.StartDate']
            ?? $filters['AttendanceDate_StartDate']
            ?? $filters['AttendanceDate_x0020_StartDate']
            ?? $filters['start_date']
            ?? $filters['TglAwal']
            ?? ''
        ));
        $endDate = trim((string) (
            $filters['AttendanceDate.EndDate']
            ?? $filters['AttendanceDate_EndDate']
            ?? $filters['AttendanceDate_x0020_EndDate']
            ?? $filters['end_date']
            ?? $filters['TglAkhir']
            ?? ''
        ));

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
        $department = strtolower(trim((string) ($row['Department'] ?? '')));
        if ($department === 'management') {
            return false;
        }

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
        $selectedGroup = trim($group);
        $sorting = $this->sortingCode($row);
        $shift = strtolower(trim((string) ($row['Scheduled Shift'] ?? $row['Shift'] ?? '')));

        if ($this->contains($selectedGroup, 'Security Pagi') || $this->contains($selectedGroup, 'security pagi')) {
            return $this->startsWithAny($sorting, ['Aa'])
                && ($this->contains($shift, 'pagi') || $this->contains($shift, 'shift i'));
        }

        if ($this->contains($selectedGroup, 'Security Malam') || $this->contains($selectedGroup, 'security malam')) {
            return $this->startsWithAny($sorting, ['Aa'])
                && ($this->contains($shift, 'malam') || $this->contains($shift, 'shift iii'));
        }

        if ($this->contains($selectedGroup, 'medan') || $this->contains($selectedGroup, 'Office')) {
            return $this->startsWithAny($sorting, ['AC']);
        }

        if (strtoupper($selectedGroup) === 'MESO') {
            return $this->startsWithAny($sorting, ['GG']);
        }

        if (strtoupper($selectedGroup) === 'MNTB') {
            return $this->startsWithAny($sorting, ['MLI']);
        }

        if (strtoupper($selectedGroup) === 'UCB') {
            return $this->startsWithAny($sorting, ['AB']);
        }

        if ($this->contains($selectedGroup, 'maintenance') || $this->contains($selectedGroup, 'Maintenance')) {
            return $this->startsWithAny($sorting, ['AB']);
        }

        return false;
    }

    private function shapeRow(array $row, string $group): array
    {
        $leave = strtoupper(trim(((string) ($row['Leave Code'] ?? '')).' '.((string) ($row['Leave Type'] ?? ''))));
        $present = strtoupper(trim((string) ($row['Present Absent'] ?? '')));
        $hasSignIn = trim((string) ($row['Sign In Time'] ?? '')) !== '';
        $cekTelat = $this->cekTelat($row, $group);
        $isLate = $cekTelat === 1;
        $isAbsent = $cekTelat === 2 || $present === 'ABSENT';

        return [
            'Nama' => (string) ($row['Full Name'] ?? ''),
            'Jam Masuk' => (string) ($row['Sign In Time'] ?? ''),
            'Briefing' => '',
            'Telat' => $isLate ? 'V' : '',
            'Sakit' => str_contains($leave, 'SICK') || str_contains($leave, 'SAKIT') ? 'V' : '',
            'Izin' => str_contains($leave, 'IZIN') || str_contains($leave, 'PERMISSION') || str_contains($leave, 'LEAVE') ? 'V' : '',
            'Alfa' => '',
            'has_sign_in' => $hasSignIn ? '1' : '0',
            'is_late' => $isLate ? '1' : '0',
            'is_not_present' => $isAbsent ? '1' : '0',
        ];
    }

    private function appendCrystalVirtualRows(array $rows, string $group): array
    {
        return $rows;
    }

    private function cekTelat(array $row, string $group): int
    {
        if (trim((string) ($row['Sign In Time'] ?? '')) === '') {
            return 2;
        }

        $timeNew = $this->timeNew((string) ($row['Sign In Time'] ?? ''));
        if ($timeNew === null) {
            return 2;
        }

        if ($timeNew > $this->shiftValue($row, $this->sortingCode($row))) {
            return 1;
        }

        return 0;
    }

    private function isAbsent(array $row): bool
    {
        return strtoupper(trim((string) ($row['Present Absent'] ?? ''))) === 'ABSENT';
    }

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
            'present_no_late' => ['count' => $presentNoLate, 'percent' => $this->roundedPercent($presentNoLate, $total)],
            'late' => ['count' => $late, 'percent' => $this->percent($late, $total)],
            'not_present' => ['count' => $notPresent, 'percent' => $this->percent($notPresent, $total)],
        ];
    }

    private function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) floor(($count / $total) * 100) : 0;
    }

    private function roundedPercent(int $count, int $total): int
    {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
    }

    private function resolveResponsiblePerson(array $filters, string $group, array $rows): string
    {
        $value = trim((string) (
            $filters['Penanggung Jawab']
            ?? $filters['Penanggung_Jawab']
            ?? $filters['Penanggung_x0020_Jawab']
            ?? $filters['penanggung_jawab']
            ?? $filters['responsible_person']
            ?? ''
        ));
        if ($value !== '') {
            return $value;
        }

        $firstName = $this->primaryResponsibleName($rows, $group);
        $parts = array_filter([
            $this->initialResponsibleName($firstName),
            ...$this->additionalResponsibleNames($group),
        ], static fn (string $part): bool => trim($part) !== '');

        return implode(', ', array_values(array_unique($parts)));
    }

    private function primaryResponsibleName(array $rows, string $group): string
    {
        $candidate = $this->primaryResponsibleCandidate($rows, $group);

        return trim((string) ($candidate['Full Name'] ?? $rows[0]['Full Name'] ?? ''));
    }

    private function primaryResponsibleCandidate(array $rows, string $group): ?array
    {
        foreach ($this->primaryResponsibleNameNeedles($group) as $needle) {
            foreach ($rows as $row) {
                if ($this->contains((string) ($row['Full Name'] ?? ''), $needle)) {
                    return $row;
                }
            }
        }

        foreach ($rows as $row) {
            if ($this->containsAny((string) ($row['Job Title'] ?? ''), ['Ka. Div', 'Ka. Dept', 'Ka. Regu'])) {
                return $row;
            }
        }

        return $rows[0] ?? null;
    }

    private function primaryResponsibleNameNeedles(string $group): array
    {
        if (strtoupper(trim($group)) === 'UCB') {
            return ['Helderia'];
        }

        if ($this->contains($group, 'Maintenance')) {
            return ['Helderia'];
        }

        if ($this->contains($group, 'MESO')) {
            return ['Heng Wie'];
        }

        if ($this->contains($group, 'MNTB')) {
            return ['Suparmin'];
        }

        if ($this->contains($group, 'Security')) {
            return ['Vinny'];
        }

        if ($this->contains($group, 'Office')) {
            return ['Shinta'];
        }

        return [];
    }

    private function initialDivision(string $group): string
    {
        if ($this->contains($group, 'Maintenance')) {
            return 'MNT';
        }

        if ($this->contains($group, 'Security')) {
            return 'SCT';
        }

        if ($this->contains($group, 'Office Medan')) {
            return 'UCM';
        }

        return strtoupper($group);
    }

    private function initialResponsibleName(string $name): string
    {
        if (strtoupper(trim($name)) === '-' || $this->contains($name, 'UCB')) {
            return '-';
        }

        if ($this->contains($name, 'helderia') || $this->contains($name, 'Helderia')) {
            return 'RIA';
        }

        if ($this->contains($name, 'HENG WIE') || $this->contains($name, 'Heng Wie') || $this->contains($name, 'heng wie')) {
            return 'AKT';
        }

        if ($this->contains($name, 'SURIONO') || $this->contains($name, 'Suriono')) {
            return 'SRO';
        }

        if ($this->contains($name, 'SUPARMIN') || $this->contains($name, 'Suparmin')) {
            return 'SPM';
        }

        if ($this->contains($name, 'VINNY') || $this->contains($name, 'Vinny')) {
            return 'VIN';
        }

        if ($this->contains($name, 'ZULKIFLI') || $this->contains($name, 'Zulkifli')) {
            return 'ZKN';
        }

        if ($this->contains($name, 'Shinta kar') || $this->contains($name, 'shinta kar') || $this->contains($name, 'Shinta Kar')) {
            return 'SHK';
        }

        if ($this->contains($name, 'Heny Pe') || $this->contains($name, 'heny pe') || $this->contains($name, 'Heny pe')) {
            return 'HPI';
        }

        if ($this->contains($name, 'Dina') || $this->contains($name, 'dina')) {
            return 'DMA';
        }

        if ($this->contains($name, 'Evi seroja') || $this->contains($name, 'evi seroja') || $this->contains($name, 'Evi Seroja')) {
            return 'EST';
        }

        if ($this->contains($name, 'Hari rama') || $this->contains($name, 'hari rama') || $this->contains($name, 'Hari Rama')) {
            return 'MHR';
        }

        if ($this->contains($name, 'Ade Rya') || $this->contains($name, 'ade rya') || $this->contains($name, 'Ade rya')) {
            return 'ARP';
        }

        return $name;
    }

    private function additionalResponsibleNames(string $group): array
    {
        return array_filter([
            $this->pj2($group),
            $this->pj3($group),
            $this->pj4($group),
            $this->pj5($group),
        ], static fn (string $value): bool => trim($value) !== '');
    }

    private function pj2(string $group): string
    {
        if (strtoupper(trim($group)) === 'MESO') {
            return ', HSP';
        }

        if ($this->contains($group, 'Pagi')) {
            return ', Lindawati Girsang';
        }

        return ' ';
    }

    private function pj3(string $group): string
    {
        if (strtoupper(trim($group)) === 'MESO') {
            return '';
        }

        if (strtoupper(trim($group)) === 'MNTB') {
            return ', Ahmad Ade Ardiansyah';
        }

        if ($this->contains($group, 'Pagi')) {
            return ', Rini Hastuti';
        }

        return ' ';
    }

    private function pj4(string $group): string
    {
        return ' ';
    }

    private function pj5(string $group): string
    {
        return ' ';
    }

    private function sortingCode(array $row): string
    {
        $employeeCode = (string) ($row['Employee Code'] ?? '');
        $name = (string) ($row['Full Name'] ?? '');
        $department = (string) ($row['Department'] ?? '');
        $division = (string) ($row['Division'] ?? '');
        $workgroup = (string) ($row['Workgroup'] ?? '');
        $jobTitle = (string) ($row['Job Title'] ?? '');
        $scheduledShift = (string) ($row['Scheduled Shift'] ?? '');

        if ($this->contains($division, 'MESO')
            || $this->contains($workgroup, 'MESO')
            || $this->contains($department, 'MESO')
        ) {
            return 'GG';
        }

        if ($this->contains($division, 'MNTB')
            || $this->contains($workgroup, 'MNTB')
            || $this->contains($jobTitle, 'MNTB')
            || $this->contains($name, 'MNTB')
        ) {
            return 'MLI';
        }

        if ($this->contains($jobTitle, 'Security')
            || $this->contains($department, 'Security')
            || $this->contains($division, 'Security')
        ) {
            return 'Aa';
        }

        if ($this->contains($jobTitle, 'Office')
            || $this->contains($department, 'Office')
            || $this->contains($workgroup, 'Office')
            || $this->contains($name, 'Admin')
            || $this->contains($name, 'Staff')
        ) {
            return 'AC';
        }

        if ($this->contains($jobTitle, 'Maintenance')
            || $this->contains($department, 'Maintenance')
        ) {
            return 'AB';
        }

        if ($this->contains($jobTitle, 'Accounting')
            || $this->contains($department, 'Accounting')
        ) {
            return 'AE';
        }

        if ($this->contains($jobTitle, 'Binjai')
            || $this->contains($department, 'Binjai')
            || $this->contains($division, 'Binjai')
        ) {
            return 'AG';
        }

        return 'AC';
    }

    private function shiftValue(array $row, string $sorting): float
    {
        $scheduledShift = (string) ($row['Scheduled Shift'] ?? '');

        if ($sorting === 'MLI') {
            return 7.45;
        }

        if ($sorting === 'GG') {
            return 7.45;
        }

        if ($this->contains($scheduledShift, 'Shift iii') || $this->contains($scheduledShift, 'Malam')) {
            return 0.45;
        }

        if ($this->contains($scheduledShift, 'Shift ii')) {
            return 19.45;
        }

        if (
            $this->contains($scheduledShift, 'normal kl')
            || $this->contains($scheduledShift, 'normal kt')
            || $this->contains($scheduledShift, 'normal kary')
            || $this->contains($scheduledShift, 'normal staff')
            || $this->contains($scheduledShift, 'Staff Off')
            || $this->contains($scheduledShift, 'Shift i')
            || $this->contains($scheduledShift, 'Pagi')
        ) {
            return 7.45;
        }

        return 7.45;
    }

    private function timeNew(string $value): ?float
    {
        if (! preg_match('/(\d{1,2})[:.](\d{2})/', trim($value), $matches)) {
            return null;
        }

        return (int) $matches[1] + ((int) $matches[2] / 100);
    }

    private function startsWithAny(string $value, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with(strtolower($value), strtolower($prefix))) {
                return true;
            }
        }

        return false;
    }

    private function contains(string $haystack, string $needle): bool
    {
        return stripos($haystack, $needle) !== false;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($this->contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function sortKey(array $row, string $group): array
    {
        return [
            (string) ($row['Date'] ?? ''),
            $this->isAbsent($row) ? 1 : 0,
            (string) ($row['Full Name'] ?? ''),
            (string) ($row['Employee Code'] ?? ''),
        ];
    }

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
