<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class SuratPeringatanReportService
{
    private const TITLE = 'Laporan Surat Peringatan';

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rows = $this->parseXml($xmlContents, $sourceLabel);
        $reportDate = self::resolveReportDate($filters);
        $mappedRows = array_values(array_map(static fn(array $row): array => self::mapRow($row), $rows));
        $groupedRows = self::groupRows($mappedRows);
        $grandSummary = self::summary($mappedRows);
        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => self::resolvePrintedBy($rows),
            'headers' => ['Nama', 'L/P', 'Jabatan', 'Status', 'SP', 'Tanggal Aktif', 'Tanggal Berakhir', 'Keterangan'],
            'rows' => $mappedRows,
            'grouped_rows' => $groupedRows,
            'grand_summary' => $grandSummary,
            'total_rows' => count($mappedRows),
            'period' => [
                'report_date' => $reportDate->toDateString(),
                'label' => 'Per Tanggal ' . $reportDate->locale('id')->translatedFormat('d-M-y'),
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Warning Notice kosong.');
        }

        $reader = new XMLReader;
        if (!@$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Warning Notice tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'warning') {
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

            $rows[] = array_map(
                static fn(mixed $value): string => is_array($value) ? '' : trim((string) $value),
                json_decode(json_encode($node), true) ?: []
            );
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('XML Warning Notice tidak memiliki record warning.');
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private static function mapRow(array $row): array
    {
        $activeDate = self::parseDate((string) ($row['Date'] ?? ''));
        $sp = self::warningLevel((string) ($row['Warning_x0020_Level'] ?? ''));

        return [
            'Nama' => (string) ($row['Full_x0020_Name'] ?? ''),
            'L/P' => self::sexCode((string) ($row['Sex'] ?? '')),
            'Jabatan' => (string) ($row['Job_x0020_Title'] ?? ''),
            'Status' => (string) ($row['Daily_x0020_Worker_x0020_Type_x0020_Code'] ?? ''),
            'SP' => $sp,
            'Tanggal Aktif' => $activeDate?->locale('id')->translatedFormat('d-M-y') ?? '',
            'Tanggal Berakhir' => $activeDate?->copy()->addDays(180)->locale('id')->translatedFormat('d-M-y') ?? '',
            'Keterangan' => (string) ($row['Description_x0020_Of_x0020_Infraction'] ?? ''),
            'department' => (string) ($row['Department_x0020_Name'] ?? ''),
            'department_code' => (string) ($row['Department_x0020_Code'] ?? ''),
            'level' => self::toInt($row['Level'] ?? 0),
            'sp_value' => self::toInt(preg_replace('/\D+/', '', $sp) ?: '0'),
            'active_date_value' => $activeDate?->timestamp ?? 0,
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
            usort($group['rows'], static function (array $left, array $right): int {
                return [
                    (int) ($left['sp_value'] ?? 0),
                    (string) ($left['Nama'] ?? ''),
                    (int) ($left['active_date_value'] ?? 0),
                ] <=> [
                    (int) ($right['sp_value'] ?? 0),
                    (string) ($right['Nama'] ?? ''),
                    (int) ($right['active_date_value'] ?? 0),
                ];
            });

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
        $sexCounts = ['L' => 0, 'P' => 0];
        $statusCounts = ['BR' => 0, 'KK' => 0, 'KT' => 0, 'ST' => 0];
        $levelCounts = array_fill(1, 7, 0);
        $spCounts = ['SP 1' => 0, 'SP 2' => 0, 'SP 3' => 0];

        foreach ($rows as $row) {
            $sex = (string) ($row['L/P'] ?? '');
            if (isset($sexCounts[$sex])) {
                $sexCounts[$sex]++;
            }

            $status = strtoupper((string) ($row['Status'] ?? ''));
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }

            $level = (int) ($row['level'] ?? 0);
            if (isset($levelCounts[$level])) {
                $levelCounts[$level]++;
            }

            $sp = (string) ($row['SP'] ?? '');
            if (isset($spCounts[$sp])) {
                $spCounts[$sp]++;
            }
        }

        return [
            'subtotal' => $total,
            'sex' => self::withPercents($sexCounts, $total),
            'status' => self::withPercents($statusCounts, $total),
            'level' => self::withPercents($levelCounts, $total),
            'sp' => self::withPercents($spCounts, $total),
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
     * @param  array<string, mixed>  $filters
     */
    private static function resolveReportDate(array $filters): Carbon
    {
        $date = self::filterValue($filters, ['report_date', 'tanggal', 'date', 'Tanggal', 'print_date']);

        return self::parseDate($date) ?? Carbon::now();
    }

    private static function warningLevel(string $value): string
    {
        if (preg_match('/(\d+)/', $value, $matches) === 1) {
            return 'SP ' . $matches[1];
        }

        return trim($value);
    }

    private static function sexCode(string $value): string
    {
        $value = strtolower(trim($value));

        return match (true) {
            $value === 'l', str_contains($value, 'male'), str_contains($value, 'pria'), str_contains($value, 'laki') => 'L',
            $value === 'p', str_contains($value, 'female'), str_contains($value, 'wanita'), str_contains($value, 'perempuan') => 'P',
            default => strtoupper($value),
        };
    }

    private static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach ([Carbon::ATOM, 'Y-m-d\TH:i:sP', 'Y-m-d H:i:s', 'Y-m-d', 'd/m/Y', 'd-M-y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date instanceof Carbon) {
                    return $date;
                }
            } catch (\Throwable) {
                // Try the next known Ascend date format.
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function toInt(mixed $value): int
    {
        return (int) preg_replace('/\D+/', '', (string) $value);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $aliases
     */
    private static function filterValue(array $filters, array $aliases): string
    {
        foreach ($aliases as $alias) {
            $value = trim((string) ($filters[$alias] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private static function resolvePrintedBy(array $rows): string
    {
        foreach ($rows as $row) {
            foreach (['Sys_Username', 'Sys_UserName', 'Printed_x0020_By'] as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }
}
