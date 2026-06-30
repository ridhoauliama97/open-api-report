<?php

namespace App\Services\Ascends\Shared\Hrm\CustomReports;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class KaryawanMasukReportService
{
    private const GENDER_LABELS = [
        'L' => 'Laki - Laki',
        'P' => 'Perempuan',
    ];

    private const STATUS_LABELS = [
        'BR' => 'BR',
        'KK' => 'KK',
        'KT' => 'KT',
        'ST' => 'ST',
    ];

    private const LEVEL_LABELS = [
        'Level 1' => 'Level 1',
        'Level 2' => 'Level 2',
        'Level 3' => 'Level 3',
        'Level 4' => 'Level 4',
        'Level 5' => 'Level 5',
        'Level 6' => 'Level 6',
        'Level 7' => 'Level 7',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data karyawan tidak ditemukan pada XML.');
        }

        $filteredRows = $this->filterByDateRange($rows, $filters);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data karyawan dalam rentang tanggal yang dipilih.');
        }

        $shapedRows = array_map(fn (array $row): array => $this->shapeRow($row), $filteredRows);

        usort($shapedRows, fn (array $left, array $right): int => [
            (string) ($left['Departemen'] ?? ''),
            (string) ($left['Tanggal Masuk Sort'] ?? ''),
            (string) ($left['Nama'] ?? ''),
        ] <=> [
            (string) ($right['Departemen'] ?? ''),
            (string) ($right['Tanggal Masuk Sort'] ?? ''),
            (string) ($right['Nama'] ?? ''),
        ]);

        $groupedRows = $this->groupRows($shapedRows);
        $grandSummary = $this->buildSummary($shapedRows);

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');

        return [
            'title' => 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk',
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'printed_by' => trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? '')),
            'company' => trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? '')),
            'headerCompany' => trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? '')),
            'headerTitle' => 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk',
            'report_date' => $this->resolveReportDate($filters, $filteredRows),
            'start_date' => $startDate?->locale('id')->translatedFormat('d-M-y') ?? '',
            'end_date' => $endDate?->locale('id')->translatedFormat('d-M-y') ?? '',
            'headers' => ['No', 'Nama', 'L/P', 'Jabatan', 'Status', 'Level', 'Tanggal Masuk', 'Hasil'],
            'rows' => array_map(fn (array $row): array => $this->publicRow($row), $shapedRows),
            'grouped_rows' => $groupedRows,
            'grand_summary' => $grandSummary,
            'total_rows' => count($shapedRows),
        ];
    }

    private function parseRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML wajib dikirim.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA)) {
            throw new RuntimeException("File XML tidak valid ({$sourceLabel}).");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'table') {
                continue;
            }

            $nodeXml = $reader->readOuterXml();
            if (! is_string($nodeXml) || trim($nodeXml) === '') {
                continue;
            }

            $node = simplexml_load_string($nodeXml);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $row[$key] = trim((string) $value);
            }

            $rows[] = $row;
        }

        $reader->close();

        return $rows;
    }

    private function filterByDateRange(array $rows, array $filters): array
    {
        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');

        if ($startDate === null && $endDate === null) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->isWithinDateRange($row, $startDate, $endDate)
        ));
    }

    private function resolveDateFilter(array $filters, string $suffix): ?Carbon
    {
        $value = trim((string) (
            $filters[$suffix]
            ?? $filters['DateRange.' . $suffix]
            ?? $filters['DateRange_' . $suffix]
            ?? $filters['date_' . strtolower($suffix)]
            ?? $filters[strtolower($suffix)]
            ?? ''
        ));

        if ($value === '') {
            return null;
        }

        return $this->parseDate($value);
    }

    private function isWithinDateRange(array $row, ?Carbon $startDate, ?Carbon $endDate): bool
    {
        $joinDate = $this->parseDate((string) ($row['JoinDate'] ?? ''));
        if ($joinDate === null) {
            return false;
        }

        if ($startDate !== null && $joinDate->lessThan($startDate->startOfDay())) {
            return false;
        }

        if ($endDate !== null && $joinDate->greaterThan($endDate->endOfDay())) {
            return false;
        }

        return true;
    }

    private function resolveReportDate(array $filters, array $rows): string
    {
        $endDate = $this->resolveDateFilter($filters, 'EndDate');
        if ($endDate !== null) {
            return $endDate->locale('id')->translatedFormat('d-M-y');
        }

        $dates = array_filter(array_map(
            fn (array $row): ?Carbon => $this->parseDate((string) ($row['JoinDate'] ?? '')),
            $rows
        ));
        rsort($dates);

        $latest = $dates[0] ?? null;
        if ($latest !== null) {
            return $latest->locale('id')->translatedFormat('d-M-y');
        }

        return Carbon::now()->locale('id')->translatedFormat('d-M-y');
    }

    private function shapeRow(array $row): array
    {
        $joinDate = (string) ($row['JoinDate'] ?? '');

        return [
            'Nama' => (string) ($row['FullName'] ?? ''),
            'L/P' => $this->formatGender((string) ($row['Sex'] ?? '')),
            'Jabatan' => (string) ($row['JobTitle'] ?? ''),
            'Status' => strtoupper(trim((string) ($row['DailyWorkerTypeCode'] ?? ''))),
            'Level' => $this->formatLevel((string) ($row['LevelName'] ?? '')),
            'Level Summary' => $this->formatLevel((string) ($row['LevelName'] ?? '')),
            'Tanggal Masuk' => $this->formatDate($joinDate),
            'Tanggal Masuk Sort' => $this->dateSortKey($joinDate),
            'Hasil' => (string) ($row['Hasil'] ?? ''),
            'Departemen' => trim((string) ($row['DepartmentName'] ?? '')),
        ];
    }

    private function groupRows(array $rows): array
    {
        $departmentRows = [];
        foreach ($rows as $row) {
            $departmentRows[(string) ($row['Departemen'] ?? '')][] = $row;
        }
        ksort($departmentRows, SORT_NATURAL | SORT_FLAG_CASE);

        $groupedRows = [];
        foreach ($departmentRows as $department => $rowsInDepartment) {
            $groupedRows[] = [
                'label' => 'Departemen : ' . $department,
                'subtotal' => count($rowsInDepartment),
                'rows' => array_map(fn (array $row): array => $this->publicRow($row), $rowsInDepartment),
                'summary' => $this->buildSummary($rowsInDepartment),
            ];
        }

        return $groupedRows;
    }

    private function buildSummary(array $rows): array
    {
        return [
            'subtotal' => count($rows),
            'gender' => $this->countWithPercent($rows, 'L/P', self::GENDER_LABELS),
            'status' => $this->countWithPercent($rows, 'Status', self::STATUS_LABELS),
            'level' => $this->countWithPercent($rows, 'Level Summary', self::LEVEL_LABELS),
        ];
    }

    private function countWithPercent(array $rows, string $field, array $defaultLabels): array
    {
        $counts = array_fill_keys(array_keys($defaultLabels), 0);
        foreach ($rows as $row) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value === '') {
                continue;
            }
            if (! array_key_exists($value, $counts)) {
                $counts[$value] = 0;
                $defaultLabels[$value] = $value;
            }
            $counts[$value]++;
        }
        $summary = [];
        foreach ($counts as $value => $count) {
            $summary[$value] = [
                'label' => $defaultLabels[$value] ?? $value,
                'count' => $count,
                'percent' => $this->percent($count, count($rows)),
            ];
        }

        return $summary;
    }

    private function publicRow(array $row): array
    {
        return [
            'Nama' => (string) ($row['Nama'] ?? ''),
            'L/P' => (string) ($row['L/P'] ?? ''),
            'Jabatan' => (string) ($row['Jabatan'] ?? ''),
            'Status' => (string) ($row['Status'] ?? ''),
            'Level' => (string) ($row['Level'] ?? ''),
            'Tanggal Masuk' => (string) ($row['Tanggal Masuk'] ?? ''),
            'Hasil' => (string) ($row['Hasil'] ?? ''),
            'Departemen' => (string) ($row['Departemen'] ?? ''),
            'Level Summary' => (string) ($row['Level Summary'] ?? ''),
        ];
    }

    private function formatGender(string $sex): string
    {
        return match (strtolower(trim($sex))) {
            'male', 'l', 'laki-laki', 'pria' => 'L',
            'female', 'p', 'perempuan', 'wanita' => 'P',
            default => trim($sex),
        };
    }

    private function formatLevel(string $level): string
    {
        $level = trim($level);
        if ($level === '') {
            return '';
        }
        if (preg_match('/(\d+)/', $level, $matches) === 1) {
            return 'Level ' . ((int) $matches[1]);
        }

        return $level;
    }

    private function formatDate(string $date): string
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

    private function dateSortKey(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (Throwable) {
            return $date;
        }
    }

    private function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
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
