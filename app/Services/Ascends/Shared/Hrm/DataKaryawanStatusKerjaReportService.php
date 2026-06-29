<?php

namespace App\Services\Ascends\Shared\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;
use Throwable;

class DataKaryawanStatusKerjaReportService
{
    private const TITLE = "Laporan Data Karyawan (RU)\nStaff, Karyawan Tetap & Karyawan Kontrak\nBerdasarkan Status Kerja";

    /**
     * @var array<string, int>
     */
    private const HK_ORDER = [
        'BORONGAN' => 1,
        'KARYAWAN KONTRAK' => 2,
        'KARYAWAN TETAP' => 3,
        'STAFF' => 4,
    ];

    /**
     * @var array<int, string>
     */
    private const INCLUDED_HK_CODES = [
        'KK',
        'KT',
        'ST',
        'BR',
    ];

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'data_karyawan_status_kerja');

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
            'data_karyawan_status_kerja',
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

        usort($rows, static function (array $left, array $right): int {
            return [
                self::hkSortOrder((string) ($left['HK'] ?? '')),
                (string) ($left['Nama'] ?? ''),
                (string) ($left['NIK'] ?? ''),
            ] <=> [
                self::hkSortOrder((string) ($right['HK'] ?? '')),
                (string) ($right['Nama'] ?? ''),
                (string) ($right['NIK'] ?? ''),
            ];
        });

        $headers = [
            'No',
            'NIK',
            'Nama',
            'Tempat',
            'Tgl Lahir',
            'Umur',
            'Pendidikan',
            'Jabatan',
            'HK',
        ];

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($rows),
        ]);
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function shouldIncludeRow(array $row): bool
    {
        return strcasecmp(trim((string) ($row['Status Aktif'] ?? '')), 'Active') === 0
            && in_array(self::resolveHkCode($row), self::INCLUDED_HK_CODES, true)
            && (int) trim((string) ($row['Level'] ?? '0')) >= 2;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function shapeRow(array $row): array
    {
        return [
            'NIK' => (string) ($row['NIK'] ?? ''),
            'Nama' => (string) ($row['Nama'] ?? ''),
            'Tempat' => (string) ($row['Tempat'] ?? ''),
            'Tgl Lahir' => self::formatDate((string) ($row['Tgl Lahir'] ?? '')),
            'Umur' => (string) ($row['Umur'] ?? ''),
            'Pendidikan' => self::resolvePendidikan($row),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'HK' => self::resolveHk($row),
        ];
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function resolvePendidikan(array $row): string
    {
        $schoolName = trim((string) ($row['Pendidikan'] ?? ''));

        return $schoolName !== '' ? $schoolName : trim((string) ($row['Jenjang Pendidikan'] ?? ''));
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function resolveHk(array $row): string
    {
        $hkCode = self::resolveHkCode($row);
        if ($hkCode !== '') {
            return match ($hkCode) {
                'BR' => 'BORONGAN',
                'KK' => 'KARYAWAN KONTRAK',
                'KT' => 'KARYAWAN TETAP',
                'ST' => 'STAFF',
                default => $hkCode,
            };
        }

        $hkName = strtoupper(trim((string) ($row['HK'] ?? '')));
        if ($hkName !== '') {
            return $hkName;
        }

        return '';
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function resolveHkCode(array $row): string
    {
        $hkCode = strtoupper(trim((string) ($row['HK Kode'] ?? '')));
        if ($hkCode !== '') {
            return $hkCode;
        }

        return match (strtoupper(trim((string) ($row['HK'] ?? '')))) {
            'BORONGAN' => 'BR',
            'KARYAWAN KONTRAK' => 'KK',
            'KARYAWAN TETAP' => 'KT',
            'STAFF' => 'ST',
            default => '',
        };
    }

    private static function hkSortOrder(string $hk): int
    {
        return self::HK_ORDER[strtoupper(trim($hk))] ?? 99;
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

    private static function formatDate(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        try {
            return Carbon::parse($date)->locale('id')->translatedFormat('d-M-y');
        } catch (Throwable) {
            return $date;
        }
    }
}
