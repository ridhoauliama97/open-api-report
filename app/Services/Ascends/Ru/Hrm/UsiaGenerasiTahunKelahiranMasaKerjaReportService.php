<?php

namespace App\Services\Ascends\Ru\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;
use Throwable;

class UsiaGenerasiTahunKelahiranMasaKerjaReportService
{
    private const TITLE = 'Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja';

    /**
     * @var array<int, array{key: string, label: string, min: int, max: int}>
     */
    private const GENERATIONS = [
        ['key' => 'baby_boomer', 'label' => 'Generasi Baby Boomer', 'min' => 1946, 'max' => 1964],
        ['key' => 'x', 'label' => 'Generasi X', 'min' => 1965, 'max' => 1980],
        ['key' => 'milenial', 'label' => 'Generasi Milenial', 'min' => 1981, 'max' => 1996],
        ['key' => 'z', 'label' => 'Generasi Z', 'min' => 1997, 'max' => 2012],
    ];

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'usia_generasi_tahun_kelahiran_masa_kerja');

        return $this->shapeReportData($reportData, 'storage/app/xml_sources/RU/hrm/AnlReports.HRM.EmployeeList.xml');
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload'): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReportFromXmlContents(
            'RU',
            'hrm',
            'usia_generasi_tahun_kelahiran_masa_kerja',
            $xmlContents,
            $sourceLabel
        );

        return $this->shapeReportData($reportData, $sourceLabel);
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array<string, mixed>
     */
    private function shapeReportData(array $reportData, string $sourceLabel): array
    {
        $rawRows = array_values(array_filter(
            $reportData['rows'] ?? [],
            static fn (array $row): bool => self::shouldIncludeRow($row)
        ));
        $printedBy = self::resolvePrintedBy($rawRows);
        $rows = array_map(
            static fn (array $row): array => self::shapeRow($row),
            $rawRows
        );
        $rows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => ($row['Generasi Key'] ?? '') !== ''
        ));

        usort($rows, static fn (array $left, array $right): int => [
            self::generationSortValue((string) ($left['Generasi Key'] ?? '')),
            -((int) ($left['Usia Sort'] ?? 0)),
            (string) ($left['Nama'] ?? ''),
        ] <=> [
            self::generationSortValue((string) ($right['Generasi Key'] ?? '')),
            -((int) ($right['Usia Sort'] ?? 0)),
            (string) ($right['Nama'] ?? ''),
        ]);

        $headers = [
            'No',
            'Nama',
            'Jabatan',
            'Departemen',
            'Usia',
            'Masa Kerja',
        ];

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'headers' => $headers,
            'rows' => array_map(static fn (array $row): array => self::publicRow($row), $rows),
            'grouped_rows' => self::groupRows($rows),
            'generation_summary' => self::buildGenerationSummary($rows),
            'total_rows' => count($rows),
        ]);
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function shouldIncludeRow(array $row): bool
    {
        $employeeCode = trim((string) ($row['Kode Karyawan'] ?? ''));

        return strcasecmp(trim((string) ($row['Status Aktif'] ?? '')), 'Active') === 0
            && ! str_starts_with(strtoupper($employeeCode), 'SPECIAL');
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private static function shapeRow(array $row): array
    {
        $birthYear = self::birthYear($row);
        $generation = self::generationForBirthYear($birthYear);
        $years = self::numericValue((string) ($row['Masa Kerja Tahun'] ?? ''));
        $months = self::numericValue((string) ($row['Masa Kerja Bulan'] ?? ''));

        if (($years === 0 && $months === 0) && trim((string) ($row['Tanggal Masuk'] ?? '')) !== '') {
            $joinDate = self::parseDate((string) ($row['Tanggal Masuk'] ?? ''));
            if ($joinDate !== null) {
                $period = $joinDate->diff(Carbon::now());
                $years = $period->y;
                $months = $period->m;
            }
        }

        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Departemen' => (string) ($row['Departemen'] ?? ''),
            'Usia' => self::formatAge((string) ($row['Usia'] ?? '')),
            'Usia Sort' => self::numericValue((string) ($row['Usia'] ?? '')),
            'Masa Kerja' => self::formatWorkingPeriod($years, $months),
            'Generasi Key' => $generation['key'] ?? '',
            'Generasi Label' => $generation['label'] ?? '',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{label: string, rows: array<int, array<string, string>>, subtotal: int, percent: string}>
     */
    private static function groupRows(array $rows): array
    {
        $total = count($rows);
        $groupedRows = [];

        foreach (self::GENERATIONS as $generation) {
            $generationRows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => ($row['Generasi Key'] ?? '') === $generation['key']
            ));

            if ($generationRows === []) {
                continue;
            }

            $subtotal = count($generationRows);
            $groupedRows[] = [
                'label' => $generation['label'],
                'rows' => array_map(static fn (array $row): array => self::publicRow($row), $generationRows),
                'subtotal' => $subtotal,
                'percent' => self::percentText($subtotal, $total),
            ];
        }

        return $groupedRows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{label: string, count: int, percent: string}>
     */
    private static function buildGenerationSummary(array $rows): array
    {
        $total = count($rows);
        $summary = [];

        foreach (self::GENERATIONS as $generation) {
            $count = count(array_filter(
                $rows,
                static fn (array $row): bool => ($row['Generasi Key'] ?? '') === $generation['key']
            ));

            $summary[] = [
                'label' => $generation['label'],
                'count' => $count,
                'percent' => self::percentText($count, $total),
            ];
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private static function publicRow(array $row): array
    {
        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Departemen' => (string) ($row['Departemen'] ?? ''),
            'Usia' => (string) ($row['Usia'] ?? ''),
            'Masa Kerja' => (string) ($row['Masa Kerja'] ?? ''),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private static function resolvePrintedBy(array $rows): string
    {
        $candidateKeys = [
            'Nama User',
            'User Name',
            'Printed By',
            'Created By',
        ];

        foreach ($rows as $row) {
            foreach ($candidateKeys as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function birthYear(array $row): int
    {
        $year = self::numericValue((string) ($row['Tahun Lahir'] ?? ''));
        if ($year > 0) {
            return $year;
        }

        $birthDate = self::parseDate((string) ($row['Tanggal Lahir'] ?? ''));
        if ($birthDate !== null) {
            return (int) $birthDate->format('Y');
        }

        return 0;
    }

    /**
     * @return array{key: string, label: string}|null
     */
    private static function generationForBirthYear(int $birthYear): ?array
    {
        foreach (self::GENERATIONS as $generation) {
            if ($birthYear >= $generation['min'] && $birthYear <= $generation['max']) {
                return [
                    'key' => $generation['key'],
                    'label' => $generation['label'],
                ];
            }
        }

        return null;
    }

    private static function generationSortValue(string $key): int
    {
        foreach (self::GENERATIONS as $index => $generation) {
            if ($generation['key'] === $key) {
                return $index;
            }
        }

        return 999;
    }

    private static function formatAge(string $age): string
    {
        $age = trim($age);

        return is_numeric($age) ? (string) ((int) $age) : $age;
    }

    private static function parseDate(string $date): ?Carbon
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (Throwable) {
            return null;
        }
    }

    private static function numericValue(string $value): int
    {
        return is_numeric(trim($value)) ? (int) trim($value) : 0;
    }

    private static function percentText(int $count, int $total): string
    {
        return $total > 0 ? number_format(($count / $total) * 100, 1, '.', '') . '%' : '0.0%';
    }

    private static function formatWorkingPeriod(int $years, int $months): string
    {
        return $years . ' Thn ' . $months . ' bln';
    }
}
