<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PengabaianKeterlambatanKehadiranManualReportService
{
    private const TITLE = 'Laporan Pengabaian Keterlambatan & Kehadiran Manual';

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rawRows = $this->parseAttendanceRows($xmlContents, $sourceLabel);
        $period = self::resolvePeriod($rawRows, $filters);
        $category = self::resolveCategory($filters);
        $rows = self::shapeRows($rawRows, $period, $category);
        $groupedRows = self::groupRows($rows);

        return [
            'title' => self::TITLE,
            'category' => $category,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => self::resolvePrintedBy($rawRows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
            'headers' => ['Dibuat Oleh', 'Nama', 'Jabatan', 'Keterangan', 'Tanggal', 'Absen Masuk', 'Absen Keluar'],
            'rows' => $rows,
            'grouped_rows' => $groupedRows,
            'grand_summary' => self::summaryByCreator($rows),
            'total_rows' => count($rows),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseAttendanceRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Attendance Full kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Attendance Full tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'attendance') {
                continue;
            }

            $nodeXml = $reader->readOuterXml();
            if (! is_string($nodeXml) || trim($nodeXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($nodeXml, 'SimpleXMLElement', LIBXML_NOCDATA);
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

        if ($rows === []) {
            throw new RuntimeException('XML Attendance Full tidak memiliki record Attendance.');
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, mixed>  $filters
     * @return array{start: Carbon, end: Carbon}
     */
    private static function resolvePeriod(array $rows, array $filters): array
    {
        $startDate = trim((string) ($filters['start_date'] ?? $filters['TglAwal'] ?? ''));
        $endDate = trim((string) ($filters['end_date'] ?? $filters['TglAkhir'] ?? ''));

        if ($startDate !== '' || $endDate !== '') {
            $start = self::parseDate($startDate) ?? self::parseDate($endDate);
            $end = self::parseDate($endDate) ?? self::parseDate($startDate);

            if ($start !== null && $end !== null) {
                if ($end->lessThan($start)) {
                    [$start, $end] = [$end, $start];
                }

                return ['start' => $start->startOfDay(), 'end' => $end->endOfDay()];
            }
        }

        $dates = array_values(array_filter(array_map(
            static fn (array $row): ?Carbon => self::parseDate((string) ($row['Date'] ?? '')),
            $rows
        )));

        if ($dates === []) {
            $now = Carbon::now()->startOfMonth();

            return ['start' => $now->copy()->startOfDay(), 'end' => $now->copy()->endOfMonth()->endOfDay()];
        }

        usort($dates, static fn (Carbon $left, Carbon $right): int => $left <=> $right);

        return ['start' => $dates[0]->copy()->startOfDay(), 'end' => $dates[count($dates) - 1]->copy()->endOfDay()];
    }

    /**
     * @param  array<int, array<string, string>>  $rawRows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<int, array<string, string>>
     */
    private static function shapeRows(array $rawRows, array $period, string $category): array
    {
        $rows = [];
        $categoryCodes = self::employeeTypeCodes($category);

        foreach ($rawRows as $row) {
            $employeeCode = trim((string) ($row['Employee_x0020_Code'] ?? ''));
            $date = self::parseDate((string) ($row['Date'] ?? ''));
            if ($employeeCode === ''
                || str_starts_with(strtoupper($employeeCode), 'SPECIAL')
                || $date === null
                || ! $date->betweenIncluded($period['start'], $period['end'])
            ) {
                continue;
            }

            $employeeType = strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? '')));
            if ($categoryCodes !== [] && ! in_array($employeeType, $categoryCodes, true)) {
                continue;
            }

            $creator = trim((string) ($row['Created_x0020_By'] ?? $row['Last_x0020_Modified_x0020_By'] ?? ''));
            if ($creator === '') {
                continue;
            }

            $notes = self::manualNotes($row);
            $rows[] = [
                'Dibuat Oleh' => $creator,
                'Nama' => trim((string) ($row['Full_x0020_Name'] ?? '')),
                'Jabatan' => trim((string) ($row['Job_x0020_Title'] ?? '')),
                'Keterangan' => $notes,
                'Tanggal' => $date->locale('id')->translatedFormat('d-M-y'),
                'Absen Masuk' => self::formatTime((string) ($row['Sign_x0020_In'] ?? $row['First_x0020_Sign_x0020_In'] ?? $row['Time_x0020_In'] ?? '')),
                'Absen Keluar' => self::formatTime((string) ($row['Sign_x0020_Out'] ?? $row['Last_x0020_Sign_x0020_Out'] ?? $row['Time_x0020_Out'] ?? '')),
                'Departemen' => trim((string) ($row['Department_x0020_Name'] ?? '')),
            ];
        }

        usort($rows, static fn (array $left, array $right): int => [
            (string) ($left['Departemen'] ?? ''),
            (string) ($left['Dibuat Oleh'] ?? ''),
            (string) ($left['Nama'] ?? ''),
        ] <=> [
            (string) ($right['Departemen'] ?? ''),
            (string) ($right['Dibuat Oleh'] ?? ''),
            (string) ($right['Nama'] ?? ''),
        ]);

        return $rows;
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function manualNotes(array $row): string
    {
        $notes = [];
        $flags = [
            'Ignore_x0020_Late_x0020_Sign_x0020_In' => 'Pengabaian terlambat masuk',
            'Ignore_x0020_Early_x0020_Sign_x0020_Out' => 'Pengabaian pulang awal',
            'Ignore_x0020_Forget_x0020_Sign_x0020_In' => 'Pengabaian lupa absen masuk',
            'Ignore_x0020_Forget_x0020_Sign_x0020_Out' => 'Pengabaian lupa absen keluar',
        ];

        foreach ($flags as $field => $label) {
            if (strtolower(trim((string) ($row[$field] ?? ''))) === 'true') {
                $notes[] = $label;
            }
        }

        $remarks = trim((string) ($row['Remarks'] ?? ''));
        if ($remarks !== '') {
            $notes[] = $remarks;
        }

        return implode('; ', array_values(array_unique($notes)));
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array{label: string, rows: array<int, array<string, string>>, summary: array<string, mixed>}>
     */
    private static function groupRows(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $department = trim((string) ($row['Departemen'] ?? ''));
            $grouped[$department][] = $row;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        $result = [];
        foreach ($grouped as $department => $items) {
            $result[] = [
                'label' => 'Departemen : '.$department,
                'rows' => array_values($items),
                'summary' => self::summaryByCreator($items),
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    private static function summaryByCreator(array $rows): array
    {
        $summary = [
            'Dina' => 0,
            'Sasi' => 0,
            'Windi' => 0,
        ];
        foreach ($rows as $row) {
            $creator = trim((string) ($row['Dibuat Oleh'] ?? ''));
            if ($creator === '') {
                continue;
            }

            $summary[$creator] = ($summary[$creator] ?? 0) + 1;
        }

        ksort($summary, SORT_NATURAL | SORT_FLAG_CASE);
        $total = max(1, array_sum($summary));

        return array_map(
            static fn (string $label, int $count): array => [
                'label' => $label,
                'count' => $count,
                'percent' => (int) round(($count / $total) * 100),
            ],
            array_keys($summary),
            array_values($summary)
        );
    }

    private static function resolveCategory(array $filters): string
    {
        foreach (['category', 'kategori', 'Kategori', 'status', 'Status', 'tipe', 'Tipe'] as $key) {
            $value = trim((string) ($filters[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'ST';
    }

    /**
     * @return array<int, string>
     */
    private static function employeeTypeCodes(string $category): array
    {
        $normalized = strtoupper(str_replace(['\\', '-', '+', ' '], '/', trim($category)));
        if ($normalized === '' || in_array($normalized, ['ALL', 'SEMUA'], true)) {
            return [];
        }

        return array_values(array_intersect(
            preg_split('/[\/,;]+/', $normalized) ?: [],
            ['BR', 'KK', 'KT', 'ST']
        ));
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

    private static function formatTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (Throwable) {
            return $value;
        }
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private static function resolvePrintedBy(array $rows): string
    {
        foreach ($rows as $row) {
            foreach (['Created_x0020_By', 'Last_x0020_Modified_x0020_By'] as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }
}
