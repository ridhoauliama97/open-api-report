<?php

namespace App\Services\Ascends\Shared\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;
use Throwable;

class ListKaryawanHabisKontrakReportService
{
    private const TITLE = 'Laporan List Karyawan Habis Kontrak';

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'list_karyawan_habis_kontrak');

        return $this->shapeReportData($reportData, 'storage/app/xml_sources/RU/hrm/AnlReports.HRM.EmployeeList.xml');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReportFromXmlContents(
            'RU',
            'hrm',
            'list_karyawan_habis_kontrak',
            $xmlContents,
            $sourceLabel
        );

        return $this->shapeReportData($reportData, $sourceLabel, $filters);
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function shapeReportData(array $reportData, string $sourceLabel, array $filters = []): array
    {
        $period = self::resolveExpiryPeriod($filters);
        $rawRows = array_values(array_filter(
            $reportData['rows'] ?? [],
            static fn (array $row): bool => self::shouldIncludeRow($row, $period)
        ));

        $printedBy = self::resolvePrintedBy($rawRows);
        $rows = array_map(
            static fn (array $row): array => self::shapeRow($row),
            $rawRows
        );

        usort($rows, static fn (array $left, array $right): int => [
            (string) ($left['Expiry Date Sort'] ?? ''),
            (string) ($left['Full Name'] ?? ''),
        ] <=> [
            (string) ($right['Expiry Date Sort'] ?? ''),
            (string) ($right['Full Name'] ?? ''),
        ]);

        $headers = ['Code', 'Full Name', 'Job Title', 'Department', 'Join Date', 'Expiry Date', 'Active'];

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'headers' => $headers,
            'rows' => array_map(static fn (array $row): array => self::publicRow($row), $rows),
            'total_rows' => count($rows),
            'period' => [
                'start_date' => $period['start']->format('Y-m-d'),
                'end_date' => $period['end']->format('Y-m-d'),
                'label' => $period['start']->locale('id')->translatedFormat('F Y'),
            ],
        ]);
    }

    /**
     * @param  array<string, string>  $row
     * @param  array{start: Carbon, end: Carbon}  $period
     */
    private static function shouldIncludeRow(array $row, array $period): bool
    {
        $employeeCode = trim((string) ($row['Code'] ?? ''));
        $status = strtoupper(trim((string) ($row['Status'] ?? '')));
        $expiryDate = trim((string) ($row['Expiry Date'] ?? ''));
        $expiry = self::parseDate($expiryDate);

        return strcasecmp(trim((string) ($row['Active'] ?? '')), 'Active') === 0
            && ! str_starts_with(strtoupper($employeeCode), 'SPECIAL')
            && $status === 'KK'
            && $expiry !== null
            && $expiry->betweenIncluded($period['start'], $period['end']);
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function shapeRow(array $row): array
    {
        $joinDate = trim((string) ($row['Join Date'] ?? ''));
        $expiryDate = trim((string) ($row['Expiry Date'] ?? ''));

        return [
            'Code' => trim((string) ($row['Code'] ?? '')),
            'Full Name' => trim((string) ($row['Full Name'] ?? '')),
            'Job Title' => trim((string) ($row['Job Title'] ?? '')),
            'Department' => trim((string) ($row['Department'] ?? '')),
            'Join Date' => self::formatDate($joinDate),
            'Join Date Sort' => self::dateSortKey($joinDate),
            'Expiry Date' => self::formatDate($expiryDate),
            'Expiry Date Sort' => self::dateSortKey($expiryDate),
            'Active' => trim((string) ($row['Active'] ?? '')),
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function publicRow(array $row): array
    {
        return [
            'Code' => (string) ($row['Code'] ?? ''),
            'Full Name' => (string) ($row['Full Name'] ?? ''),
            'Job Title' => (string) ($row['Job Title'] ?? ''),
            'Department' => (string) ($row['Department'] ?? ''),
            'Join Date' => (string) ($row['Join Date'] ?? ''),
            'Expiry Date' => (string) ($row['Expiry Date'] ?? ''),
            'Active' => (string) ($row['Active'] ?? ''),
        ];
    }

    private static function formatDate(string $value): string
    {
        $date = self::parseDate($value);

        return $date?->locale('id')->translatedFormat('d-M-y') ?? $value;
    }

    private static function dateSortKey(string $value): string
    {
        $date = self::parseDate($value);

        return $date?->format('Y-m-d') ?? $value;
    }

    private static function parseDate(string $value): ?Carbon
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

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start: Carbon, end: Carbon}
     */
    private static function resolveExpiryPeriod(array $filters): array
    {
        $startDate = trim((string) ($filters['start_date'] ?? $filters['TglAwal'] ?? ''));
        $endDate = trim((string) ($filters['end_date'] ?? $filters['TglAkhir'] ?? ''));

        if ($startDate !== '' || $endDate !== '') {
            $start = self::parseDate($startDate) ?? self::parseDate($endDate) ?? Carbon::now()->startOfMonth();
            $end = self::parseDate($endDate) ?? self::parseDate($startDate) ?? $start->copy()->endOfMonth();

            if ($end->lessThan($start)) {
                [$start, $end] = [$end, $start];
            }

            return ['start' => $start->startOfDay(), 'end' => $end->endOfDay()];
        }

        $month = (int) ($filters['month'] ?? $filters['bulan'] ?? 0);
        $year = (int) ($filters['year'] ?? $filters['tahun'] ?? 0);
        if ($month >= 1 && $month <= 12) {
            $base = Carbon::create($year > 0 ? $year : (int) Carbon::now()->format('Y'), $month, 1)->startOfMonth();

            return ['start' => $base->copy()->startOfDay(), 'end' => $base->copy()->endOfMonth()->endOfDay()];
        }

        $reportDate = trim((string) ($filters['report_date'] ?? $filters['tanggal'] ?? ''));
        $base = self::parseDate($reportDate) ?? Carbon::now()->startOfMonth();

        return ['start' => $base->copy()->startOfMonth()->startOfDay(), 'end' => $base->copy()->endOfMonth()->endOfDay()];
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
}
