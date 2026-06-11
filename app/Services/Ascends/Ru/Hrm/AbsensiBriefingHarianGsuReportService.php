<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class AbsensiBriefingHarianGsuReportService
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
                'Daily Worker Type Code' => $row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? $row['Worker_x0020_Type_x0020_Code'] ?? '',
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
        $group = trim((string) (
            $filters['Pilih Group']
            ?? $filters['Pilih_Group']
            ?? $filters['Pilih_x0020_Group']
            ?? $filters['group']
            ?? $filters['division']
            ?? $filters['divisi']
            ?? ''
        ));

        return $group !== '' ? $group : 'Bahan Baku, Washing & Broker';
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
        $employeeCode = trim((string) ($row['Employee Code'] ?? ''));
        $date = $this->parseDate((string) ($row['Date'] ?? ''));
        if (
            $employeeCode === '120509'
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

        if ($this->contains($selectedGroup, 'bahan baku')) {
            return $this->startsWithAny($sorting, ['AA']) && $this->isWnbBriefingRow($row);
        }

        if ($this->contains($selectedGroup, 'warehouse')) {
            return $this->startsWithAny($sorting, ['AC']);
        }

        if ($this->contains($selectedGroup, 'inject')) {
            return $this->startsWithAny($sorting, ['ZZ']);
        }

        if ($this->contains($selectedGroup, 'Sales')) {
            return $this->startsWithAny($sorting, ['AB']);
        }

        if ($this->contains($selectedGroup, 'regu d')) {
            return $this->startsWithAny($sorting, ['AJ4']);
        }

        if ($this->contains($selectedGroup, 'regu c')) {
            return $this->startsWithAny($sorting, ['AJ3']);
        }

        if ($this->contains($selectedGroup, 'regu b')) {
            return $this->startsWithAny($sorting, ['AJ2']);
        }

        if ($this->contains($selectedGroup, 'regu a')) {
            return $this->startsWithAny($sorting, ['AJ1']);
        }

        if ($this->contains($selectedGroup, 'Ekstrusi Pagi')) {
            return $this->startsWithAny($sorting, ['AK1']);
        }

        if ($this->contains($selectedGroup, 'Ekstrusi Sore')) {
            return $this->startsWithAny($sorting, ['AK2']);
        }

        if ($this->contains($selectedGroup, 'Ekstrusi Malam')) {
            return $this->startsWithAny($sorting, ['AK3']);
        }

        if ($this->contains($selectedGroup, 'PIN HULU')) {
            return $this->startsWithAny($sorting, ['PINHU', 'PINHI']);
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
        foreach ($this->virtualEmployeeNames($group) as $name) {
            $exists = false;
            foreach ($rows as $row) {
                if ($this->contains((string) ($row['Nama'] ?? ''), $name)) {
                    $exists = true;
                    break;
                }
            }

            if (! $exists) {
                $rows[] = $this->virtualAbsentRow($name);
            }
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function virtualEmployeeNames(string $group): array
    {
        if ($this->contains($group, 'Prod Regu A')) {
            return ['Sapta Yudha'];
        }

        if ($this->contains($group, 'Prod Regu B')) {
            return ['Mhd. Ridho Nugraha'];
        }

        if ($this->contains($group, 'Sales')) {
            return [
                'Laksana Febri Wijaya Laia',
                'Erikson Roni Rumapea',
                'Bambang Paldawan',
                'Tita Andriani',
                'Viqih Rizky Lubis',
                'Nurusysyafillah',
                'Marina Mentari Br Kaban',
            ];
        }

        if ($this->contains($group, 'Warehouse')) {
            return ['Netty Pasaribu'];
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private function virtualAbsentRow(string $name): array
    {
        return [
            'Nama' => $name,
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

        return implode(', ', $parts);
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
        if ($this->contains($group, 'Washing') || $this->contains($group, 'Bahan Baku')) {
            return ['Frans Bossy Panjaitan', 'Evi Yunita', 'Sumisri', 'Sumando'];
        }

        if ($this->contains($group, 'Warehouse')) {
            return ['Florida', 'Eko Herianto', 'Netty'];
        }

        if ($this->contains($group, 'Sales')) {
            return ['Indah Kar', 'Ricky', 'Laksana'];
        }

        if ($this->contains($group, 'Regu A')) {
            return ['Janter Hutapea'];
        }

        if ($this->contains($group, 'Regu B')) {
            return ['Fandi Rahmadani'];
        }

        if ($this->contains($group, 'Regu C')) {
            return ['Rahmad Fauzy'];
        }

        if ($this->contains($group, 'Ekstrusi Pagi')) {
            return ['Abdul Hakim Halawa'];
        }

        if ($this->contains($group, 'Ekstrusi Sore')) {
            return ['Toni'];
        }

        if ($this->contains($group, 'Ekstrusi Malam')) {
            return ['Ilham'];
        }

        if ($this->contains($group, 'PIN HULU')) {
            return ['Marisa', 'Suria Ramadan', 'Novelia'];
        }

        return [];
    }

    private function initialDivision(string $group): string
    {
        if ($this->contains($group, 'washing') || $this->contains($group, 'bahan baku')) {
            return 'WNB';
        }

        if ($this->contains($group, 'warehouse')) {
            return 'WHS';
        }

        if ($this->contains($group, 'inject')) {
            return 'PIN';
        }

        return $group;
    }

    private function initialResponsibleName(string $name): string
    {
        if ($this->contains($name, 'Evi yunita')) {
            return 'EYS';
        }

        if ($this->contains($name, 'Florida')) {
            return 'FLO';
        }

        if ($this->contains($name, 'Suparmin')) {
            return 'SPM';
        }

        if ($this->contains($name, 'Indah Kar')) {
            return 'IKA';
        }

        if ($this->contains($name, 'Sumisri')) {
            return 'SUM';
        }

        if ($this->contains($name, 'eKY HAN')) {
            return 'EHN';
        }

        if ($this->contains($name, 'Sumando')) {
            return 'SPY';
        }

        if ($this->contains($name, 'Ediyanto')) {
            return 'EDO';
        }

        if ($this->contains($name, 'Fendy')) {
            return 'FDY';
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
        if ($this->contains($group, 'Inject')) {
            return '';
        }

        if ($this->contains($group, 'Washing') || $this->contains($group, 'Bahan Baku')) {
            return 'Surya Santoso';
        }

        if ($this->contains($group, 'GSU Warehouse')) {
            return 'KFA';
        }

        if ($this->contains($group, 'Sales')) {
            return 'IKA';
        }

        if ($this->contains($group, 'Regu B')) {
            return 'Fandi Rahmadani';
        }

        if ($this->contains($group, 'Regu A')) {
            return 'Janter Hutapea';
        }

        if ($this->contains($group, 'Regu C')) {
            return 'Rahmad Fauzy';
        }

        if ($this->contains($group, 'PIN HILIR')) {
            return 'Novelia Dinda Utami';
        }

        return '';
    }

    private function pj3(string $group): string
    {
        if ($this->contains($group, 'Inject')) {
            return 'Suparmin';
        }

        if ($this->contains($group, 'Washing') || $this->contains($group, 'Bahan Baku')) {
            return 'SUM';
        }

        if ($this->contains($group, 'PIN HULU')) {
            return 'Suria Ramadan';
        }

        if ($this->contains($group, 'PIN HILIR')) {
            return 'Fahrul Rozy';
        }

        if ($this->contains($group, 'WAREH')) {
            return 'Eko Herianto';
        }

        if ($this->contains($group, 'Sales')) {
            return 'ENS';
        }

        return '';
    }

    private function pj4(string $group): string
    {
        if ($this->contains($group, 'WARE')) {
            return 'NTP';
        }

        if ($this->contains($group, 'PIN HULU')) {
            return 'Deni Darmansyah';
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
        $workgroup = (string) ($row['Workgroup'] ?? '');
        $jobTitle = (string) ($row['Job Title'] ?? '');
        $scheduledShift = (string) ($row['Scheduled Shift'] ?? '');

        if (strcasecmp($name, 'alfisyah rizal') === 0
            || $this->containsAny($name, ['Freddy Panj', 'Ibal Sireg', 'David Ferna', 'Eko Syahpu', 'Surya Santos', 'H Rinaldy A', 'Elton Rollys', 'Yohanes Perangin Angin', 'Evi Yunita', 'Friend Rohot', 'Filijisuk', 'Edi Kurnia'])
            || in_array($employeeCode, ['120660'], true)
            || $this->containsAny($jobTitle, ['Operator Bahan Awal', 'bahan baku', 'washing', 'Ka. Div. Produksi Brok', 'Cleaning', 'forklift', 'Spv. Cuci', 'Kru Cuci', 'Operator Cuci', 'Kru Penerima BB', 'Kru Penerimaan BB'])
            || ($this->contains($jobTitle, 'Kru Ekstrusi BB') && $this->contains($workgroup, 'Normal Shift'))
        ) {
            return 'AA';
        }

        if (strcasecmp($department, 'marketing') === 0 || strcasecmp($department, 'Sales') === 0) {
            return 'AB';
        }

        if ($this->containsAny($jobTitle, ['Ka. Regu Spare', 'Kru Pilih Barang', 'Kru Online Shop', 'KRU GUDANG', 'Kru Setting', 'Admin Gudang BS', 'Admin Sparepa', 'Supir', 'Kernet', 'Gudang Spare Part', 'Gudang Barang Jadi'])
            || strcasecmp($department, 'gudang') === 0
        ) {
            return 'AC';
        }

        if ($this->isPinHuluHilirSortingRow($row)) {
            return 'PINHI';
        }

        if ($this->contains($workgroup, 'Produksi Regu IV')) {
            return 'AJ4';
        }

        if ($this->contains($workgroup, 'Produksi Regu III')) {
            return 'AJ3';
        }

        if ($this->contains($workgroup, 'Produksi Regu II')) {
            return 'AJ2';
        }

        if ($this->contains($workgroup, 'Produksi Regu I')) {
            return 'AJ1';
        }

        if (($this->contains($workgroup, 'Ekstrusi Kecil') || $this->contains($workgroup, 'Ekstrusi Besar'))
            && ($this->contains($scheduledShift, 'Shift III') || $this->contains($scheduledShift, 'Shift 3'))
        ) {
            return 'AK3';
        }

        if (($this->contains($workgroup, 'Ekstrusi Kecil') || $this->contains($workgroup, 'Ekstrusi Besar'))
            && ($this->contains($scheduledShift, 'Shift II') || $this->contains($scheduledShift, 'Shift 2'))
        ) {
            return 'AK2';
        }

        if (($this->contains($workgroup, 'Ekstrusi Kecil') || $this->contains($workgroup, 'Ekstrusi Besar'))
            && ($this->contains($scheduledShift, 'Shift I') || $this->contains($scheduledShift, 'Shift 1'))
        ) {
            return 'AK1';
        }

        if (strcasecmp($department, 'Produksi') !== 0) {
            return 'AK';
        }

        if ($this->contains($jobTitle, 'broker')) {
            return 'AA';
        }

        return 'ZZ';
    }

    private function isPinHuluHilirSortingRow(array $row): bool
    {
        $employeeCode = (string) ($row['Employee Code'] ?? '');
        $name = (string) ($row['Full Name'] ?? '');
        $department = (string) ($row['Department'] ?? '');
        $jobTitle = (string) ($row['Job Title'] ?? '');

        if ($this->containsAny($name, ['Rizky Wahand', 'Chindyka Putr', 'Alenta Br. S', 'Angraini', 'Nirmala Sa', 'Bebby Valent', 'Deni Darman', 'Uci Rahmadan', 'Asima Afrianti', 'Ulfa Hayatu', 'Santi Eti', 'Ria Ade Per', 'Maysarah'])
            || in_array($employeeCode, ['120589', '120162'], true)
            || $this->containsAny($jobTitle, [
                'Operator Kunci', 'Operator Assembly Kunci', 'Produksi Hilir', 'Regu Packing', 'Regu Assembly',
                'Kru Assembly Sead Seal', 'Kru Assembly Kunci Cover', 'Kru Assembly Kunci Layer C', 'Kru Assembly Door Seal',
                'Kru Assembly Packing Lemari', 'Kru Assembly Packing Kursi', 'Operator Penggilingan', 'Kru Packing Meja &',
                'Operator Assembly Kunci Layer', 'Admin Produksi', 'Produksi Hulu', 'Teknisi Mesin Prod', 'Spv. Admin Produks',
                'HOT STAMPING', 'Ka. Regu Pencampuran', 'Kru Penggilingan', 'Operator Giling Bahan', 'Kru Strike Film',
                'Pencampur & Giling Baha', 'Operator Pencampuran', 'Operator Pencampur Baha', 'Long & Short Span',
                'Admin Produks', 'Kru Kunci Layer', 'Kru Kunci Cover', 'Kru Door Seal', 'Strapping Band', 'Kru Packing Lemari',
                'Kru Packing Kursi', 'Operator Packing Kursi', 'Operator Packing Lemari', 'Plastik Cover', 'FILM PIN', 'LONG &',
                'GILING BAH', 'HELPER TEK', 'HELPER PEN', 'ADM. PROD', 'KA. DIV. PROD', 'TEKNISI PRO', 'KEBERSIHAN LAP',
                'PACKING KUR', 'SIDE SEAL', 'KUNCI COV', 'KUNCI LAYER', 'BOR LOB', 'DOOR SEAL', 'BOTTOM FOOT',
                'PACKING', 'STRAPPING BAND', 'ADM. HASIL', 'ADM. INPUT',
            ])
        ) {
            return true;
        }

        return $this->contains($department, 'Produksi Inject')
            && $this->containsAny($jobTitle, ['Packing', 'Strapping', 'Hot Stamping']);
    }

    private function isWnbBriefingRow(array $row): bool
    {
        $department = (string) ($row['Department'] ?? '');
        $jobTitle = (string) ($row['Job Title'] ?? '');
        $workgroup = (string) ($row['Workgroup'] ?? '');

        if ($this->contains($department, 'Penerimaan Bahan Baku')) {
            return true;
        }

        if (! $this->contains($department, 'Washing & Broker')) {
            return false;
        }

        if ($this->contains($workgroup, 'Prod Ekstrusi')) {
            return false;
        }

        return $this->containsAny($jobTitle, [
            'Ka. Div. Washing & Broker',
            'Operator Cuci',
            'Kru Cuci',
            'Spv. Cuci',
            'Operator Bahan Awal',
        ]);
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
        $department = (string) ($row['Department'] ?? '');
        $employeeCode = (string) ($row['Employee Code'] ?? '');
        $dailyWorkerType = (string) ($row['Daily Worker Type Code'] ?? '');
        $scheduledShift = (string) ($row['Scheduled Shift'] ?? '');

        if (($this->contains($department, 'Marketing') || $this->contains($department, 'Sales'))
            && $dailyWorkerType === 'ST'
        ) {
            return 8.15;
        }

        if ($this->contains($scheduledShift, 'Prod Sab III (23')) {
            return 22.45;
        }

        if ($this->contains($scheduledShift, 'Prod Sab II (15')) {
            return 14.45;
        }

        if ($employeeCode === '120162') {
            return 8.30;
        }

        if ($employeeCode === '120728') {
            return 7.45;
        }

        return match ($this->shiftBroker($scheduledShift)) {
            'Pagi', 'Pagi1' => 6.45,
            'Sore' => 14.45,
            'Malam' => 22.45,
            'Siang1' => 11.45,
            'Sore1' => 16.45,
            default => $dailyWorkerType === 'ST' ? 7.45 : 7.45,
        };
    }

    private function shiftBroker(string $scheduledShift): string
    {
        if ($this->contains($scheduledShift, 'prod shift iii')) {
            return 'Malam';
        }

        if ($this->contains($scheduledShift, 'prod shift ii')) {
            return 'Sore';
        }

        if ($this->contains($scheduledShift, 'prod shift i')) {
            return 'Pagi';
        }

        if ($this->contains($scheduledShift, 'prod shift 1 - Sab')) {
            return 'Pagi1';
        }

        if ($this->contains($scheduledShift, 'prod shift 2 - Sab')) {
            return 'Siang1';
        }

        if ($this->contains($scheduledShift, 'prod shift 3 - Sab')) {
            return 'Sore1';
        }

        if ($this->contains($scheduledShift, 'prod shift 1')) {
            return 'Pagi';
        }

        if ($this->contains($scheduledShift, 'prod shift 2')) {
            return 'Sore';
        }

        if ($this->contains($scheduledShift, 'prod shift 3')) {
            return 'Malam';
        }

        return '';
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
        if ($this->contains($group, 'Sales')) {
            return [
                (string) ($row['Date'] ?? ''),
                $this->salesSortOrder((string) ($row['Full Name'] ?? '')),
                (string) ($row['Employee Code'] ?? ''),
            ];
        }

        if ($this->contains($group, 'Ekstrusi Malam')) {
            return [
                (string) ($row['Date'] ?? ''),
                $this->ekstrusiMalamSortOrder($row),
                (string) ($row['Full Name'] ?? ''),
                (string) ($row['Employee Code'] ?? ''),
            ];
        }

        if ($this->contains($group, 'PIN HULU')) {
            return [
                (string) ($row['Date'] ?? ''),
                $this->pinHuluHilirSortOrder((string) ($row['Full Name'] ?? '')),
                (string) ($row['Employee Code'] ?? ''),
            ];
        }

        if ($this->contains($group, 'bahan baku') || $this->contains($group, 'washing')) {
            return [
                (string) ($row['Date'] ?? ''),
                $this->wnbSortOrder($row),
                $this->nameSortKey($row),
                (string) ($row['Employee Code'] ?? ''),
            ];
        }

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
            $this->nameSortKey($row),
            (string) ($row['Employee Code'] ?? ''),
        ];
    }

    private function salesSortOrder(string $name): int
    {
        $order = [
            'Indah Kartika Ayu',
            'Tommi Syahputra',
            'Ella Nursita Sari',
            'Ariani Pertiwi',
            'Dira Indira',
            'Dwi Nanda Paridjal',
            'Fendy',
            'Jaya Hendra',
            'Muhammad Ade Irvan Simatupang',
            'Nur Haliza Rahayu',
            'Rudianto',
            'Sahrul Ramadhan',
            'Sugianto Gunawan',
            'Laksana Febri Wijaya Laia',
            'Erikson Roni Rumapea',
            'Bambang Paldawan',
            'Tita Andriani',
            'Viqih Rizky Lubis',
            'Nurusysyafillah',
            'Marina Mentari Br Kaban',
        ];

        foreach ($order as $index => $needle) {
            if ($this->contains($name, $needle)) {
                return $index + 1;
            }
        }

        return 999;
    }

    private function ekstrusiMalamSortOrder(array $row): int
    {
        return $this->contains((string) ($row['Job Title'] ?? ''), 'Ka. Regu') ? 10 : 20;
    }

    private function pinHuluHilirSortOrder(string $name): int
    {
        $order = [
            'Sri Wahyuni',
            'Amanda Harun',
            'Anggi Safitri',
            'Angraini',
            'Asima Afrianti',
            'Dormian Sitorus',
            'Koko Budiman',
            'Marsaulina Tobing',
            'Muhammad Andika',
            'Nila',
            'Posmo Atenta Bancin',
            'Rahmat Wahyudi',
            'Ramah Nurjanah',
            'Ruwahdi',
            'Sarinem',
            'Sarlan Baskoro',
            'Sartika',
            'Sefanya Jilfandi Palempung',
            'Susanti',
            'Wawan Setiawan',
        ];

        foreach ($order as $index => $needle) {
            if ($this->contains($name, $needle)) {
                return $index + 1;
            }
        }

        return 999;
    }

    private function nameSortKey(array $row): string
    {
        $employeeCode = (string) ($row['Employee Code'] ?? '');
        $name = (string) ($row['Full Name'] ?? '');
        $jobTitle = (string) ($row['Job Title'] ?? '');

        if ($this->contains($name, 'Ediyanto')) {
            return '00000'.$jobTitle;
        }

        if ($this->contains($jobTitle, 'Ast. ka. div.')) {
            return '02'.$jobTitle;
        }

        if ($this->contains($name, 'Fendy')) {
            return 'ZZ'.$name;
        }

        if ($this->contains($name, 'Fica Astri')) {
            return '00001'.$jobTitle;
        }

        if ($this->contains($name, 'Restu Arif')) {
            return 'ZZ'.$name;
        }

        if ($this->contains($name, 'Novelia Dinda U')) {
            return '003'.$jobTitle;
        }

        if ($this->contains($name, 'Surya Santo')) {
            return '003'.$jobTitle;
        }

        if ($this->contains($name, 'H Rinaldy A')) {
            return 'zz'.$name;
        }

        if ($this->contains($name, 'Abdul Hakim Hal')) {
            return '005'.$name;
        }

        if ($this->contains($name, 'Deni Darmansy')) {
            return '008'.$name;
        }

        if ($this->contains($name, 'Amsari')) {
            return '005'.$name;
        }

        if ($this->contains($name, 'Rahmad Fau')) {
            return '03'.$name;
        }

        if ($this->contains($name, 'Suria Ramadan')) {
            return '003'.$name;
        }

        if ($this->contains($name, 'Fahrul Rozy')) {
            return '05'.$name;
        }

        if ($this->contains($name, 'Ibal Sireg')) {
            return '007'.$name;
        }

        if ($employeeCode === '120469') {
            return '006'.$name;
        }

        if ($this->contains($jobTitle, 'ka. div. produksi brok')) {
            return '002'.$jobTitle;
        }

        if ($employeeCode === '120040') {
            return '0000'.$jobTitle;
        }

        if ($this->contains($jobTitle, 'ka. div.')) {
            return '001'.$jobTitle;
        }

        if ($this->contains($name, 'Wawan Setia')) {
            return 'ZZ'.$name;
        }

        if ($this->contains($name, 'Dedek Ramad')) {
            return '02'.$jobTitle;
        }

        if ($this->contains($jobTitle, 'ka. dept')) {
            return '000'.$jobTitle;
        }

        if ($this->contains($jobTitle, 'ka. tek')) {
            return '02'.$jobTitle;
        }

        if ($this->contains($jobTitle, 'ka. Regu')) {
            return '02'.$jobTitle;
        }

        if ($this->contains($name, 'Eko Heriant')) {
            return '03'.$jobTitle;
        }

        if ($this->contains($name, 'NETTY PAS')) {
            return '04'.$jobTitle;
        }

        if ($this->contains($name, 'Ibal Sir')) {
            return '007'.$jobTitle;
        }

        return match ($employeeCode) {
            '120240', '120096', '120589', '120660' => '001'.$jobTitle,
            '120437' => '002'.$jobTitle,
            '120241' => '003'.$jobTitle,
            '120057', '120018' => '02'.$jobTitle,
            '120476', '120360', '120640', '120396' => '03'.$jobTitle,
            '120407' => '04'.$jobTitle,
            default => 'zz'.$name,
        };
    }

    private function wnbSortOrder(array $row): int
    {
        $jobTitle = (string) ($row['Job Title'] ?? '');

        if ($this->contains($jobTitle, 'Ka. Div')) {
            return 10;
        }

        if ($this->contains($jobTitle, 'Ka. Regu')) {
            return 20;
        }

        return 30;
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
