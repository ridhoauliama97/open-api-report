<?php

namespace App\Services\Ascends\Ru\Hrm;

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
            'printed_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'printed_by' => $this->resolvePrintedBy($rows),
            'group' => $groupFilter,
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => $period['start']->format('Y-m-d') === $period['end']->format('Y-m-d')
                    ? $period['end']->locale('id')->translatedFormat('d-M-y')
                    : $period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
            'headers' => ['Divisi', 'Jumlah Hadir Tidak Telat', 'Jumlah Telat', 'Jumlah Tidak Hadir', 'Jumlah Saat Pukul 12.55 Wib', 'Selisih', 'Keterangan'],
            'rows' => $summaryRows,
            'grand_summary' => $this->buildGrandSummary($summaryRows),
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
                if ($end->lessThan($start)) {
                    [$start, $end] = [$end, $start];
                }

                return ['start' => $start->startOfDay(), 'end' => $end->endOfDay()];
            }
        }

        $reportDate = trim((string) ($filters['report_date'] ?? $filters['tanggal'] ?? $filters['date'] ?? ''));
        if ($reportDate !== '') {
            $date = $this->parseDate($reportDate) ?? throw new RuntimeException("Format tanggal tidak valid: {$reportDate}.");

            return ['start' => $date->copy()->startOfDay(), 'end' => $date->copy()->endOfDay()];
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

        return ['start' => $latest->copy()->startOfDay(), 'end' => $latest->copy()->endOfDay()];
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
        if ($employeeCode === ''
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

            $presentNoLate = count(array_filter($items, fn (array $row): bool => $this->hasSignIn($row) && ! $this->isLate($row)));
            $late = count(array_filter($items, fn (array $row): bool => $this->isLate($row)));
            $notPresent = count(array_filter($items, fn (array $row): bool => $this->isAbsent($row)));

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
        $department = strtoupper(trim((string) ($row['Department'] ?? '')));
        $division = strtoupper(trim((string) ($row['Division'] ?? '')));

        if ($division === 'PHU') {
            return 'PHU';
        }

        if ($division === 'PHI') {
            return 'PHI';
        }

        if (($department === 'VACUUM & K/D' && $division === 'K/D') || in_array($division, ['K/D', 'VKD'], true)) {
            return 'VKD';
        }

        if ($department === 'SAWMILL' || in_array($division, ['SML', 'BAND SAW', 'SLP', 'STICK ST'], true)) {
            return 'SML';
        }

        return '';
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return array<string, int|string>
     */
    private function buildGrandSummary(array $rows): array
    {
        $presentNoLate = array_sum(array_map(static fn (array $row): int => (int) ($row['Jumlah Hadir Tidak Telat'] ?? 0), $rows));
        $late = array_sum(array_map(static fn (array $row): int => (int) ($row['Jumlah Telat'] ?? 0), $rows));
        $notPresent = array_sum(array_map(static fn (array $row): int => (int) ($row['Jumlah Tidak Hadir'] ?? 0), $rows));

        return [
            'Jumlah Hadir Tidak Telat' => $presentNoLate,
            'Jumlah Telat' => $late,
            'Jumlah Tidak Hadir' => $notPresent,
            'Jumlah Saat Pukul 12.55 Wib' => '',
            'Selisih' => '',
            'Keterangan' => '',
        ];
    }

    private function hasSignIn(array $row): bool
    {
        return trim((string) ($row['Sign In Time'] ?? '')) !== '';
    }

    private function isLate(array $row): bool
    {
        $signInDiff = (float) str_replace(',', '.', (string) ($row['Sign In Diff'] ?? '0'));

        return $this->hasSignIn($row) && $signInDiff > 0;
    }

    private function isAbsent(array $row): bool
    {
        return strtoupper(trim((string) ($row['Present Absent'] ?? ''))) === 'ABSENT';
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
