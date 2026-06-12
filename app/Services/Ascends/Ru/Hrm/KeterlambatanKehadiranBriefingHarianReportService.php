<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class KeterlambatanKehadiranBriefingHarianReportService
{
    private const TITLE = 'Laporan Keterlambatan Kehadiran Briefing Harian';

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $company = self::resolveCompany($filters);
        $aggregates = $this->aggregateXml($xmlContents, $sourceLabel, $company, $filters);
        $period = $aggregates['period'];
        $months = self::resolveMonths($period);
        $rows = [];
        $totals = array_fill_keys(array_keys($months), 0);

        foreach ($aggregates['employees'] as $employee) {
            $row = [
                'Kode' => $employee['code'],
                'Nama' => $employee['name'],
                'Jabatan' => $employee['job_title'],
                'months' => [],
            ];

            foreach ($months as $monthKey => $monthLabel) {
                $count = (int) ($employee['months'][$monthKey] ?? 0);
                $row['months'][$monthKey] = [
                    'label' => $monthLabel,
                    'value' => $count,
                ];
                $totals[$monthKey] += $count;
            }

            $rows[] = $row;
        }

        usort($rows, static fn(array $left, array $right): int => strcasecmp((string) $left['Nama'], (string) $right['Nama']));

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => '',
            'headers' => ['Kode', 'Nama', 'Jabatan', ...array_values($months)],
            'month_keys' => array_keys($months),
            'month_labels' => $months,
            'rows' => $rows,
            'totals' => $totals,
            'total_rows' => count($rows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari ' . $period['start']->locale('id')->translatedFormat('d-M-y') . ' Sampai ' . $period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{period: array{start: Carbon, end: Carbon}, employees: array<string, array{code: string, name: string, job_title: string, months: array<string, int>}>}
     */
    private function aggregateXml(string $xmlContents, string $sourceLabel, string $company, array $filters): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Attendance kosong.');
        }

        $reader = new XMLReader;
        if (!@$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Attendance tidak valid: {$sourceLabel}");
        }

        $period = self::resolveRequestedPeriod($filters);
        $dates = [];
        $employees = [];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'attendacesimple') {
                continue;
            }

            $recordXml = $reader->readOuterXML();
            if (!is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }

            $date = self::parseDate((string) ($node->Date ?? ''));
            $employeeCode = trim((string) ($node->{'Employee_x0020_Code'} ?? ''));
            if ($date === null || $employeeCode === '' || str_starts_with(strtoupper($employeeCode), 'SPECIAL')) {
                continue;
            }

            $dates[] = $date->copy();
            if ($period !== null && !$date->betweenIncluded($period['start'], $period['end'])) {
                continue;
            }

            $department = trim((string) ($node->{'Department_x0020_Name'} ?? ''));
            if ($department === 'Management' || !self::isWorkday($node, $company) || !self::isLate($node, $company)) {
                continue;
            }

            $key = $employeeCode . '|' . trim((string) ($node->{'Full_x0020_Name'} ?? '')) . '|' . trim((string) ($node->{'Job_x0020_Title'} ?? ''));
            if (!isset($employees[$key])) {
                $employees[$key] = [
                    'code' => $employeeCode,
                    'name' => trim((string) ($node->{'Full_x0020_Name'} ?? '')),
                    'job_title' => trim((string) ($node->{'Job_x0020_Title'} ?? '')),
                    'months' => [],
                ];
            }

            $monthKey = $date->format('Y-m');
            $employees[$key]['months'][$monthKey] = ($employees[$key]['months'][$monthKey] ?? 0) + 1;
        }

        $reader->close();

        if ($dates === []) {
            throw new RuntimeException('Data Attendance tidak ditemukan di XML.');
        }

        if ($period === null) {
            usort($dates, static fn(Carbon $left, Carbon $right): int => $left <=> $right);
            $period = [
                'start' => $dates[0]->copy()->startOfDay(),
                'end' => $dates[count($dates) - 1]->copy()->endOfDay(),
            ];
        }

        return [
            'period' => $period,
            'employees' => $employees,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start: Carbon, end: Carbon}|null
     */
    private static function resolveRequestedPeriod(array $filters): ?array
    {
        $start = self::parseDate((string) ($filters['start_date'] ?? $filters['TglAwal'] ?? ''));
        $end = self::parseDate((string) ($filters['end_date'] ?? $filters['TglAkhir'] ?? ''));
        if ($start === null && $end === null) {
            return null;
        }

        $start ??= $end?->copy();
        $end ??= $start?->copy();
        if ($start === null || $end === null) {
            return null;
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<string, string>
     */
    private static function resolveMonths(array $period): array
    {
        $months = [];
        $cursor = $period['start']->copy()->startOfMonth();
        $end = $period['end']->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            $months[$cursor->format('Y-m')] = $cursor->locale('id')->translatedFormat('M');
            $cursor->addMonthNoOverflow();
        }

        return $months;
    }

    private static function isWorkday(\SimpleXMLElement $node, string $company = 'GSU'): bool
    {
        if (self::contains((string) ($node->Day ?? ''), 'Sunday')) {
            return false;
        }

        $holidayName = trim((string) ($node->{'Holiday_x0020_Name'} ?? ''));
        $scheduledShift = (string) ($node->{'Scheduled_x0020_Shift'} ?? '');

        if ($company === 'RU') {
            if (self::isOffShift($scheduledShift)) {
                return false;
            }

            if ($holidayName !== '') {
                return false;
            }

            return true;
        }

        if ($holidayName === '') {
            return true;
        }

        if (self::isOffShift($scheduledShift)) {
            return false;
        }

        return false;
    }

    private static function isLate(\SimpleXMLElement $node, string $company = 'GSU'): bool
    {
        $jamMasuk = self::jamMasuk(
            (string) ($node->Workgroup ?? ''),
            (string) ($node->{'Scheduled_x0020_Shift'} ?? ''),
            $company
        );
        $gabungJam = self::gabungJam(
            (string) ($node->{'Sign_x0020_In_x0020__x0028_Time_x0029_'} ?? ''),
            (string) ($node->{'Sign_x0020_In'} ?? '')
        );

        return $gabungJam !== null && $gabungJam > $jamMasuk;
    }

    private static function jamMasuk(string $workgroup, string $scheduledShift, string $company = 'GSU'): int
    {
        if ($company === 'RU') {
            return self::jamMasukRu($workgroup, $scheduledShift);
        }

        return self::jamMasukGsu($workgroup, $scheduledShift);
    }


    private static function jamMasukGsu(string $workgroup, string $scheduledShift): int
    {
        $workgroupRules = [
            'Staff Office PT. UC I' => 745,
            'Staff Office II (08' => 745,
            'Staff Office III (08.30' => 815,
            'Staff Office PT UC II' => 815,
            'Kary. Normal Shift' => 745,
            'Kary. Normal (06' => 745,
            'Kary. Normal KL (08' => 745,
            'Security Normal' => 745,
            'Diesel I' => 745,
        ];

        foreach ($workgroupRules as $needle => $value) {
            if (self::contains($workgroup, $needle)) {
                return $value;
            }
        }

        $shiftRules = [
            'Kary.(08' => 745,
            'Sabtu (08' => 745,
            'Prod Shift III' => 2245,
            'Prod Shift II' => 1445,
            'Prod Shift I' => 645,
            'Prod Shift 1 - Sab' => 645,
            'Prod Shift 2 - Sab' => 1145,
            'Prod Shift 3 - Sab' => 1645,
            'Security Pagi' => 745,
            'Security Malam' => 1945,
            'Security Normal (08' => 745,
        ];

        foreach ($shiftRules as $needle => $value) {
            if (self::contains($scheduledShift, $needle)) {
                return $value;
            }
        }

        return 0;
    }

    private static function jamMasukRu(string $workgroup, string $scheduledShift): int
    {
        $workgroupRules = [
            'Staff Office I (08' => 745,
            'Staff Office II (08.30' => 815,
        ];

        foreach ($workgroupRules as $needle => $value) {
            if (self::contains($workgroup, $needle)) {
                return $value;
            }
        }

        $shiftRules = [
            'Karyawan Normal (07' => 645,
            'Karyawan Normal  (08' => 745,
            'Kary. Normal Sab (08' => 745,
            'Kary. Shift 1 Sab(07' => 645,
            'Kary. Jumat (08' => 745,
            'Normal Shift III (23' => 2245,
            'Prod.Shift II (15' => 1445,
            'Prod.Shift IISabt' => 1445,
            'Shift III Sabt (23' => 2245,
            'Vacuum Shift Malam' => 1845,
            'Vacuum Shift II (20' => 1945,
            'Vacuum Malam (Sabtu' => 1845,
            'Borongan Sawmill 08.' => 745,
        ];

        foreach ($shiftRules as $needle => $value) {
            if (self::contains($scheduledShift, $needle)) {
                return $value;
            }
        }

        return 0;
    }

    private static function gabungJam(string $signInTime, string $signIn): ?int
    {
        $timeCode = self::timeCode($signInTime);
        if ($timeCode !== null) {
            return $timeCode;
        }

        $date = self::parseDate($signIn);

        return $date === null ? null : ((int) $date->locale('id')->translatedFormat('H') * 100) + (int) $date->locale('id')->translatedFormat('i');
    }

    private static function timeCode(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})[:.](\d{2})/', $value, $matches) !== 1) {
            return null;
        }

        return ((int) $matches[1] * 100) + (int) $matches[2];
    }

    private static function contains(string $value, string $needle): bool
    {
        return stripos($value, $needle) !== false;
    }

    private static function isOffShift(string $scheduledShift): bool
    {
        return self::contains($scheduledShift, 'Off');
    }

    private static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function resolveCompany(array $filters): string
    {
        $company = trim((string) ($filters['company'] ?? ''));

        return $company !== '' ? strtoupper($company) : 'GSU';
    }
}
