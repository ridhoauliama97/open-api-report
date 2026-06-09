<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class RekapitulasiAbsensiBriefingHarianGsuReportService
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
        $filteredRows = array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->shouldIncludeRow($row, $period['start'], $period['end'])
        ));

        $summaryRows = $this->buildSummaryRows($filteredRows, $period['start'], $period['end']);

        return [
            'title' => 'Laporan Rekapitulasi Absensi Briefing Harian',
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'printed_by' => $this->resolvePrintedBy($rows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
            'headers' => ['No', 'Divisi', 'Jumlah Hadir Tidak Telat', 'Jumlah Telat', 'Jumlah Tidak Hadir', 'Jumlah Saat Pukul 15.00 WIB', 'Selisih', 'Keterangan'],
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
                'Workgroup' => $row['Workgroup'] ?? '',
                'Job Title' => $row['Job_x0020_Title'] ?? '',
                'Scheduled Shift' => $row['Scheduled_x0020_Shift'] ?? $row['Shift'] ?? '',
                'Date' => $row['Date'] ?? '',
                'Sign In Time' => $row['Sign_x0020_In_x0020__x0028_Time_x0029_'] ?? '',
                'Sign In Diff' => $row['Sign_x0020_In_x0020_Diff.'] ?? $row['Sign_x0020_In_x0020_Diff._x0020__x0028_Mins_x0029_'] ?? '',
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

    private function shouldIncludeRow(array $row, Carbon $startDate, Carbon $endDate): bool
    {
        $employeeCode = trim((string) ($row['Employee Code'] ?? ''));
        $date = $this->parseDate((string) ($row['Date'] ?? ''));

        return $employeeCode !== ''
            && ! str_starts_with(strtoupper($employeeCode), 'SPECIAL')
            && $date !== null
            && $date->betweenIncluded($startDate, $endDate)
            && trim((string) ($row['Present Absent'] ?? '')) !== '';
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, string|int>>
     */
    private function buildSummaryRows(array $rows, Carbon $startDate, Carbon $endDate): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $division = $this->initialDivision($row);
            if ($division === '') {
                continue;
            }

            $groups[$division][] = $row;
        }

        $result = [];
        foreach (['WNB', 'WHS', 'MKT', 'Prod Regu A', 'Prod Regu B', 'Prod Regu C', 'Prod Broker A', 'Prod Broker B', 'PIN'] as $division) {
            $items = $groups[$division] ?? [];
            if ($items === []) {
                continue;
            }

            $presentNoLate = count(array_filter($items, fn (array $row): bool => $this->hasSignIn($row) && ! $this->isLate($row)));
            $late = count(array_filter($items, fn (array $row): bool => $this->hasSignIn($row) && $this->isLate($row)));
            $notPresent = count(array_filter($items, fn (array $row): bool => ! $this->hasSignIn($row)))
                + $this->countMissingAttendanceDates($items, $startDate, $endDate);

            $result[] = [
                'Divisi' => $division,
                'Jumlah Hadir Tidak Telat' => $presentNoLate,
                'Jumlah Telat' => $late,
                'Jumlah Tidak Hadir' => $notPresent,
                'Jumlah Saat Pukul 15.00 Wib' => '',
                'Selisih' => '',
                'Keterangan' => '',
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, string>>  $items
     */
    private function countMissingAttendanceDates(array $items, Carbon $startDate, Carbon $endDate): int
    {
        $employeeDates = [];
        foreach ($items as $row) {
            $employeeCode = trim((string) ($row['Employee Code'] ?? ''));
            $date = $this->parseDate((string) ($row['Date'] ?? ''));
            if ($employeeCode === '' || $date === null) {
                continue;
            }

            $employeeDates[$employeeCode][$date->toDateString()] = true;
        }

        $missing = 0;
        $groupFirstDate = null;
        foreach ($employeeDates as $dates) {
            $availableDates = array_keys($dates);
            sort($availableDates);
            $employeeFirstDate = Carbon::parse($availableDates[0])->startOfDay();
            if ($groupFirstDate === null || $employeeFirstDate->lessThan($groupFirstDate)) {
                $groupFirstDate = $employeeFirstDate;
            }
        }

        if ($groupFirstDate === null) {
            return 0;
        }

        foreach ($employeeDates as $dates) {
            $availableDates = array_keys($dates);
            sort($availableDates);
            $firstDate = Carbon::parse($availableDates[0])->startOfDay();
            if (! $firstDate->equalTo($groupFirstDate)) {
                continue;
            }

            $cursor = $startDate->copy()->startOfDay();
            $last = $endDate->copy()->startOfDay();

            while ($cursor->lessThanOrEqualTo($last)) {
                if ($cursor->lessThan($firstDate)) {
                    $dateKey = $cursor->toDateString();
                    if (! isset($dates[$dateKey])) {
                        $missing++;
                    }
                }

                $cursor->addDay();
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function initialDivision(array $row): string
    {
        $nameDivision = $this->nameDivision($this->sortingCode($row));

        if ($this->contains($nameDivision, 'Bahan baku')) {
            return 'WNB';
        }

        if ($this->contains($nameDivision, 'warehouse')) {
            return 'WHS';
        }

        if ($this->contains($nameDivision, 'inject')) {
            return 'PIN';
        }

        if ($this->contains($nameDivision, 'marketing')) {
            return 'MKT';
        }

        if ($this->contains($nameDivision, 'Broker a')) {
            return 'Prod Broker A';
        }

        if ($this->contains($nameDivision, 'Broker b')) {
            return 'Prod Broker B';
        }

        if ($this->contains($nameDivision, 'Regu A')) {
            return 'Prod Regu A';
        }

        if ($this->contains($nameDivision, 'Regu B')) {
            return 'Prod Regu B';
        }

        if ($this->contains($nameDivision, 'Regu C')) {
            return 'Prod Regu C';
        }

        return trim($nameDivision);
    }

    private function nameDivision(string $sorting): string
    {
        if ($this->contains($sorting, 'AA')) {
            return 'bahan baku';
        }

        if ($this->contains($sorting, 'AC')) {
            return 'warehouse';
        }

        if ($this->contains($sorting, 'ZZ')) {
            return 'inject';
        }

        if ($this->contains($sorting, 'AB')) {
            return 'marketing';
        }

        if ($this->contains($sorting, 'AJ3')) {
            return 'regu c';
        }

        if ($this->contains($sorting, 'AJ2')) {
            return 'regu b';
        }

        if ($this->contains($sorting, 'AJ1')) {
            return 'regu a';
        }

        if ($this->contains($sorting, 'AK1')) {
            return 'broker a';
        }

        if ($this->contains($sorting, 'AK2')) {
            return 'broker b';
        }

        return '';
    }

    /**
     * @param  array<string, string>  $row
     */
    private function sortingCode(array $row): string
    {
        $name = (string) ($row['Full Name'] ?? '');
        $department = (string) ($row['Department'] ?? '');
        $jobTitle = (string) ($row['Job Title'] ?? '');
        $workgroup = (string) ($row['Workgroup'] ?? '');

        if (strcasecmp($name, 'alfisyah rizal') === 0) {
            return 'AC';
        }

        if (
            $this->contains($jobTitle, 'bahan baku')
            || $this->contains($jobTitle, 'washing')
            || $this->contains($jobTitle, 'div stock')
            || $this->contains($jobTitle, 'Cleaning')
            || $this->contains($jobTitle, 'forklift')
        ) {
            return 'AA';
        }

        if (strcasecmp($department, 'marketing') === 0) {
            return 'AB';
        }

        if (strcasecmp($department, 'gudang') === 0) {
            return 'AC';
        }

        if (strcasecmp($department, 'Produksi') !== 0) {
            return 'AK';
        }

        if ($this->contains($workgroup, 'Produksi regu iii')) {
            return 'AJ3';
        }

        if ($this->contains($workgroup, 'Produksi regu ii')) {
            return 'AJ2';
        }

        if ($this->contains($workgroup, 'Produksi regu i')) {
            return 'AJ1';
        }

        if ($this->contains($workgroup, 'Broker II')) {
            return 'AK2';
        }

        if ($this->contains($workgroup, 'Broker I')) {
            return 'AK1';
        }

        if ($this->contains($jobTitle, 'broker')) {
            return 'AA';
        }

        return 'ZZ';
    }

    /**
     * @param  array<int, array<string, string|int>>  $rows
     * @return array<string, int|string>
     */
    private function buildGrandSummary(array $rows): array
    {
        return [
            'Jumlah Hadir Tidak Telat' => array_sum(array_map(static fn (array $row): int => (int) ($row['Jumlah Hadir Tidak Telat'] ?? 0), $rows)),
            'Jumlah Telat' => array_sum(array_map(static fn (array $row): int => (int) ($row['Jumlah Telat'] ?? 0), $rows)),
            'Jumlah Tidak Hadir' => array_sum(array_map(static fn (array $row): int => (int) ($row['Jumlah Tidak Hadir'] ?? 0), $rows)),
            'Jumlah Saat Pukul 15.00 Wib' => '',
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

        return $signInDiff < 0;
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
