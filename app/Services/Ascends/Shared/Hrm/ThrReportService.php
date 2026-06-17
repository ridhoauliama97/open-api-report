<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class ThrReportService
{
    private const THR_ALIASES = [
        'Pilih THR',
        'Pilih_x0020_THR',
        'pilih_thr',
        'PilihTHR',
        'thr',
        'THR',
    ];

    private const PER_DATE_ALIASES = [
        'PerDate',
        'per_date',
        'perdate',
        'PeriodeDate',
        'tanggal',
        'date',
    ];

    private const COMPANY_ALIASES = [
        'company',
        'DB_CompanyName',
        'DB_Company',
    ];

    private const WORKER_TYPE_ORDER = [
        'KARYAWAN KONTRAK' => 'Karyawan Kontrak',
        'KARYAWAN TETAP' => 'Karyawan Tetap',
        'STAFF' => 'Staff',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rawRows = $this->parseXml($xmlContents, $sourceLabel);
        $thr = self::resolveThr($filters);
        $perDate = self::resolvePerDate($filters);
        $company = strtoupper(trim(self::filterValue($filters, self::COMPANY_ALIASES)));
        $filteredByThr = self::filterByThr($rawRows, $thr);
        $filteredByActive = self::filterActiveWithTenure($filteredByThr, $perDate, $company);
        $mappedRows = array_map(fn (array $row): array => self::mapRow($row), $filteredByActive);
        $sections = self::groupByWorkerType($mappedRows, $thr);

        return [
            'title' => "Laporan THR ({$thr})",
            'thr_type' => $thr,
            'source_file' => $sourceLabel,
            'year_label' => Carbon::now()->format('Y'),
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => self::resolvePrintedBy($rawRows),
            'headers' => ['No', 'Nama', 'NIK', 'Jabatan', 'Tanggal Masuk', 'Lama Bekerja', 'Gaji Pokok'],
            'sections' => $sections,
            'total_rows' => count($mappedRows),
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Employee List kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Employee List tidak valid: {$sourceLabel}");
        }

        $rows = [];
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

            $row = json_decode(json_encode($node), true) ?: [];
            $rows[] = array_map(
                static fn (mixed $value): string => is_array($value) ? '' : trim((string) $value),
                $row
            );
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('XML Employee List tidak memiliki record.');
        }

        return $rows;
    }

    private static function resolveThr(array $filters): string
    {
        $value = self::filterValue($filters, self::THR_ALIASES);

        if ($value === '') {
            return 'Idul Fitri';
        }

        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/^thr\s+/i', '', $normalized) ?? $normalized;

        if (str_contains($normalized, 'natal')) {
            return 'Natal';
        }

        if (str_contains($normalized, 'imlek')) {
            return 'Imlek';
        }

        return 'Idul Fitri';
    }

    private static function resolvePerDate(array $filters): Carbon
    {
        $value = self::filterValue($filters, self::PER_DATE_ALIASES);

        return $value !== '' ? (self::parseDate($value) ?? Carbon::now()) : Carbon::now();
    }

    private static function filterActiveWithTenure(array $rows, Carbon $perDate, string $company): array
    {
        if ($company !== 'GSU') {
            return $rows;
        }

        return array_values(array_filter($rows, static function (array $row) use ($perDate): bool {
            $workerType = strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Name'] ?? '')));

            if ($workerType === 'STAFF') {
                return true;
            }

            return strcasecmp(trim((string) ($row['Active'] ?? '')), 'Active') === 0
                && self::daysSinceJoin($row, $perDate) > 90;
        }));
    }

    private static function daysSinceJoin(array $row, Carbon $perDate): int
    {
        $joinDate = self::parseDate((string) ($row['Join_x0020_Date'] ?? ''));
        if ($joinDate === null) {
            return 0;
        }

        return (int) $joinDate->diffInDays($perDate, false);
    }

    private static function filterByThr(array $rows, string $thr): array
    {
        $filterThr = strtolower(trim($thr));

        return array_values(array_filter($rows, static fn (array $row): bool => (
            preg_replace('/^thr\s+/i', '', strtolower(trim((string) ($row['THR'] ?? '')))) === $filterThr
        )));
    }

    private static function mapRow(array $row): array
    {
        $joinDate = self::parseDate((string) ($row['Join_x0020_Date'] ?? ''));
        $masaKerja = $joinDate !== null ? self::formatMasaKerja($joinDate) : '';

        return [
            'Nama' => (string) ($row['Full_x0020_Name'] ?? ''),
            'NIK' => (string) ($row['Employee_x0020_Code'] ?? ''),
            'Jabatan' => (string) ($row['Job_x0020_Title'] ?? ''),
            'Tanggal Masuk' => $joinDate?->locale('id')->translatedFormat('d-M-Y') ?? '',
            'Lama Bekerja' => $masaKerja,
            'Gaji Pokok' => self::formatAmount((string) ($row['Basic_x0020_Salary'] ?? '0')),
            'worker_type_name' => strtoupper(trim((string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Name'] ?? ''))),
        ];
    }

    private static function groupByWorkerType(array $rows, string $thrType): array
    {
        $grouped = [];

        foreach (self::WORKER_TYPE_ORDER as $typeName => $label) {
            $typeRows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => ($row['worker_type_name'] ?? '') === $typeName
            ));

            $grouped[] = [
                'label' => $label,
                'title' => "Laporan THR ({$thrType}) - {$label}",
                'rows' => $typeRows,
                'total' => count($typeRows),
            ];
        }

        $grouped = array_values(array_filter($grouped, static fn (array $section): bool => $section['total'] > 0));

        if ($grouped === []) {
            $grouped[] = [
                'label' => 'Staff',
                'title' => "Laporan THR ({$thrType}) - Staff",
                'rows' => [],
                'total' => 0,
            ];
        }

        return $grouped;
    }

    private static function formatMasaKerja(Carbon $joinDate): string
    {
        $now = Carbon::now();
        $diff = $joinDate->diff($now);
        $years = $diff->y;
        $months = $diff->m;

        $parts = [];
        if ($years > 0) {
            $parts[] = "{$years} Thn";
        }
        if ($months > 0) {
            $parts[] = "{$months} Bln";
        }

        return $parts !== [] ? implode(' ', $parts) : '0 Bln';
    }

    private static function formatAmount(string $value): string
    {
        $amount = (float) $value;

        if ($amount === 0.0) {
            return '0';
        }

        return number_format($amount, 0, ',', '.');
    }

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

        $normalizedAliases = array_map(static fn (string $alias): string => self::normalizeKey($alias), $aliases);
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
