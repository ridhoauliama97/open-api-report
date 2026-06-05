<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class KetidakhadiranBulananReportService
{
    private const TITLE = 'Laporan Ketidakhadiran Bulanan';

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rawRows = $this->parseAbsenceRows($xmlContents, $sourceLabel);
        $tipe = self::resolveTipe($filters);
        $filteredRows = self::filterRowsByEmployeeType($rawRows, $tipe);
        $period = self::resolvePeriod($filteredRows, $filters);
        $dateColumns = self::resolveDateColumns($filteredRows, $period);
        $rows = self::shapeRows($filteredRows, $period, $dateColumns);
        $additionalNotes = self::shapeAdditionalNotes($filteredRows, $period);

        return [
            'title' => self::TITLE,
            'tipe' => $tipe,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => self::resolvePrintedBy($rawRows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari '.$period['start']->locale('id')->translatedFormat('d-M-y')
                    .' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
            'date_columns' => $dateColumns,
            'headers' => array_merge(['No', 'Nama', 'Jabatan'], array_column($dateColumns, 'label'), ['Total']),
            'rows' => $rows,
            'additional_notes' => $additionalNotes,
            'total_rows' => count($rows),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseAbsenceRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Absence kosong.');
        }

        $reader = new XMLReader;
        $opened = @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET);
        if ($opened === false) {
            throw new RuntimeException("XML Absence tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->localName) !== 'absence') {
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

            $row = json_decode(json_encode($node), true) ?: [];
            $rows[] = array_map(
                static fn (mixed $value): string => is_array($value) ? '' : trim((string) $value),
                $row
            );
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('XML Absence tidak memiliki record Absence.');
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

        return [
            'start' => $dates[0]->copy()->startOfDay(),
            'end' => $dates[count($dates) - 1]->copy()->endOfDay(),
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<int, array{date: string, label: string}>
     */
    private static function resolveDateColumns(array $rows, array $period): array
    {
        $dates = [];
        foreach ($rows as $row) {
            $date = self::parseDate((string) ($row['Date'] ?? ''));
            if ($date === null || ! $date->betweenIncluded($period['start'], $period['end'])) {
                continue;
            }

            $dates[$date->toDateString()] = $date->copy();
        }

        ksort($dates);

        return array_values(array_map(
            static fn (Carbon $date): array => [
                'date' => $date->toDateString(),
                'label' => (string) $date->day,
            ],
            $dates
        ));
    }

    /**
     * @param  array<int, array<string, string>>  $rawRows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @param  array<int, array{date: string, label: string}>  $dateColumns
     * @return array<int, array<string, mixed>>
     */
    private static function shapeRows(array $rawRows, array $period, array $dateColumns): array
    {
        $employees = [];
        $dateKeys = array_column($dateColumns, 'date');

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

            $key = $employeeCode;
            if (! isset($employees[$key])) {
                $employees[$key] = [
                    'Nama' => trim((string) ($row['Full_x0020_Name'] ?? '')),
                    'Jabatan' => trim((string) ($row['Job_x0020_Title'] ?? '')),
                    'dates' => array_fill_keys($dateKeys, ''),
                    'total' => 0.0,
                ];
            }

            $dateKey = $date->toDateString();
            $type = trim((string) ($row['Leave_x0020_Type'] ?? ''));
            $leaveDays = self::numericValue((string) ($row['Leave_x0020_Days'] ?? '1'));

            $employees[$key]['dates'][$dateKey] = self::appendCellValue(
                (string) ($employees[$key]['dates'][$dateKey] ?? ''),
                $type
            );
            $employees[$key]['total'] += $leaveDays > 0 ? $leaveDays : 1.0;
        }

        $rows = [];
        foreach ($employees as $employee) {
            $rows[] = [
                'Nama' => (string) ($employee['Nama'] ?? ''),
                'Jabatan' => (string) ($employee['Jabatan'] ?? ''),
                'dates' => $employee['dates'] ?? [],
                'Total' => self::formatTotal((float) ($employee['total'] ?? 0.0)),
            ];
        }

        usort($rows, static fn (array $left, array $right): int => [
            (string) ($left['Nama'] ?? ''),
            (string) ($left['Jabatan'] ?? ''),
        ] <=> [
            (string) ($right['Nama'] ?? ''),
            (string) ($right['Jabatan'] ?? ''),
        ]);

        return $rows;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, string>>
     */
    private static function filterRowsByEmployeeType(array $rows, string $tipe): array
    {
        $codes = self::employeeTypeCodes($tipe);
        if ($codes === []) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => in_array(
                strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? ''))),
                $codes,
                true
            )
        ));
    }

    /**
     * @param  array<int, array<string, string>>  $rawRows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<int, array{label: string, groups: array<int, array{remark: string, items: array<int, array{name: string, days: string, dates: string}>}>}>
     */
    private static function shapeAdditionalNotes(array $rawRows, array $period): array
    {
        $categoryOrder = ['LK', 'I', 'S', 'SKD', 'C', 'A', 'DL', 'KK', 'M'];
        $bucket = [];
        $labels = self::defaultAdditionalNoteLabels();

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

            $type = strtoupper(trim((string) ($row['Leave_x0020_Type'] ?? '')));
            if ($type === '') {
                continue;
            }

            $labels[$type] ??= self::additionalNoteLabel($row, $type);
            $remark = trim((string) ($row['Remarks'] ?? ''));
            $remark = $remark !== '' ? $remark : trim((string) ($row['Leave_x0020_Type_x0020_Description'] ?? ''));
            $remark = $remark !== '' ? $remark : $type;
            $name = trim((string) ($row['Full_x0020_Name'] ?? ''));
            $itemKey = $remark.'|'.$employeeCode;

            if (! isset($bucket[$type][$remark])) {
                $bucket[$type][$remark] = [];
            }

            if (! isset($bucket[$type][$remark][$itemKey])) {
                $bucket[$type][$remark][$itemKey] = [
                    'name' => $name,
                    'days' => 0.0,
                    'dates' => [],
                ];
            }

            $leaveDays = self::numericValue((string) ($row['Leave_x0020_Days'] ?? '1'));
            $bucket[$type][$remark][$itemKey]['days'] += $leaveDays > 0 ? $leaveDays : 1.0;
            $bucket[$type][$remark][$itemKey]['dates'][$date->toDateString()] = (string) $date->day;
        }

        $result = [];
        $types = array_values(array_unique(array_merge(
            $categoryOrder,
            array_diff(array_keys($bucket), $categoryOrder)
        )));

        foreach ($types as $type) {
            $groups = [];
            $remarks = $bucket[$type] ?? [];
            ksort($remarks, SORT_NATURAL | SORT_FLAG_CASE);

            foreach ($remarks as $remark => $items) {
                $formattedItems = array_values(array_map(
                    static fn (array $item): array => [
                        'name' => (string) ($item['name'] ?? ''),
                        'days' => self::formatTotal((float) ($item['days'] ?? 0.0)),
                        'dates' => implode(', ', array_values($item['dates'] ?? [])),
                    ],
                    $items
                ));

                usort($formattedItems, static fn (array $left, array $right): int => [
                    (string) ($left['name'] ?? ''),
                    (string) ($left['dates'] ?? ''),
                ] <=> [
                    (string) ($right['name'] ?? ''),
                    (string) ($right['dates'] ?? ''),
                ]);

                $groups[] = [
                    'remark' => (string) $remark,
                    'items' => $formattedItems,
                ];
            }

            $result[] = [
                'label' => $labels[$type] ?? $type,
                'groups' => $groups,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private static function defaultAdditionalNoteLabels(): array
    {
        return [
            'LK' => 'Luar Kota',
            'I' => 'Izin',
            'S' => 'Sakit',
            'SKD' => 'SKD',
            'C' => 'Cuti',
            'A' => 'Alpha',
            'DL' => 'Diliburkan',
            'KK' => 'Kecelakaan Kerja',
            'M' => 'Mangkir',
        ];
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function additionalNoteLabel(array $row, string $type): string
    {
        $description = trim((string) ($row['Leave_x0020_Type_x0020_Description'] ?? ''));

        return $description !== '' ? $description : $type;
    }

    /**
     * @return array<int, string>
     */
    private static function employeeTypeCodes(string $tipe): array
    {
        $normalized = strtoupper(trim($tipe));
        if ($normalized === '' || in_array($normalized, ['ALL', 'SEMUA'], true)) {
            return [];
        }

        $normalized = str_replace(['\\', '-', '+'], '/', $normalized);
        $normalized = preg_replace('/\s+/', '', $normalized) ?: $normalized;

        return array_values(array_intersect(
            preg_split('/[\/,;]+/', $normalized) ?: [],
            ['KK', 'KT', 'ST', 'BR']
        ));
    }

    private static function appendCellValue(string $existing, string $value): string
    {
        if ($value === '') {
            return $existing;
        }

        return $existing === '' ? $value : $existing.' '.$value;
    }

    private static function numericValue(string $value): float
    {
        $normalized = str_replace(',', '.', trim($value));

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private static function formatTotal(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? (string) (int) $value
            : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function resolveTipe(array $filters): string
    {
        $tipe = trim((string) (
            $filters['tipe']
            ?? $filters['Tipe']
            ?? $filters['kategori']
            ?? $filters['Kategori']
            ?? $filters['pilih_kategori']
            ?? $filters['PilihKategori']
            ?? $filters['Pilih Kategori']
            ?? $filters['Pilih_x0020_Kategori']
            ?? $filters['type']
            ?? $filters['Type']
            ?? ''
        ));

        return $tipe !== '' ? $tipe : 'KK/KT';
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
