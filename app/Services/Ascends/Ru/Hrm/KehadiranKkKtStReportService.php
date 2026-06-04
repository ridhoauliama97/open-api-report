<?php

namespace App\Services\Ascends\Ru\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;
use Throwable;

class KehadiranKkKtStReportService
{
    private const TITLE = 'Laporan Kehadiran KK/KT/ST';

    /**
     * @var array<int, string>
     */
    private const INCLUDED_STATUSES = ['KK', 'KT', 'ST'];

    /**
     * @var array<int, string>
     */
    private const ATTENDANCE_DIVISIONS = ['PKB', 'VKD'];

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'kehadiran_kk_kt_st');

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
            'kehadiran_kk_kt_st',
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

        usort($rows, static fn (array $left, array $right): int => [
            (string) ($left['Divisi'] ?? ''),
            (string) ($left['Nama'] ?? ''),
        ] <=> [
            (string) ($right['Divisi'] ?? ''),
            (string) ($right['Nama'] ?? ''),
        ]);

        $groupedRows = self::groupRows();

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'headers' => ['No', 'Nama', 'Keterangan'],
            'rows' => array_map(static fn (array $row): array => self::publicRow($row), $rows),
            'grouped_rows' => $groupedRows,
            'follow_up_rows' => self::buildFollowUpRows(),
            'status_summary' => self::countStatuses($rows),
            'total_rows' => count($rows),
        ]);
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function shouldIncludeRow(array $row): bool
    {
        $employeeCode = trim((string) ($row['Kode Karyawan'] ?? ''));
        $status = strtoupper(trim((string) ($row['Status'] ?? '')));
        $division = trim((string) ($row['Divisi'] ?? ''));

        return strcasecmp(trim((string) ($row['Status Aktif'] ?? '')), 'Active') === 0
            && ! str_starts_with(strtoupper($employeeCode), 'SPECIAL')
            && in_array($status, self::INCLUDED_STATUSES, true)
            && $division !== '';
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function shapeRow(array $row): array
    {
        return [
            'Nama' => trim((string) ($row['Nama'] ?? '')),
            'Status' => strtoupper(trim((string) ($row['Status'] ?? ''))),
            'Divisi' => trim((string) ($row['Divisi'] ?? '')),
            'Keterangan' => '',
        ];
    }

    /**
     * @return array<int, array{label: string, division: string, member_total: int, rows: array<int, array<string, string>>, summary: array<string, mixed>}>
     */
    private static function groupRows(): array
    {
        return array_map(
            static fn (string $division): array => [
                'label' => 'Divisi : '.$division,
                'division' => $division,
                'member_total' => 0,
                'rows' => self::blankRows(6),
                'summary' => [
                    'subtotal' => 0,
                    'status' => array_fill_keys(self::INCLUDED_STATUSES, 0),
                ],
            ],
            self::ATTENDANCE_DIVISIONS
        );
    }

    /**
     * @return array<int, array{Nama: string, Divisi: string, Penanganan: string, Keterangan: string, Follow Up: string}>
     */
    private static function buildFollowUpRows(): array
    {
        return array_map(
            static fn (): array => [
                'Nama' => '',
                'Divisi' => '',
                'Penanganan' => '',
                'Keterangan' => '',
                'Follow Up' => '',
            ],
            range(1, 15)
        );
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function blankRows(int $count): array
    {
        return array_map(
            static fn (): array => [
                'Nama' => '',
                'Status' => '',
                'Divisi' => '',
                'Keterangan' => '',
            ],
            range(1, $count)
        );
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, int>
     */
    private static function countStatuses(array $rows): array
    {
        $summary = array_fill_keys(self::INCLUDED_STATUSES, 0);

        foreach ($rows as $row) {
            $status = strtoupper(trim((string) ($row['Status'] ?? '')));
            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function publicRow(array $row): array
    {
        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'Status' => (string) ($row['Status'] ?? ''),
            'Divisi' => (string) ($row['Divisi'] ?? ''),
            'Keterangan' => (string) ($row['Keterangan'] ?? ''),
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

    public static function formatReportDate(mixed $value): string
    {
        try {
            return Carbon::parse($value ?? now())->locale('id')->translatedFormat('d/m/Y');
        } catch (Throwable) {
            return Carbon::now()->locale('id')->translatedFormat('d/m/Y');
        }
    }
}
