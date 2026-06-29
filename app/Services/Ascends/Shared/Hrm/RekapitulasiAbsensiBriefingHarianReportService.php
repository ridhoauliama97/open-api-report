<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class RekapitulasiAbsensiBriefingHarianReportService
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
        $groupFilter = $this->resolveGroupFilter($filters);
        $filteredRows = array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->shouldIncludeRow($row, $period['start'], $period['end'], $groupFilter)
        ));

        $summaryRows = $this->buildSummaryRows($filteredRows);

        return [
            'title' => 'Laporan Rekapitulasi Absensi Briefing Harian',
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->translatedFormat('d-M-y H:i'),
            'printed_by' => $this->resolvePrintedBy($rows),
            'group' => $groupFilter,
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
            'headers' => ['No', 'Divisi', 'Jumlah Hadir Tidak Telat', 'Jumlah Telat', 'Jumlah Tidak Hadir', 'Jumlah Saat Pukul 12.55 Wib', 'Selisih', 'Keterangan'],
            'rows' => $summaryRows,
            'grand_summary' => $this->buildGrandSummary($summaryRows, $this->vinyNotPresentAdjustment($filteredRows)),
            'total_rows' => count($summaryRows),
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
                if ($startDate === '' || $endDate === '') {
                    return [
                        'start' => $end->copy()->startOfMonth()->startOfDay(),
                        'end' => $end->copy()->endOfMonth()->endOfDay(),
                    ];
                }

                if ($end->lessThan($start)) {
                    [$start, $end] = [$end, $start];
                }

                return ['start' => $start->startOfDay(), 'end' => $end->endOfDay()];
            }
        }

        $reportDate = trim((string) ($filters['report_date'] ?? $filters['tanggal'] ?? $filters['date'] ?? ''));
        if ($reportDate !== '') {
            $date = $this->parseDate($reportDate) ?? throw new RuntimeException("Format tanggal tidak valid: {$reportDate}.");

            return ['start' => $date->copy()->startOfMonth()->startOfDay(), 'end' => $date->copy()->endOfMonth()->endOfDay()];
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
        $latest = $dates[count($dates) - 1];

        return ['start' => $latest->copy()->startOfMonth()->startOfDay(), 'end' => $latest->copy()->endOfMonth()->endOfDay()];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveGroupFilter(array $filters): string
    {
        return strtoupper(trim((string) ($filters['group'] ?? $filters['division'] ?? $filters['divisi'] ?? '')));
    }

    private function shouldIncludeRow(array $row, Carbon $startDate, Carbon $endDate, string $groupFilter): bool
    {
        $employeeCode = trim((string) ($row['Employee Code'] ?? ''));
        $date = $this->parseDate((string) ($row['Date'] ?? ''));
        if (
            $employeeCode === ''
            || str_starts_with(strtoupper($employeeCode), 'SPECIAL')
            || $date === null
            || ! $date->betweenIncluded($startDate, $endDate)
        ) {
            return false;
        }

        if (trim((string) ($row['Present Absent'] ?? '')) === '') {
            return false;
        }

        if ($groupFilter === '') {
            return true;
        }

        foreach ([$row['Department'] ?? '', $row['Division'] ?? '', $row['Sub Division'] ?? '', $row['Workgroup'] ?? ''] as $value) {
            $normalized = strtoupper(trim((string) $value));
            if ($normalized === $groupFilter || str_contains($normalized, $groupFilter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, string|int>>
     */
    private function buildSummaryRows(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $group = $this->briefingDivision($row);
            if ($group === '') {
                continue;
            }

            $groups[$group][] = $row;
        }

        $result = [];
        foreach (['SML', 'VKD', 'PHU', 'PHI'] as $group) {
            $items = $groups[$group] ?? [];
            if ($items === []) {
                continue;
            }

            $presentNoLate = count(array_filter($items, fn (array $row): bool => $this->isBriefingPresentNoLate($row)));
            $notPresent = count(array_filter($items, fn (array $row): bool => ! $this->hasSignIn($row)));
            $late = count($items) - $presentNoLate - $notPresent;

            $result[] = [
                'Divisi' => $group,
                'Jumlah Hadir Tidak Telat' => $presentNoLate,
                'Jumlah Telat' => $late,
                'Jumlah Tidak Hadir' => $notPresent,
                'Jumlah Saat Pukul 12.55 Wib' => '',
                'Selisih' => '',
                'Keterangan' => '',
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function briefingDivision(array $row): string
    {
        return match ($this->sortingCode($row)) {
            'AA', 'AB' => 'SML',
            'AC' => 'VKD',
            'AD' => 'PHU',
            'AE' => 'PHI',
            default => '',
        };
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return array<string, int|string>
     */
    private function buildGrandSummary(array $rows, int $vinyNotPresentAdjustment = 0): array
    {
        $presentNoLate = array_sum(array_map(static fn (array $row): int => (int) ($row['Jumlah Hadir Tidak Telat'] ?? 0), $rows));
        $late = array_sum(array_map(static fn (array $row): int => (int) ($row['Jumlah Telat'] ?? 0), $rows));
        $notPresent = array_sum(array_map(static fn (array $row): int => (int) ($row['Jumlah Tidak Hadir'] ?? 0), $rows)) + $vinyNotPresentAdjustment;

        return [
            'Jumlah Hadir Tidak Telat' => $presentNoLate,
            'Jumlah Telat' => $late,
            'Jumlah Tidak Hadir' => $notPresent,
            'Jumlah Saat Pukul 12.55 Wib' => '',
            'Selisih' => '',
            'Keterangan' => '',
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function vinyNotPresentAdjustment(array $rows): int
    {
        return $rows === [] ? 0 : 1;
    }

    private function hasSignIn(array $row): bool
    {
        return trim((string) ($row['Sign In Time'] ?? '')) !== '';
    }

    private function isBriefingPresentNoLate(array $row): bool
    {
        if (! $this->hasSignIn($row)) {
            return false;
        }

        $time = $this->timeNew((string) ($row['Sign In Time'] ?? ''));
        $shift = $this->shiftValue((string) ($row['Scheduled Shift'] ?? ''));

        return $time !== null && $shift !== null && $time <= $shift;
    }

    private function timeNew(string $value): ?float
    {
        if (! preg_match('/(\d{1,2}):(\d{2})/', $value, $matches)) {
            return null;
        }

        return ((int) $matches[1]) + (((int) $matches[2]) / 100);
    }

    private function shiftValue(string $value): ?float
    {
        if ($this->contains($value, 'Shift iii')) {
            return 0.45;
        }

        if ($this->contains($value, 'Shift ii')) {
            return 19.45;
        }

        if (
            $this->contains($value, 'normal kl')
            || $this->contains($value, 'normal kt')
            || $this->contains($value, 'normal staff')
            || $this->contains($value, 'Shift i')
        ) {
            return 7.45;
        }

        return null;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function sortingCode(array $row): string
    {
        $employeeCode = (string) ($row['Employee Code'] ?? '');
        $name = (string) ($row['Full Name'] ?? '');
        $department = (string) ($row['Department'] ?? '');
        $jobTitle = (string) ($row['Job Title'] ?? '');

        if ($this->contains($jobTitle, 'kayu Bulat')) {
            return 'AA';
        }

        if ($this->contains($employeeCode, '131157') || $this->contains($employeeCode, '131182')) {
            return 'AE';
        }

        if ($this->contains($employeeCode, '131145')) {
            return 'AB';
        }

        if ($this->contains($employeeCode, '130206')) {
            return 'AE';
        }

        if ($this->contains($name, 'Benjamin')) {
            return 'ZZ';
        }

        if ($this->contains($department, 'maintenance')) {
            return 'AE';
        }

        if ($this->contains($jobTitle, 'Ka. div penerimaan')) {
            return 'AA';
        }

        if (
            $this->contains($jobTitle, 'grenda')
            || $name === 'Dalifao Lase'
            || $this->contains($jobTitle, 'sawmil')
            || $this->contains($jobTitle, 'tally stick')
            || $this->contains($jobTitle, 'penerimaan')
            || $this->contains($jobTitle, 'service lapangan')
            || $this->contains($jobTitle, 'forklift 6.0')
            || $this->contains($jobTitle, 'grader s')
            || $this->contains($jobTitle, 'kernet for')
        ) {
            return 'AB';
        }

        if (
            $this->contains($jobTitle, 'Vacuum')
            || $this->contains($jobTitle, 'KD')
            || $this->contains($jobTitle, 'oto carry')
            || $this->contains($jobTitle, 'forklift 3.5 ton')
            || $this->contains($name, 'zulham efendy')
        ) {
            return 'AC';
        }

        if ($this->contains($jobTitle, 'PPIC')) {
            return 'AF';
        }

        if ($this->contains($jobTitle, 'cut akhir')) {
            return 'AE';
        }

        if (
            $this->contains($jobTitle, 'S4s')
            || $this->contains($jobTitle, 'planner')
            || $this->contains($jobTitle, 'ripsaw')
            || $this->contains($jobTitle, 'rip saw')
            || $this->contains($jobTitle, 'cross')
            || $this->contains($jobTitle, 'forklift 2.5')
            || $this->contains($jobTitle, 'mandor s4s')
            || $this->contains($jobTitle, 'Singel Rip')
            || $this->contains($jobTitle, 'finger joint')
        ) {
            return 'AD';
        }

        if (
            $this->contains($jobTitle, 'Rotary')
            || $this->contains($jobTitle, 'moulding')
            || $this->contains($jobTitle, 'sanding')
            || $this->contains($jobTitle, 'setting')
            || $this->contains($jobTitle, 'packing')
            || $this->contains($jobTitle, 'produksi hilir')
            || $this->contains($jobTitle, 'tally produksi')
            || $this->contains($jobTitle, 'Mandor rotary')
        ) {
            return 'AE';
        }

        if (
            $this->contains($jobTitle, 'adm stock')
            || $this->contains($jobTitle, 'gudang')
            || $this->contains($jobTitle, 'hrd')
            || $this->contains($jobTitle, 'div stock')
            || $this->contains($jobTitle, 'div.produksi')
            || $this->contains($jobTitle, 'kantor')
            || $this->contains($jobTitle, 'KA. DIVISI PRO')
        ) {
            return 'AF';
        }

        return 'ZZ';
    }

    private function contains(string $haystack, string $needle): bool
    {
        return stripos($haystack, $needle) !== false;
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
