<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class EmployeeTerminationReportService
{
    private const TITLE = 'Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $period = self::resolvePeriod($filters);
        $records = $this->parseXml($xmlContents, $sourceLabel, $period);
        $groupedRows = self::groupRows($records['rows']);
        $grandSummary = self::buildSummary($records['rows']);

        $headers = [
            'No',
            'Nama',
            'L/P',
            'Jabatan',
            'Status',
            'Level',
            'Tanggal Masuk',
            'Tanggal Keluar',
            'Masa Kerja',
            'Alasan Keluar',
        ];

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $period !== null
                ? 'Periode : '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y')
                : '',
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'headers' => $headers,
            'rows' => array_map(static fn (array $row): array => self::publicRow($row), $records['rows']),
            'grouped_rows' => $groupedRows,
            'grand_summary' => $grandSummary,
            'total_rows' => count($records['rows']),
        ];
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, printed_by: string}
     */
    private function parseXml(string $xmlContents, string $sourceLabel, ?array $period): array
    {
        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        $printedBy = '';

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'employees') {
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

            $terminationDate = self::parseDate((string) ($node->Date ?? ''));
            if ($terminationDate === null) {
                continue;
            }

            if ($period !== null && ! $terminationDate->betweenIncluded($period['start'], $period['end'])) {
                continue;
            }

            $joinDate = self::parseDate((string) ($node->{'Date_x0020_of_x0020_Join'} ?? ''));
            $nama = trim((string) ($node->{'Full_x0020_Name'} ?? ''));
            if ($nama === '') {
                continue;
            }

            $sex = trim((string) ($node->Sex ?? ''));
            $department = trim((string) ($node->{'Department_x0020_Name'} ?? ''));
            $jobTitle = trim((string) ($node->{'Job_x0020_Title'} ?? ''));
            $rawStatus = trim((string) ($node->{'Status_x0020_Type'} ?? ''));
            $statusType = self::formatStatus($rawStatus);
            $level = trim((string) ($node->Level ?? ''));
            $alasanKeluar = trim((string) ($node->{'Retirement_x0020_Reason'} ?? ''));

            $masaKerja = self::formatMasaKerja($joinDate, $terminationDate);
            $masaKerjaYears = self::computeMasaKerjaYears($joinDate, $terminationDate);

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $rows[] = [
                'Nama' => $nama,
                'L/P' => self::formatGender($sex),
                'Jabatan' => $jobTitle,
                'Status' => $statusType,
                'Level' => self::formatLevel($level),
                'Level Sort' => (int) $level,
                'Tanggal Masuk' => self::formatDate($joinDate),
                'Tanggal Masuk Sort' => $joinDate !== null ? $joinDate->format('Y-m-d') : '',
                'Tanggal Keluar' => self::formatDate($terminationDate),
                'Tanggal Keluar Sort' => $terminationDate->format('Y-m-d'),
                'Masa Kerja' => $masaKerja,
                'Masa Kerja Years' => $masaKerjaYears,
                'Alasan Keluar' => $alasanKeluar,
                'Departemen' => $department,
            ];
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('Data Karyawan Keluar tidak ditemukan di XML.');
        }

        usort($rows, static fn (array $left, array $right): int => [
            (string) ($left['Departemen'] ?? ''),
            (string) ($left['Tanggal Keluar Sort'] ?? ''),
            (string) ($left['Nama'] ?? ''),
        ] <=> [
            (string) ($right['Departemen'] ?? ''),
            (string) ($right['Tanggal Keluar Sort'] ?? ''),
            (string) ($right['Nama'] ?? ''),
        ]);

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{label: string, subtotal: int, rows: array<int, array<string, string>>, summary: array<string, mixed>}>
     */
    private static function groupRows(array $rows): array
    {
        $departmentRows = [];

        foreach ($rows as $row) {
            $departmentRows[(string) ($row['Departemen'] ?? '')][] = $row;
        }

        ksort($departmentRows, SORT_NATURAL | SORT_FLAG_CASE);

        $groupedRows = [];
        foreach ($departmentRows as $department => $rowsInDepartment) {
            $groupedRows[] = [
                'label' => 'Departemen : '.$department,
                'subtotal' => count($rowsInDepartment),
                'rows' => array_map(static fn (array $row): array => self::publicRow($row), $rowsInDepartment),
                'summary' => self::buildSummary($rowsInDepartment),
            ];
        }

        return $groupedRows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private static function buildSummary(array $rows): array
    {
        $genderCounts = ['L' => 0, 'P' => 0];
        $statusOrder = ['BR', 'KK', 'KT', 'ST'];
        $statusCounts = array_fill_keys($statusOrder, 0);
        $levelCounts = [];

        foreach ($rows as $row) {
            $gender = (string) ($row['L/P'] ?? '');
            if (isset($genderCounts[$gender])) {
                $genderCounts[$gender]++;
            }

            $status = (string) ($row['Status'] ?? '');
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }

            $levelNum = (int) ($row['Level Sort'] ?? 0);
            $levelLabel = 'Level '.$levelNum;
            $levelCounts[$levelLabel] = ($levelCounts[$levelLabel] ?? 0) + 1;
        }

        $total = count($rows);

        return [
            'subtotal' => $total,
            'gender' => [
                'L' => [
                    'label' => 'Laki-Laki',
                    'count' => $genderCounts['L'],
                    'percent' => self::percent($genderCounts['L'], $total),
                ],
                'P' => [
                    'label' => 'Perempuan',
                    'count' => $genderCounts['P'],
                    'percent' => self::percent($genderCounts['P'], $total),
                ],
            ],
            'status' => array_map(static fn (string $code) => [
                'label' => $code,
                'count' => $statusCounts[$code],
                'percent' => self::percent($statusCounts[$code], $total),
            ], $statusOrder),
            'level' => array_map(static fn (int $i) => [
                'label' => 'Level '.$i,
                'count' => $levelCounts['Level '.$i] ?? 0,
                'percent' => self::percent($levelCounts['Level '.$i] ?? 0, $total),
            ], range(1, 7)),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private static function publicRow(array $row): array
    {
        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'L/P' => (string) ($row['L/P'] ?? ''),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Status' => (string) ($row['Status'] ?? ''),
            'Level' => (string) ($row['Level'] ?? ''),
            'Tanggal Masuk' => (string) ($row['Tanggal Masuk'] ?? ''),
            'Tanggal Keluar' => (string) ($row['Tanggal Keluar'] ?? ''),
            'Masa Kerja' => (string) ($row['Masa Kerja'] ?? ''),
            'Alasan Keluar' => (string) ($row['Alasan Keluar'] ?? ''),
            'Departemen' => (string) ($row['Departemen'] ?? ''),
        ];
    }

    private static function formatGender(string $sex): string
    {
        return stripos($sex, 'Female') !== false ? 'P' : 'L';
    }

    private static function formatStatus(string $status): string
    {
        return match (strtolower($status)) {
            'contract' => 'KK',
            'permanent' => 'KT',
            'staff' => 'ST',
            'borongan' => 'BR',
            default => $status,
        };
    }

    private static function formatLevel(string $level): string
    {
        $level = trim($level);
        if ($level === '') {
            return '';
        }

        if (preg_match('/(\d+)/', $level, $matches) === 1) {
            return (string) ((int) $matches[1]);
        }

        return $level;
    }

    private static function formatDate(?Carbon $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->locale('id')->translatedFormat('d-M-y');
    }

    private static function formatMasaKerja(?Carbon $joinDate, ?Carbon $terminationDate): string
    {
        if ($joinDate === null || $terminationDate === null) {
            return '';
        }

        $diff = $joinDate->diff($terminationDate);

        $parts = [];
        if ($diff->y > 0) {
            $parts[] = $diff->y.' Thn';
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m.' Bln';
        }
        if ($diff->d > 0) {
            $parts[] = $diff->d.' Hr';
        }

        return $parts !== [] ? implode(' ', $parts) : '0 Hr';
    }

    private static function computeMasaKerjaYears(?Carbon $joinDate, ?Carbon $terminationDate): int
    {
        if ($joinDate === null || $terminationDate === null) {
            return 0;
        }

        return (int) floor($joinDate->diffInDays($terminationDate) / 365.25);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function resolvePrintedBy(\SimpleXMLElement $node): string
    {
        $candidateKeys = [
            'Nama_x0020_User',
            'User_x0020_Name',
            'Printed_x0020_By',
            'Created_x0020_By',
        ];

        foreach ($candidateKeys as $key) {
            $value = trim((string) ($node->$key ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start: Carbon, end: Carbon}|null
     */
    private static function resolvePeriod(array $filters): ?array
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

    private static function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
    }
}
