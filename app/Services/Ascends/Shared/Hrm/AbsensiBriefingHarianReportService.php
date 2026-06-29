<?php

namespace App\Services\Ascends\Shared\Hrm;

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

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, array<string, string>>  $rows
     */
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

    /**
     * @param  array<string, mixed>  $filters
     */
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

        return $group !== '' ? $group : 'RU vacuum & KD';
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, array<string, string>>  $rows
     * @return array{start: Carbon, end: Carbon}
     */
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
        $employeeCode = trim((string) ($row['Employee Code'] ?? ''));
        $date = $this->parseDate((string) ($row['Date'] ?? ''));
        if (
            in_array($employeeCode, ['131121', '131189', '131122', '131144'], true)
            || $date === null
            || ! $date->betweenIncluded($startDate, $endDate)
        ) {
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

        if ($this->contains($selectedGroup, 'PBB')) {
            return $this->startsWithAny($sorting, ['AA']);
        }

        if ($this->contains($selectedGroup, 'sawmil')) {
            return $this->startsWithAny($sorting, ['AB']);
        }

        if ($this->contains($selectedGroup, 'vacuum & KD (Shift III)')) {
            return $this->startsWithAny($sorting, ['KDMLM']);
        }

        if ($this->contains($selectedGroup, 'vacuum & KD (Shift II)')) {
            return $this->startsWithAny($sorting, ['GG3']);
        }

        if ($this->contains($selectedGroup, 'vacuum')) {
            return $this->startsWithAny($sorting, ['Ac']) && $this->shiftValue($row, $sorting) === 7.45;
        }

        if (strtoupper($selectedGroup) === 'VKD') {
            return $this->startsWithAny($sorting, ['Ac']);
        }

        if ($this->contains($selectedGroup, 'hilir (Shift III)')) {
            return $this->startsWithAny($sorting, ['Rotary Malam']);
        }

        if ($this->contains($selectedGroup, 'hilir (Shift II)')) {
            return $this->startsWithAny($sorting, ['Rotary Sore']);
        }

        if ($this->contains($selectedGroup, 'hilir')) {
            return $this->startsWithAny($sorting, ['Ae', 'ae', 'AE', 'Rotary Pagi']);
        }

        if ($this->contains($selectedGroup, 'Finger Joint A')) {
            return $this->startsWithAny($sorting, ['FJ1']);
        }

        if ($this->contains($selectedGroup, 'Finger Joint B')) {
            return $this->startsWithAny($sorting, ['FJ2']);
        }

        if ($this->contains($selectedGroup, 'Hulu 2 (Shift II)')) {
            return $this->startsWithAny($sorting, ['GG2']);
        }

        if ($this->contains($selectedGroup, 'Hulu 1')) {
            return $this->startsWithAny($sorting, ['AD1'])
                && $this->contains((string) ($row['Division'] ?? ''), 'PHU')
                && $this->contains((string) ($row['Sub Division'] ?? ''), 'S4S')
                && ! $this->contains((string) ($row['Job Title'] ?? ''), 'Kru Cross Cut Manual');
        }

        if ($this->contains($selectedGroup, 'Hulu 2')) {
            return $this->startsWithAny($sorting, ['AD2']);
        }

        if ($this->contains($selectedGroup, 'SGR')) {
            return $this->startsWithAny($sorting, ['SGR']);
        }

        if ($this->contains($selectedGroup, 'BandSaw')) {
            return $this->startsWithAny($sorting, ['SMG']);
        }

        if ($this->contains($selectedGroup, 'SLP')) {
            return $this->startsWithAny($sorting, ['SLP']);
        }

        if ($this->contains($selectedGroup, 'office')) {
            return $this->startsWithAny($sorting, ['af']);
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
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

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, string>>
     */
    private function appendCrystalVirtualRows(array $rows, string $group): array
    {
        if (! $this->contains($group, 'Office')) {
            return $rows;
        }

        foreach ($rows as $row) {
            if ($this->contains((string) ($row['Nama'] ?? ''), 'Winnie Trinisya')) {
                return $rows;
            }
        }

        $rows[] = [
            'Nama' => 'Winnie Trinisya',
            'Jam Masuk' => '',
            'Briefing' => '',
            'Telat' => '',
            'Sakit' => '',
            'Izin' => '',
            'Alfa' => '',
            'has_sign_in' => '0',
            'is_late' => '0',
            'is_not_present' => '1',
        ];

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

    /**
     * @param  array<string, mixed>  $filters
     */
    /**
     * @param  array<int, array<string, string>>  $rows
     */
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

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function primaryResponsibleName(array $rows, string $group): string
    {
        $candidate = $this->primaryResponsibleCandidate($rows, $group);

        return trim((string) ($candidate['Full Name'] ?? $rows[0]['Full Name'] ?? ''));
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, string>|null
     */
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

    /**
     * @return array<int, string>
     */
    private function primaryResponsibleNameNeedles(string $group): array
    {
        if ($this->contains($group, 'PBB')) {
            return ['Helderia'];
        }

        if ($this->contains($group, 'Vacuum') || strtoupper(trim($group)) === 'VKD') {
            return ['Suriono'];
        }

        if ($this->contains($group, 'Hilir')) {
            return ['Edy Sutoyo'];
        }

        if ($this->contains($group, 'Hulu 1')) {
            return ['Riza Apriadi'];
        }

        if ($this->contains($group, 'Hulu 2')) {
            return ['Lilis'];
        }

        if ($this->contains($group, 'Office')) {
            return ['Tin Meilysa'];
        }

        if ($this->contains($group, 'SGR')) {
            return ['Nur Aini'];
        }

        if ($this->contains($group, 'SLP')) {
            return ['Kardi'];
        }

        if ($this->contains($group, 'BandSaw')) {
            return ['Syaf'];
        }

        return [];
    }

    private function initialDivision(string $group): string
    {
        if ($this->contains($group, 'Sawmil')) {
            return 'SML';
        }

        if ($this->contains($group, 'PBB')) {
            return 'PBB';
        }

        if ($this->contains($group, 'Vacuum & KD (Shift III')) {
            return 'VKD Shift III';
        }

        if ($this->contains($group, 'Vacuum & KD (Shift II')) {
            return 'VKD Shift II';
        }

        if ($this->contains($group, 'Vacuum')) {
            return 'VKD';
        }

        if ($this->contains($group, 'HILIR (Shift III')) {
            return 'PHI Shift III';
        }

        if ($this->contains($group, 'HILIR (Shift II')) {
            return 'PHI Shift II';
        }

        if ($this->contains($group, 'HILIR')) {
            return 'PHI';
        }

        if ($this->contains($group, 'HULU 2 (Shift')) {
            return 'PHU 2 Shift II';
        }

        if ($this->contains($group, 'HULU 1')) {
            return 'PHU 1';
        }

        if ($this->contains($group, 'HULU 2')) {
            return 'PHU 2';
        }

        if ($this->contains($group, 'OFFICE')) {
            return 'KRUT';
        }

        return $group;
    }

    private function initialResponsibleName(string $name): string
    {
        if ($this->contains($name, 'helderia')) {
            return 'RIA';
        }

        if ($this->contains($name, 'HENG WIE')) {
            return 'AKT';
        }

        if ($this->contains($name, 'Kardi')) {
            return 'KRD';
        }

        if ($this->contains($name, 'SURIONO')) {
            return 'SRO';
        }

        if ($this->contains($name, 'VINNY')) {
            return 'VIN';
        }

        if ($this->contains($name, 'Suharman')) {
            return 'SPS';
        }

        if ($this->contains($name, 'ZULKIFLI')) {
            return 'ZKN';
        }

        if ($this->contains($name, 'Edy sutoyo')) {
            return 'EDS';
        }

        if ($this->contains($name, 'Riza Apriadi')) {
            return 'Riza Apriadi';
        }

        if ($this->contains($name, 'Lilis')) {
            return 'LRU';
        }

        if ($this->contains($name, 'Dwi')) {
            return 'DIY';
        }

        if ($this->contains($name, 'Otomosi')) {
            return 'OTM';
        }

        if ($this->contains($name, 'Syafrud')) {
            return 'SFD';
        }

        if ($this->contains($name, 'Fairuza Hus')) {
            return 'FAI';
        }

        if ($this->contains($name, 'Nur Aini')) {
            return 'NUR';
        }

        return $name;
    }

    /**
     * @return array<int, string>
     */
    private function additionalResponsibleNames(string $group): array
    {
        return array_filter([
            $this->pj2($group),
            $this->pj3($group),
            $this->pj4($group),
            $this->pj5($group),
            $this->pj6($group),
            $this->pj7($group),
        ], static fn (string $value): bool => trim($value) !== '');
    }

    private function pj2(string $group): string
    {
        if ($this->contains($group, 'PBB')) {
            return 'LRU';
        }

        if ($this->contains($group, 'Finger Joint B')) {
            return 'Susi Simamora';
        }

        if ($this->contains($group, 'Finger Joint A')) {
            return 'Ronika Sianturi';
        }

        if ($this->contains($group, 'Hilir') && ! $this->contains($group, 'Shift')) {
            return 'Difa Alamsah';
        }

        if ($this->contains($group, 'Hulu 2') && ! $this->contains($group, 'Shift')) {
            return 'Yuda Syahputra';
        }

        if ($this->contains($group, 'Hulu 1')) {
            return 'Juwita Br Sembiring';
        }

        if ($this->contains($group, 'SGR')) {
            return 'Netti Herawati Harahap';
        }

        if ($this->contains($group, 'SLP')) {
            return 'Roma Jeki Manullang';
        }

        if ($this->contains($group, 'BandSaw')) {
            return 'RSW';
        }

        return '';
    }

    private function pj3(string $group): string
    {
        if ($this->contains($group, 'Office')) {
            return 'SHB';
        }

        if ($this->contains($group, 'Hulu 2') && ! $this->contains($group, 'Shift')) {
            return 'Windiar Wati Zagoto';
        }

        if ($this->contains($group, 'BandSaw')) {
            return 'Musliyan';
        }

        if ($this->contains($group, 'Hilir') && ! $this->contains($group, 'Shift')) {
            return 'Yuliana Pratiwi';
        }

        return '';
    }

    private function pj4(string $group): string
    {
        if ($this->contains($group, 'Office')) {
            return 'Tin Meilysa S';
        }

        return '';
    }

    private function pj5(string $group): string
    {
        return '';
    }

    private function pj6(string $group): string
    {
        return '';
    }

    private function pj7(string $group): string
    {
        return '';
    }

    /**
     * @param  array<string, string>  $row
     */
    private function sortingCode(array $row): string
    {
        $employeeCode = (string) ($row['Employee Code'] ?? '');
        $name = (string) ($row['Full Name'] ?? '');
        $department = (string) ($row['Department'] ?? '');
        $division = (string) ($row['Division'] ?? '');
        $workgroup = (string) ($row['Workgroup'] ?? '');
        $jobTitle = (string) ($row['Job Title'] ?? '');
        $scheduledShift = (string) ($row['Scheduled Shift'] ?? '');

        if ($employeeCode === '131749') {
            return 'ZZ';
        }

        if ($this->contains($jobTitle, 'SLP')) {
            return 'SLP';
        }

        if ($this->contains($division, 'Band Saw')
            || $this->contains($jobTitle, 'Band Saw')
            || $this->contains($jobTitle, 'BandSaw')
            || $this->contains($jobTitle, 'Ka. Regu Grader Sawmill')
            || $this->contains($jobTitle, 'Kru Grader Supp')
        ) {
            return 'SMG';
        }

        if ($this->contains($jobTitle, 'Operator Forklift') && $this->containsAny($name, ['Ismet Pramu', 'Bambang', 'Haris Prana'])) {
            return 'AF';
        }

        if ($this->containsAny($jobTitle, ['Admin Produksi RU', 'Sales'])
            || $this->containsAny($name, ['Tin Meilys', 'IIs Ramad', 'Haris Prana', 'Sayani G', 'Arie Agus', 'Pardomuan Sihom', 'Wahyu Affandi'])
            || $this->containsAny($jobTitle, ['Tally Kayu Bulat', 'Tally Produksi Hilir', 'Tally KD', 'Tally Saw', 'Regu Spare P', 'Operator Barang Jadi', 'Operator Bahan Baku', 'Tally Bahan Baku', 'Tally Motor', 'Ast. Ka. Div. Stoc', 'PPIC', 'Sales Eks', 'ADMIN stock', 'STAFF stock', 'ADM stock', 'STAFF HR', 'gudang', 'hrd', 'div stock', 'div.produksi', 'KA. DIVISI PRO'])
            || in_array($employeeCode, ['131125', '131655', '131015', '131077', '130274', '130056', '131157', '131182'], true)
        ) {
            return 'AF';
        }

        if ($this->containsAny($name, ['Soniwati G', 'Netti Hera', 'Nur Aini', 'Irmawa', 'Lena Pa', 'Siska Mut', 'Berkat', 'Desilina', 'Daniel Set', 'Michael Fran', 'Nurmi Tam', 'Roma Hut', 'Ronny Sam'])
            || $this->containsAny($jobTitle, ['Borongan Stick', 'KRU STICK'])
        ) {
            return 'SGR';
        }

        if ($this->containsAny($name, ['Rafi Prawira', 'Jaka Syatr', 'Yosy Andrean', 'Ruswanto', 'Syafrud', 'Ponijan', 'Otomosi', 'Yosi Sep', 'Orlando Ad', 'Nur Rahma', 'Puput In', 'Lintar Br', 'Mhd Ashab', 'Ngadino', 'M Azha', 'Khairul Af', 'Brenda Uli', 'Yunita Bar', 'Diana Ma', 'Ikhsan'])
            || $this->containsAny($jobTitle, ['Kru Mesin SLP', 'Kru Borongan Sawmil', 'Operator Borongan Sawmil', 'Ka. Div. Grenda', 'Kru Grader Sawmil', 'Kernet Forkli', 'KRU RACIP', 'Kru Grader Supplier'])
            || in_array($employeeCode, ['130492'], true)
        ) {
            return 'SMG';
        }

        if ($this->contains($workgroup, 'Borongan')) {
            return $this->contains($jobTitle, 'Penerimaan Bahan Baku') ? 'AA' : 'TT';
        }

        if ($this->containsAny($jobTitle, ['Operator Penerimaan Bahan Baku', 'Kru Penerimaan Bahan Baku', 'Staff Penerimaan Baha', 'Penerimaan Kayu Bul', 'Kru Tulis Bahan Baku', 'Kru Ukur Bahan Baku', 'Kru Harian Bahan Baku', 'Operator Harian Bahan Baku', 'Kru Borongan Penerimaan Bahan Baku', 'Operator Borongan Bahan Baku', 'Borongan B. Bal', 'Ka. div penerimaan'])
            || $this->containsAny($name, ['Helderia', 'Chandra Pad'])
            || $this->contains($department, 'Penerimaan Bahan Baku')
            || $this->contains($department, 'Supplier Service')
            || $this->contains($jobTitle, 'Bahan Baku')
            || $this->contains($jobTitle, 'Kru Kayu Bulat')
            || $this->contains($jobTitle, 'Kru Tulis Kayu Bulat')
            || $this->contains($jobTitle, 'kayu Bulat')
            || in_array($employeeCode, ['131499', '130223'], true)
        ) {
            return 'AA';
        }

        if ($this->contains($scheduledShift, 'VKD PAGI')
            || ($this->contains($workgroup, 'GROUP A') && $this->contains($jobTitle, 'Kru Vacuum'))
            || $this->containsAny($jobTitle, ['Ka. Regu Vacuum', 'Operator Odong-'])
        ) {
            return 'AC';
        }

        if ($this->contains($scheduledShift, 'VKD MALAM')
            || ($this->contains($scheduledShift, 'Normal Shift III') && $this->contains($workgroup, 'Vacuum'))
            || ($this->containsAny($jobTitle, ['Kru KD', 'Operator KD']) && $this->contains($scheduledShift, 'Normal Shift III (23.'))
            || $this->contains($scheduledShift, 'Vacuum Shift II (20.')
        ) {
            return 'KDMLM';
        }

        if ($this->contains($workgroup, 'Finger Joint Shift II')
            || ($this->contains($scheduledShift, 'Prod.Shift II') && $this->contains($workgroup, 'Finger Joint'))
            || ($this->contains($scheduledShift, 'Shift II Sabtu') && $this->contains($workgroup, 'Finger Joint'))
        ) {
            return 'GG2';
        }

        if (($this->contains($scheduledShift, 'Prod.Shift II') && $this->containsAny($workgroup, ['Vacuum', 'KD']))
            || ($this->contains($scheduledShift, 'Prod.Shift IISab') && $this->contains($workgroup, 'Vacuum Group B'))
            || ($this->contains($scheduledShift, 'Shift II Sabtu') && $this->containsAny($workgroup, ['KD', 'Vacuum Group']))
        ) {
            return 'GG3';
        }

        if ($this->contains($workgroup, 'Finger Joint Grup  A')) {
            return 'FJ1';
        }

        if ($this->contains($workgroup, 'Finger Joint Grup B')) {
            return 'FJ2';
        }

        if ($this->contains($workgroup, 'Rotary Group')) {
            if ($this->containsAny($scheduledShift, ['Shift III Sab', 'Shift III Rot', 'Prod. Shift III', 'Normal Shift III'])) {
                return 'Rotary Malam';
            }

            if ($this->containsAny($scheduledShift, ['Prod.Shift II', 'Shift II Sab'])) {
                return 'Rotary Sore';
            }

            if ($this->containsAny($scheduledShift, ['Kary. Shift 1 Sab', 'Karyawan Normal (07.'])) {
                return 'Rotary Pagi';
            }
        }

        if ($this->containsAny($jobTitle, ['Staff Finger Joint', 'Operator Finger Joint', 'Kru Finger Joint 1', 'Kru Finger Joint 2', 'Kru Finger Joint 3'])
            || ($this->containsAny($jobTitle, ['Kru Finger Joint', 'Operator Finger Joint', 'Ast. Operator Finger Joint']) && $this->contains($workgroup, 'Kary. Produksi Shi'))
            || $this->containsAny($name, ['Lesrina Sian', 'Emi Dwi', 'Riko Triwa', 'Wisnu Wardian', 'Merniaty Ber', 'Aditiya Prata', 'Amos Priam', 'Khadijah', 'Evi Susant', 'Holida Nasu', 'Teguh Febri', 'Raditia Perma', 'Irfan Syahpu'])
            || $this->contains($workgroup, 'FINGER JOINT SHIFT I')
            || $this->containsAny($jobTitle, ['MANDOR S4S', 'FORKLIFT 2.5', 'FINGER JOINT 2', 'FINGER JOINT 3', 'DOUBLE PLANNER RIP', 'DOUBLE PLANER RIP', 'JOINT 3', 'FINGER JOINT 1', 'DOUBLE RIP PLA'])
            || in_array($employeeCode, ['131153', '130138', '131256', '131267', '131327', '131409', '131350', '131415', '131416', '131418', '131114', '130016', '130023'], true)
        ) {
            return 'AD2';
        }

        if ($this->containsAny($jobTitle, ['Kru Grader Kualitas', 'Kru Double Rip', 'Operator Double Rip', 'MANDOR PRODUKSI S4S', 'KRU MULTI RIP SAW', 'S4S LINE - CROSS CUT', 'S4S LINE - GRADER', 'SINGLE RIP (ASS', 'GRADER SINGLE RIP', 'GRADER S4S-LINE', 'S4S', 'TALLY SAWMILL', 'Kru Cross Cut Awal', 'Kru Susun', 'Operator Multi Rip S', 'KRU TABLE SAW', 'KRU GRADER S4S', 'OPERATOR TABLE SAW', 'OPERATOR CROSS CUT AWAL', 'KRU GRADER CROSS CUT AWAL'])
            || in_array($employeeCode, ['130943', '131362', '130924', '131375', '130532', '130433', '130029', '130955', '130911', '131280', '131134', '130204', '130907', '131154', '131259', '131386', '131192'], true)
        ) {
            return 'AD1';
        }

        if (
            $this->contains($jobTitle, 'Kru Sanding')
            || $this->contains($jobTitle, 'Produksi Hilir')
            || $this->contains($jobTitle, 'Kru Rotary')
            || $this->contains($jobTitle, 'Operator Moulding')
            || $this->contains($jobTitle, 'Kru Moulding')
            || $this->contains($jobTitle, 'Kru Packing')
            || $this->contains($jobTitle, 'Kru Bahan Daur Ulang')
            || ($this->contains($jobTitle, 'Kru Pallet') && $this->contains($department, 'Produksi Akhir'))
            || $this->contains($jobTitle, 'Operator Sanding')
            || $this->contains($jobTitle, 'Operator Rotary')
            || $this->contains($jobTitle, 'Teknisi Pisau Mesin Pro')
        ) {
            return $this->rotaryShiftCode($scheduledShift, 'AE');
        }

        if ($this->contains($jobTitle, 'Operator Table Saw')
            || ($this->contains($jobTitle, 'Supir Forklift') && $this->contains($department, 'Produksi Akhir'))
            || $this->contains($jobTitle, 'Ka. Dept. Produksi RU')
            || $this->contains($jobTitle, 'Ka. Dept. Produksi')
            || $this->contains($jobTitle, 'Operator Finger Joint')
            || $this->contains($jobTitle, 'Kru Finger Joint')
            || $this->contains($jobTitle, 'Mandor Produksi Hulu')
            || $this->contains($jobTitle, 'Operator Double Planner Rip Saw')
            || $this->contains($jobTitle, 'S4S')
        ) {
            return $this->fingerJointCode($workgroup, 'AD1');
        }

        if ($this->containsAny($name, ['Ya\'aso Za', 'Rahmat Wahyud', 'Kardi', 'Maju Parsar'])
            || $this->containsAny($jobTitle, ['Supir Loader', 'Supir Forklift', 'Operator Vacuum', 'Supir Oto Carry', 'Kru KD', 'Kru Vacuum', 'Mandor Vacuum KD', 'Ka. Div. Vacuum KD', 'Vacuum & KD', 'Vacuum & K/D', 'Vacuum', 'KD', 'Vacum', 'forklift 3.5 ton'])
            || in_array($employeeCode, ['131085', '131502'], true)
        ) {
            return $this->vacuumShiftCode($scheduledShift);
        }

        if ($this->contains($jobTitle, 'Supir Forklift')
            || $this->contains($jobTitle, 'Operator Vacuum')
            || $this->contains($jobTitle, 'Supir Oto Carry')
            || $this->contains($jobTitle, 'Kru KD')
            || $this->contains($jobTitle, 'Kru Vacuum')
            || $this->contains($jobTitle, 'Mandor Vacuum KD')
            || $this->contains($jobTitle, 'Ka. Div. Vacuum KD')
            || $this->contains($jobTitle, 'Vacuum & KD')
            || $this->contains($jobTitle, 'Vacuum & K/D')
            || $this->contains($name, 'Rahmat Wahyudi')
            || $this->contains($name, 'Raihan Muktasim')
            || $this->contains($name, 'Muhammad Ridho')
        ) {
            return $this->vacuumShiftCode($scheduledShift);
        }

        if ($this->containsAny($name, ['Daniel Yaso Zal', 'Daniel yas'])) {
            return 'Ae';
        }

        if ($this->containsAny($jobTitle, ['Sawmill', 'Grader Sawmill', 'Tally Sawmill', 'grenda', 'Pallet', 'Supir Otto Ca', 'tally stick', 'penerimaan', 'service lapangan', 'forklift 6.0', 'grader s', 'kernet for', 'BORONGAN STICK'])
            || $name === 'Dalifao Lase'
            || in_array($employeeCode, ['131147', '131242', '130222', '131363', '131180', '131145'], true)
        ) {
            return 'AB';
        }

        if ($this->containsAny($jobTitle, ['Kru Pallet', 'cut akhir', 'Rotary', 'moulding', 'sanding', 'setting', 'packing', 'produksi hilir', 'tally produksi', 'Mandor rotary', 'Grenda'])
            || $this->containsAny($name, ['Daniel Yaso Zal', 'Ayu Lestari Gul', 'Daniel yas', 'sabda te', 'Agung Winata', 'Muhammad Fajr', 'Dyta Rufai', 'Maya Sari'])
            || in_array($employeeCode, ['131339', '131264', '131250', '131223', '131372', '131384', '130032', '130206'], true)
        ) {
            return 'Ae';
        }

        if ($this->contains($jobTitle, 'BORONGAN SAWMILL') || $this->contains($name, 'Benjamin')) {
            return 'ZZ';
        }

        if (
            $this->contains($jobTitle, 'adm stock')
            || $this->contains($jobTitle, 'gudang')
            || $this->contains($jobTitle, 'hrd')
            || $this->contains($jobTitle, 'div stock')
            || $this->contains($jobTitle, 'div.produksi')
            || $this->contains($jobTitle, 'kantor')
            || $this->contains($jobTitle, 'KA. DIVISI PRO')
            || $this->contains($employeeCode, '131157')
            || $this->contains($employeeCode, '131182')
        ) {
            return 'af';
        }

        return 'AD1';
    }

    private function vacuumShiftCode(string $scheduledShift): string
    {
        if ($this->contains($scheduledShift, 'Shift III')) {
            return 'KDMLM';
        }

        if ($this->contains($scheduledShift, 'Shift II')) {
            return 'GG3';
        }

        return 'Ac';
    }

    private function rotaryShiftCode(string $scheduledShift, string $default): string
    {
        if ($this->contains($scheduledShift, 'Shift III')) {
            return 'Rotary Malam';
        }

        if ($this->contains($scheduledShift, 'Shift II')) {
            return 'Rotary Sore';
        }

        if ($this->contains($scheduledShift, 'Shift I')) {
            return 'Rotary Pagi';
        }

        return $default;
    }

    private function fingerJointCode(string $workgroup, string $default): string
    {
        if ($this->contains($workgroup, 'Finger Joint A')) {
            return 'FJ1';
        }

        if ($this->contains($workgroup, 'Finger Joint B')) {
            return 'FJ2';
        }

        return $default;
    }

    private function shiftValue(array $row, string $sorting): float
    {
        $scheduledShift = (string) ($row['Scheduled Shift'] ?? '');
        $date = $this->parseDate((string) ($row['Date'] ?? ''));

        if ($sorting === 'KDMLM' || $sorting === 'Rotary Malam') {
            return 22.45;
        }

        if ($this->startsWithAny($sorting, ['AD1', 'AD2', 'AE']) && $date?->isSaturday()) {
            return 7.45;
        }

        if ($sorting === 'ADZ') {
            return 14.45;
        }

        if ($this->startsWithAny($sorting, ['AD1', 'AD2', 'AE'])) {
            return 7.45;
        }

        if ($this->contains($scheduledShift, 'Staff II')) {
            return 8.15;
        }

        if ($this->contains($scheduledShift, 'Shift iii')) {
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

    /**
     * @param  array<int, string>  $prefixes
     */
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

    /**
     * @param  array<int, string>  $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($this->contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, int|string>
     */
    private function sortKey(array $row, string $group): array
    {
        if ($this->contains($group, 'PBB')) {
            return [
                (string) ($row['Date'] ?? ''),
                $this->pbbSortOrder((string) ($row['Full Name'] ?? '')),
                (string) ($row['Employee Code'] ?? ''),
            ];
        }

        if (strtoupper(trim($group)) === 'VKD' || $this->contains($group, 'Vacuum')) {
            return [
                (string) ($row['Date'] ?? ''),
                $this->vkdSortOrder($row),
                (string) ($row['Full Name'] ?? ''),
                (string) ($row['Employee Code'] ?? ''),
            ];
        }

        return [
            (string) ($row['Date'] ?? ''),
            $this->isAbsent($row) ? 1 : 0,
            (string) ($row['Full Name'] ?? ''),
            (string) ($row['Employee Code'] ?? ''),
        ];
    }

    private function vkdSortOrder(array $row): int
    {
        $jobTitle = (string) ($row['Job Title'] ?? '');
        $workgroup = (string) ($row['Workgroup'] ?? '');

        if ($this->contains($jobTitle, 'Ka. Div. Vacuum')) {
            return 10;
        }

        if ($this->contains($workgroup, 'Vacuum Group')) {
            return 20;
        }

        if ($this->contains($jobTitle, 'Supir Forklift')) {
            return 30;
        }

        if ($this->contains($jobTitle, 'Ka. Regu Vacuum')) {
            return 40;
        }

        return 50;
    }

    private function pbbSortOrder(string $name): int
    {
        $orders = [
            'helderia' => 10,
            'lilis roma' => 20,
            'alex sihombing' => 30,
            'atoziduhu hura' => 40,
            'juster e sinaga' => 50,
            'mhd. ervin' => 60,
            'muhammad dava' => 70,
            'muhammad rinaldi' => 80,
            'radot manik' => 90,
            'rahman david' => 100,
            'ronal herianto' => 110,
            'satria wiranata' => 120,
        ];

        foreach ($orders as $needle => $order) {
            if ($this->contains($name, $needle)) {
                return $order;
            }
        }

        return 999;
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
