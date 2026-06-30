<?php

namespace App\Services\Ascends\Shared\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;

class PerbandinganJumlahKaryawanTahunanPerBulanReportService
{
    private const TITLE = 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan';

    private const DEFAULT_MPP = 80;

    /**
     * @var array<int, string>
     */
    private const MONTH_LABELS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'perbandingan_jumlah_karyawan_tahunan_per_bulan');

        return $this->shapeReportData($reportData, 'storage/app/xml_sources/RU/hrm/AnlReports.HRM.EmployeeList.xml');
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReportFromXmlContents(
            'RU',
            'hrm',
            'perbandingan_jumlah_karyawan_tahunan_per_bulan',
            $xmlContents,
            $sourceLabel
        );

        return $this->shapeReportData($reportData, $sourceLabel, $filters);
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array<string, mixed>
     */
    private function shapeReportData(array $reportData, string $sourceLabel, array $filters = []): array
    {
        $rawRows = array_values(array_filter(
            $reportData['rows'] ?? [],
            static fn (array $row): bool => self::shouldIncludeRow($row)
        ));

        $period = self::resolveReportPeriod($filters);
        $years = array_values(array_unique(array_filter([
            $period['year'] - 1,
            $period['year'],
        ], static fn (int $year): bool => $year > 0)));

        $yearlyRows = [];
        foreach ($years as $year) {
            $monthLimit = $year === $period['year'] ? $period['month'] : 12;
            $rows = self::buildMonthlyRows($rawRows, $year, $monthLimit, self::DEFAULT_MPP);
            $yearlyRows[] = [
                'year' => $year,
                'rows' => $rows,
                'summary' => [
                    'joined' => self::seriesSummary(array_column($rows, 'Karyawan Masuk')),
                    'terminated' => self::seriesSummary(array_column($rows, 'Karyawan Keluar')),
                    'total' => self::seriesSummary(array_column($rows, 'Total Karyawan'), true),
                ],
            ];
        }

        $headers = [
            'Bulan',
            'MPP Karyawan',
            'Karyawan Masuk',
            '%',
            'Karyawan Keluar',
            '%',
            'Total Karyawan',
            '%',
            'GAP',
            '%',
            'Remark',
        ];

        $now = Carbon::now()->locale('id');

        $perDateFilter = $filters['PerDate'] ?? '';
        $perDateValue = $perDateFilter !== ''
            ? Carbon::parse($perDateFilter)->toDateString()
            : $period['date']->toDateString();

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => self::resolvePrintedBy($rawRows),
            'printed_at' => $now->translatedFormat('d F Y H:i'),
            'per_date' => $perDateValue,
            'headers' => $headers,
            'yearly_rows' => $yearlyRows,
            'rows' => array_merge(...array_map(static fn (array $year): array => $year['rows'], $yearlyRows)),
            'total_rows' => count($rawRows),
            'mpp' => self::DEFAULT_MPP,
        ]);
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function shouldIncludeRow(array $row): bool
    {
        $employeeCode = trim((string) ($row['Kode Karyawan'] ?? ''));

        return ! str_starts_with(strtoupper($employeeCode), 'SPECIAL');
    }

    /**
     * @return array{year: int, month: int, date: Carbon}
     */
    private static function resolveReportPeriod(array $filters = []): array
    {
        $perDateValue = $filters['PerDate'] ?? '';

        $ref = $perDateValue !== ''
            ? Carbon::parse($perDateValue)->locale('id')
            : Carbon::now()->locale('id');

        return [
            'year' => (int) $ref->year,
            'month' => (int) $ref->month,
            'date' => Carbon::create((int) $ref->year, (int) $ref->month, 1, 0, 0, 0)->locale('id'),
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function buildMonthlyRows(array $rows, int $year, int $monthLimit, int $mpp): array
    {
        $monthlyRows = [];
        $previousTotal = null;

        for ($month = 1; $month <= $monthLimit; $month++) {
            $total = self::totalAtMonthEnd($rows, $year, $month);
            $joined = self::countByYearMonth($rows, 'Tahun Masuk', 'Bulan Masuk', $year, $month);
            $terminated = self::countByYearMonth($rows, 'Tahun Keluar', 'Bulan Keluar', $year, $month);
            $gap = $total - $mpp;
            $employeeChangePercent = $previousTotal === null || $previousTotal === 0
                ? '0.0%'
                : self::percentText($total - $previousTotal, $previousTotal);

            $monthlyRows[] = [
                'Bulan' => self::MONTH_LABELS[$month] ?? (string) $month,
                'MPP' => $mpp,
                'Karyawan Masuk' => $joined,
                '% Masuk' => self::percentText($joined, $mpp),
                'Karyawan Keluar' => $terminated,
                '% Keluar' => self::percentText($terminated, $mpp),
                'Total Karyawan' => $total,
                '% Total' => $employeeChangePercent,
                'GAP' => $gap,
                '% GAP' => self::percentText($gap, $mpp),
                'Remark' => '',
            ];

            $previousTotal = $total;
        }

        return $monthlyRows;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private static function totalAtMonthEnd(array $rows, int $year, int $month): int
    {
        return count(array_filter($rows, static function (array $row) use ($year, $month): bool {
            $joinYear = self::numericValue((string) ($row['Tahun Masuk'] ?? ''));
            $joinMonth = self::numericValue((string) ($row['Bulan Masuk'] ?? ''));
            $terminationYear = self::numericValue((string) ($row['Tahun Keluar'] ?? ''));
            $terminationMonth = self::numericValue((string) ($row['Bulan Keluar'] ?? ''));

            return self::yearMonthLessThanOrEqual($joinYear, $joinMonth, $year, $month)
                && self::yearMonthGreaterThan($terminationYear, $terminationMonth, $year, $month);
        }));
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private static function countByYearMonth(array $rows, string $yearKey, string $monthKey, int $year, int $month): int
    {
        return count(array_filter($rows, static fn (array $row): bool => self::numericValue((string) ($row[$yearKey] ?? '')) === $year
            && self::numericValue((string) ($row[$monthKey] ?? '')) === $month));
    }

    private static function yearMonthLessThanOrEqual(int $leftYear, int $leftMonth, int $rightYear, int $rightMonth): bool
    {
        return $leftYear > 0
            && $leftMonth > 0
            && ($leftYear < $rightYear || ($leftYear === $rightYear && $leftMonth <= $rightMonth));
    }

    private static function yearMonthGreaterThan(int $leftYear, int $leftMonth, int $rightYear, int $rightMonth): bool
    {
        return $leftYear <= 0
            || $leftMonth <= 0
            || $leftYear > $rightYear
            || ($leftYear === $rightYear && $leftMonth > $rightMonth);
    }

    /**
     * @param  array<int, int|string>  $values
     * @return array{min: int, min_percent: string, max: int, max_percent: string, avg: int, avg_percent: string}
     */
    private static function seriesSummary(array $values, bool $percentAgainstAverage = false): array
    {
        $numbers = array_map(static fn (mixed $value): int => (int) $value, $values);
        if ($numbers === []) {
            return [
                'min' => 0,
                'min_percent' => '0.0%',
                'max' => 0,
                'max_percent' => '0.0%',
                'avg' => 0,
                'avg_percent' => '0.0%',
            ];
        }

        $min = min($numbers);
        $max = max($numbers);
        $avg = (int) round(array_sum($numbers) / count($numbers));
        $denominator = $percentAgainstAverage ? $avg : array_sum($numbers);

        return [
            'min' => $min,
            'min_percent' => self::percentText($min, $denominator),
            'max' => $max,
            'max_percent' => self::percentText($max, $denominator),
            'avg' => $avg,
            'avg_percent' => self::percentText($avg, $denominator),
        ];
    }

    private static function percentText(int $value, int $total): string
    {
        if ($total === 0) {
            return '0.0%';
        }

        return number_format(($value / $total) * 100, 1, '.', '').'%';
    }

    private static function numericValue(string $value): int
    {
        $normalized = preg_replace('/[^0-9-]/', '', $value) ?? '';

        return (int) $normalized;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private static function resolvePrintedBy(array $rows): string
    {
        foreach (['Nama User', 'User Name', 'Printed By', 'Created By'] as $field) {
            foreach ($rows as $row) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }
}
