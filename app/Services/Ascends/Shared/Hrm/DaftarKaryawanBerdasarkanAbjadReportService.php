<?php

namespace App\Services\Ascends\Shared\Hrm;

use App\Services\XmlDataSourceService;

class DaftarKaryawanBerdasarkanAbjadReportService
{
    private const TITLE = "Laporan Daftar Karyawan (RU)\nBerdasarkan Abjad";

    /**
     * @var array<int, array{label: string, letters: array<int, string>}>
     */
    private const GROUPS = [
        ['label' => 'A - D', 'letters' => ['A', 'B', 'C', 'D']],
        ['label' => 'E - H', 'letters' => ['E', 'F', 'G', 'H']],
        ['label' => 'I - L', 'letters' => ['I', 'J', 'K', 'L']],
        ['label' => 'M - P', 'letters' => ['M', 'N', 'O', 'P']],
        ['label' => 'Q - T', 'letters' => ['Q', 'R', 'S', 'T']],
        ['label' => 'U - Z', 'letters' => ['U', 'V', 'W', 'X', 'Y', 'Z']],
    ];

    /**
     * @var array<int, string>
     */
    private const INCLUDED_HK_CODES = [
        'BR',
        'KK',
        'KT',
    ];

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'daftar_karyawan_berdasarkan_abjad');

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
            'daftar_karyawan_berdasarkan_abjad',
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
            (string) ($left['Nama'] ?? ''),
            (string) ($left['No ID'] ?? ''),
        ] <=> [
            (string) ($right['Nama'] ?? ''),
            (string) ($right['No ID'] ?? ''),
        ]);

        $groupedRows = self::groupRows($rows);

        $headers = [
            'No',
            'Nama',
            'No ID',
            'Posisi',
            'Paraf',
        ];

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'headers' => $headers,
            'rows' => $rows,
            'grouped_rows' => $groupedRows,
            'total_rows' => count($rows),
        ]);
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function shouldIncludeRow(array $row): bool
    {
        return strcasecmp(trim((string) ($row['Status Aktif'] ?? '')), 'Active') === 0
            && in_array(strtoupper(trim((string) ($row['HK Kode'] ?? ''))), self::INCLUDED_HK_CODES, true);
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function shapeRow(array $row): array
    {
        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'No ID' => (string) ($row['No ID'] ?? ''),
            'Posisi' => (string) ($row['Posisi'] ?? ''),
            'Paraf' => '',
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array{label: string, rows: array<int, array<string, string>>}>
     */
    private static function groupRows(array $rows): array
    {
        $groupedRows = [];

        foreach (self::GROUPS as $group) {
            $groupRows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => in_array(self::firstLetter((string) ($row['Nama'] ?? '')), $group['letters'], true)
            ));

            if ($groupRows === []) {
                continue;
            }

            $groupedRows[] = [
                'label' => $group['label'],
                'rows' => $groupRows,
            ];
        }

        return $groupedRows;
    }

    private static function firstLetter(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return strtoupper(substr($value, 0, 1));
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
