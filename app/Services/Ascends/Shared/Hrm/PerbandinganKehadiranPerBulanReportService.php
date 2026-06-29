<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PerbandinganKehadiranPerBulanReportService
{
    private const TITLE = 'Laporan Perbandingan Kehadiran Per Bulan';

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $aggregates = $this->aggregateXml($xmlContents, $sourceLabel, $filters);
        $period = $aggregates['period'];
        $months = self::resolveMonths($period);
        $sections = [];

        foreach (['Staff', 'KK/KT'] as $type) {
            $rows = [];
            foreach ($months as $monthKey => $monthLabel) {
                $bucket = $aggregates['types'][$type][$monthKey] ?? [
                    'employees' => [],
                    'absence_count' => 0,
                    'late_count' => 0,
                ];
                $totalEmployees = count($bucket['employees'] ?? []);
                $absenceCount = (int) ($bucket['absence_count'] ?? 0);
                $lateCount = (int) ($bucket['late_count'] ?? 0);

                $rows[] = [
                    'Bulan' => $monthLabel,
                    'Total Karyawan' => self::formatNumber($totalEmployees),
                    'Jumlah Ketidakhadiran' => self::formatNumber($absenceCount),
                    '% Ketidakhadiran' => self::formatPercent($totalEmployees > 0 ? ($absenceCount / $totalEmployees) * 100 : 0),
                    'Jumlah Terlambat' => self::formatNumber($lateCount),
                    '% Terlambat' => self::formatPercent($totalEmployees > 0 ? ($lateCount / $totalEmployees) * 100 : 0),
                ];
            }

            $sections[] = [
                'title' => $type,
                'rows' => $rows,
            ];
        }

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => '',
            'headers' => ['Bulan', 'Total Karyawan', 'Jumlah Ketidakhadiran', '% Ketidakhadiran', 'Jumlah Terlambat', '% Terlambat'],
            'sections' => $sections,
            'total_rows' => array_sum(array_map(static fn (array $section): int => count($section['rows'] ?? []), $sections)),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{period: array{start: Carbon, end: Carbon}, types: array<string, array<string, array{employees: array<string, true>, absence_count: int, late_count: int}>}
     */
    private function aggregateXml(string $xmlContents, string $sourceLabel, array $filters): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Attendance kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Attendance tidak valid: {$sourceLabel}");
        }

        $period = self::resolveRequestedPeriod($filters);
        $dates = [];
        $types = [
            'Staff' => [],
            'KK/KT' => [],
        ];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || ! in_array(strtolower($reader->name), ['attendacesimple', 'attendance'], true)) {
                continue;
            }

            $recordXml = $reader->readOuterXML();
            if (! is_string($recordXml) || trim($recordXml) === '') {
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
            if ($period !== null && ! $date->betweenIncluded($period['start'], $period['end'])) {
                continue;
            }

            $day = trim((string) ($node->Day ?? ''));
            $department = trim((string) ($node->{'Department_x0020_Name'} ?? ''));
            if (self::startsWith($day, 'Sunday') || self::startsWith($department, 'Management')) {
                continue;
            }

            $type = self::resolveWorkerType((string) ($node->{'Daily_x0020_Worker_x0020_Type_x0020_Code'} ?? $node->{'Worker_x0020_Type_x0020_Code'} ?? ''));
            if ($type === null) {
                continue;
            }

            $monthKey = $date->format('Y-m');
            if (! isset($types[$type][$monthKey])) {
                $types[$type][$monthKey] = [
                    'employees' => [],
                    'absence_count' => 0,
                    'late_count' => 0,
                ];
            }

            $types[$type][$monthKey]['employees'][$employeeCode] = true;

            $signInTime = trim((string) ($node->{'Sign_x0020_In_x0020__x0028_Time_x0029_'} ?? ''));
            $signIn = trim((string) ($node->{'Sign_x0020_In'} ?? ''));
            if ($signInTime === '' && $signIn === '') {
                $types[$type][$monthKey]['absence_count']++;

                continue;
            }

            $holidayName = trim((string) ($node->{'Holiday_x0020_Name'} ?? ''));
            if ($holidayName === '' && ! self::isNationalHoliday($date) && self::isLateByFormula($signInTime, $signIn, (string) ($node->Shift ?? ''), (string) ($node->Workgroup ?? ''))) {
                $types[$type][$monthKey]['late_count']++;
            }
        }

        $reader->close();

        if ($dates === []) {
            throw new RuntimeException('Data Attendance tidak ditemukan di XML.');
        }

        if ($period === null) {
            usort($dates, static fn (Carbon $left, Carbon $right): int => $left <=> $right);
            $period = [
                'start' => $dates[0]->copy()->startOfDay(),
                'end' => $dates[count($dates) - 1]->copy()->endOfDay(),
            ];
        }

        return [
            'period' => $period,
            'types' => $types,
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
            $months[$cursor->format('Y-m')] = $cursor->locale('id')->translatedFormat('F');
            $cursor->addMonthNoOverflow();
        }

        return $months;
    }

    private static function resolveWorkerType(string $value): ?string
    {
        $code = strtoupper(trim($value));
        if ($code === '') {
            return null;
        }

        return str_starts_with($code, 'ST') ? 'Staff' : 'KK/KT';
    }

    private static function isLateByFormula(string $signInTime, string $signIn, string $shift, string $workgroup): bool
    {
        $hasilJam = self::hasilJam($signInTime, $signIn);
        if ($hasilJam === null) {
            return false;
        }

        return $hasilJam > self::jamKerja($shift, $workgroup);
    }

    private static function hasilJam(string $signInTime, string $signIn): ?int
    {
        $timeCode = self::timeCode($signInTime);
        if ($timeCode !== null) {
            return $timeCode;
        }

        $date = self::parseDate($signIn);

        return $date === null ? null : ((int) $date->format('H') * 100) + (int) $date->format('i');
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

    private static function jamKerja(string $shift, string $workgroup): int
    {
        $rules = [
            '23.00-07.00' => 2245,
            '08.00-17.00' => 745,
            '08-17.30' => 745,
            '08 - 17.30' => 745,
            '08.00-13.00' => 745,
            '08.0-13.0' => 745,
            '08.00-' => 745,
            '(08.00)' => 745,
            '08.-18' => 745,
            '08-16.30' => 745,
            '08.30-' => 815,
            '(08.30)' => 815,
            '08-30' => 815,
            '08.3-13.0' => 815,
            'Office II Jumat' => 815,
            'Staff Produksi' => 830,
            '07.00-' => 645,
            '12.00-' => 1145,
            '13.00-' => 1245,
            '15.00-' => 1445,
            '15:00-' => 1445,
            '20.00-' => 1945,
            'Prod Shift III' => 2245,
            'Prod Shift II' => 1445,
            'Prod Shift I' => 645,
            'Prod Shift 1' => 645,
            'Prod Shift 2' => 1145,
            'Staff Jumat II' => 815,
            'Shift Jumat' => 745,
            'Kary. Normal Shift' => 745,
            'Kary. Normal Sabtu' => 745,
            'KL/KT Jumat' => 745,
            'Normal Shift II' => 745,
            'Security Pagi' => 745,
            'Security Malam' => 1945,
            'Security Normal 2' => 815,
            'Genset Sab' => 2245,
            'Genset III' => 2245,
            'Genset II' => 1445,
            'Genset I' => 645,
        ];

        foreach ($rules as $needle => $value) {
            if (self::contains($shift, $needle)) {
                return $value;
            }
        }

        return self::contains($workgroup, '(06.00-18.00)') ? 545 : 0;
    }

    private static function startsWith(string $value, string $needle): bool
    {
        return str_starts_with(trim($value), $needle);
    }

    private static function contains(string $value, string $needle): bool
    {
        return str_contains($value, $needle);
    }

    private static function isNationalHoliday(Carbon $date): bool
    {
        return in_array($date->toDateString(), [
            '2021-01-01',
            '2021-01-02',
            '2021-02-11',
            '2021-02-12',
            '2021-02-13',
            '2021-03-11',
            '2021-03-14',
            '2021-04-02',
            '2021-05-01',
            '2021-05-12',
            '2021-05-13',
            '2021-05-14',
            '2021-05-15',
            '2021-05-26',
            '2021-06-01',
            '2021-07-19',
            '2021-07-20',
            '2021-08-11',
            '2021-08-16',
            '2021-08-17',
            '2021-10-20',
            '2021-12-25',
            '2021-12-29',
            '2021-12-30',
            '2021-12-31',
        ], true);
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

    private static function formatNumber(int $value): string
    {
        return number_format($value, 0, '.', ',');
    }

    private static function formatPercent(float $value): string
    {
        return number_format(round($value), 0, '.', ',').'%';
    }
}
