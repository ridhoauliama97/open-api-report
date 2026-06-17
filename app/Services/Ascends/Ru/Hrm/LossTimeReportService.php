<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LossTimeReportService
{
    private const TITLE = 'Laporan Loss Time';

    private const START_DATE_ALIASES = [
        'start_date',
        'StartDate',
        'startDate',
        'date_start',
        'DateStart',
        'from_date',
        'FromDate',
        'TglAwal',
        'TanggalAwal',
    ];

    private const END_DATE_ALIASES = [
        'end_date',
        'EndDate',
        'endDate',
        'date_end',
        'DateEnd',
        'to_date',
        'ToDate',
        'TglAkhir',
        'TanggalAkhir',
    ];

    private const TYPE_ALIASES = [
        'Pilih Type',
        'Pilih_x0020_Type',
        'pilih_type',
        'pilihType',
        'type',
        'Type',
        'Pilih Tipe',
        'Pilih_x0020_Tipe',
        'pilih_tipe',
        'pilihTipe',
        'tipe',
        'Tipe',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rawRows = $this->parseXml($xmlContents, $sourceLabel);
        $period = self::resolvePeriod($rawRows, $filters);
        $type = self::resolveType($filters);
        $filteredRows = self::filterRows($rawRows, $period, $type);
        $mappedRows = array_values(array_map(static fn(array $row): array => self::mapRow($row), $filteredRows));
        $groupedRows = self::groupRows($mappedRows);
        $grandSummary = self::summary($mappedRows);

        return [
            'title' => self::TITLE,
            'type' => $type,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => self::resolvePrintedBy($rawRows),
            'headers' => ['NIK', 'Nama Karyawan', 'Jabatan', 'Tanggal Izin', 'Total Jam', 'Total Menit', 'Keterangan'],
            'rows' => $mappedRows,
            'grouped_rows' => $groupedRows,
            'grand_summary' => $grandSummary,
            'total_rows' => count($mappedRows),
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Dari ' . $period['start']->locale('id')->translatedFormat('d-M-y') . ' s/d ' . $period['end']->locale('id')->translatedFormat('d-M-y'),
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Loss Time kosong.');
        }

        $reader = new XMLReader;
        if (!@$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Loss Time tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'losstime') {
                continue;
            }

            $recordXml = $reader->readOuterXML();
            if (!is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }

            $row = json_decode(json_encode($node), true) ?: [];
            $rows[] = array_map(
                static fn(mixed $value): string => is_array($value) ? '' : trim((string) $value),
                $row
            );
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('XML Loss Time tidak memiliki record.');
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
        $startDate = self::filterValue($filters, self::START_DATE_ALIASES);
        $endDate = self::filterValue($filters, self::END_DATE_ALIASES);

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
            static fn(array $row): ?Carbon => self::parseDate((string) ($row['Date'] ?? '')),
            $rows
        )));

        if ($dates === []) {
            $now = Carbon::now()->startOfMonth();

            return ['start' => $now->copy()->startOfDay(), 'end' => $now->copy()->endOfMonth()->endOfDay()];
        }

        usort($dates, static fn(Carbon $left, Carbon $right): int => $left <=> $right);

        return [
            'start' => $dates[0]->copy()->startOfMonth()->startOfDay(),
            'end' => $dates[count($dates) - 1]->copy()->endOfMonth()->endOfDay(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function resolveType(array $filters): string
    {
        $value = self::filterValue($filters, self::TYPE_ALIASES);

        return str_contains(strtoupper($value), 'STAFF') ? 'Staff' : 'KK/KT';
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<int, array<string, string>>
     */
    private static function filterRows(array $rows, array $period, string $type): array
    {
        return array_values(array_filter($rows, static function (array $row) use ($period, $type): bool {
            $date = self::parseDate((string) ($row['Date'] ?? ''));
            if ($date === null || !$date->betweenIncluded($period['start'], $period['end'])) {
                return false;
            }

            return self::matchesType($row, $type);
        }));
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function matchesType(array $row, string $type): bool
    {
        $workerType = strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? '')));

        return $type === 'Staff' ? $workerType === 'ST' : in_array($workerType, ['KK', 'KT', 'BR'], true);
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private static function mapRow(array $row): array
    {
        $totalMinutes = (int) ((float) ($row['Total_x0020_Loss_x0020_Minutes'] ?? 0));
        $hours = (float) ($row['Loss_x0020_Hours'] ?? 0);

        return [
            'NIK' => (string) ($row['Employee_x0020_Code'] ?? ''),
            'Nama Karyawan' => (string) ($row['Full_x0020_Name'] ?? ''),
            'Jabatan' => (string) ($row['Job_x0020_Title'] ?? ''),
            'Tanggal Izin' => self::parseDate((string) ($row['Date'] ?? ''))?->locale('id')->translatedFormat('d-M-y') ?? '',
            'Total Jam' => $hours,
            'Total Menit' => $totalMinutes,
            'Keterangan' => (string) ($row['Remarks'] ?? ''),
            'department' => (string) ($row['Department_x0020_Name'] ?? ''),
            'department_code' => (string) ($row['Department_x0020_Code'] ?? ''),
            'worker_type' => (string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? ''),
            'total_minutes_value' => $totalMinutes,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function groupRows(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $department = trim((string) ($row['department'] ?? '')) ?: 'Tanpa Departemen';
            $groups[$department]['department'] = $department;
            $groups[$department]['department_code'] ??= (string) ($row['department_code'] ?? '');
            $groups[$department]['rows'][] = $row;
        }

        foreach ($groups as $department => $group) {
            $group['summary'] = self::summary($group['rows']);
            $groups[$department] = $group;
        }

        uasort($groups, static function (array $left, array $right): int {
            $leftCount = (int) ($left['summary']['subtotal'] ?? 0);
            $rightCount = (int) ($right['summary']['subtotal'] ?? 0);

            return [$rightCount, (string) ($left['department'] ?? '')] <=> [$leftCount, (string) ($right['department'] ?? '')];
        });

        return array_values($groups);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private static function summary(array $rows): array
    {
        $total = count($rows);
        $statusCounts = ['KK' => 0, 'KT' => 0, 'ST' => 0, 'BR' => 0];
        $totalJam = 0;
        $totalMenit = 0;

        foreach ($rows as $row) {
            $status = strtoupper((string) ($row['worker_type'] ?? ''));
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }

            $totalJam += (float) ($row['Total Jam'] ?? 0);
            $totalMenit += (int) ($row['total_minutes_value'] ?? 0);
        }

        return [
            'subtotal' => $total,
            'status' => self::withPercents($statusCounts, $total),
            'total_jam' => $totalJam,
            'total_menit' => $totalMenit,
        ];
    }

    /**
     * @param  array<int|string, int>  $counts
     * @return array<int|string, array{count: int, percent: int}>
     */
    private static function withPercents(array $counts, int $total): array
    {
        $result = [];
        foreach ($counts as $key => $count) {
            $result[$key] = [
                'count' => $count,
                'percent' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private static function resolvePrintedBy(array $rows): string
    {
        foreach ($rows as $row) {
            foreach (['Sys_Username', 'Sys_UserName', 'Printed_x0020_By', 'Created_x0020_By'] as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $aliases
     */
    private static function filterValue(array $filters, array $aliases): string
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $filters)) {
                $value = trim((string) $filters[$alias]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        $normalizedAliases = array_map(static fn(string $alias): string => self::normalizeKey($alias), $aliases);
        foreach ($filters as $key => $value) {
            if (in_array(self::normalizeKey((string) $key), $normalizedAliases, true)) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private static function normalizeKey(string $key): string
    {
        return strtolower(str_replace([' ', '_x0020_', '_', '-'], '', $key));
    }

    private static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach ([Carbon::ATOM, 'Y-m-d\TH:i:sP', 'Y-m-d H:i:s', 'Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date instanceof Carbon) {
                    return $date->startOfDay();
                }
            } catch (Throwable) {
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
