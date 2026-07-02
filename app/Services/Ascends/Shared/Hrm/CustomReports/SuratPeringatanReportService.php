<?php

namespace App\Services\Ascends\Shared\Hrm\CustomReports;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class SuratPeringatanReportService
{
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $parsed = $this->parseRows($xmlContents, $sourceLabel);
        $allRows = $parsed['data_rows'];

        if ($allRows === []) {
            throw new RuntimeException('Data surat peringatan tidak ditemukan pada XML.');
        }

        $filteredRows = $this->filterDocTypeSp($allRows);
        if ($filteredRows === []) {
            throw new RuntimeException('Data surat peringatan (SP) tidak ditemukan pada XML.');
        }

        $mappedRows = array_map(fn (array $row): array => $this->mapRow($row), $filteredRows);

        $this->sortRows($mappedRows);

        $groupedRows = $this->groupRows($mappedRows);
        $grandSummary = $this->summary($mappedRows);

        return [
            'title' => 'Laporan Surat Peringatan',
            'headerTitle' => 'Laporan Surat Peringatan',
            'subtitle' => 'Per Tanggal '.Carbon::now()->locale('id')->translatedFormat('d-M-y'),
            'grouped_rows' => $groupedRows,
            'grand_summary' => $grandSummary,
            'period' => [
                'label' => 'Per Tanggal '.Carbon::now()->locale('id')->translatedFormat('d-M-y'),
            ],
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
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

        $dataRows = [];

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

            if (($row['EmployeeCode'] ?? '') !== '') {
                $dataRows[] = $row;
            }
        }

        $reader->close();

        return [
            'data_rows' => $dataRows,
        ];
    }

    private function filterDocTypeSp(array $rows): array
    {
        return array_values(array_filter($rows, static function (array $row): bool {
            $docType = strtoupper(trim((string) ($row['DocType'] ?? '')));
            $warningLevel = (int) ($row['WarningLevel'] ?? 0);

            return $docType === 'UC-SP' && in_array($warningLevel, [7, 8, 9], true);
        }));
    }

    private function mapRow(array $row): array
    {
        $aktif = $this->parseDate($row['WarningNoticeDate'] ?? '');
        $berakhir = $this->parseDate($row['Expired'] ?? '');

        return [
            'Nama' => $row['FullName'] ?? '',
            'L/P' => $this->sexCode($row['Sex'] ?? ''),
            'Jabatan' => $row['JobTitle'] ?? '',
            'Status' => $row['DailyWorkerTypeCode'] ?? '',
            'SP' => $this->warningLevel((int) ($row['WarningLevel'] ?? 0)),
            'Tanggal Aktif' => $aktif ? $aktif->locale('id')->translatedFormat('d-M-y') : '',
            'Tanggal Berakhir' => $berakhir ? $berakhir->locale('id')->translatedFormat('d-M-y') : '',
            'Keterangan' => $row['DescriptionOfInfraction'] ?? '',
            'department' => $row['DepartmentName'] ?? '',
            'level' => (int) ($row['LevelName'] ?? 0),
            'sp_value' => (int) ($row['WarningLevel'] ?? 0),
            'active_date_value' => $aktif ? $aktif->timestamp : 0,
        ];
    }

    private function warningLevel(int $value): string
    {
        return match ($value) {
            7 => 'SP 1',
            8 => 'SP 2',
            9 => 'SP 3',
            default => 'SP ' . $value,
        };
    }

    private function sexCode(string $value): string
    {
        $value = strtolower(trim($value));

        return match (true) {
            str_contains($value, 'male'), str_contains($value, 'l'), str_contains($value, 'pria'), str_contains($value, 'laki') => 'L',
            str_contains($value, 'female'), str_contains($value, 'p'), str_contains($value, 'wanita'), str_contains($value, 'perempuan') => 'P',
            default => strtoupper($value),
        };
    }

    private function parseDate(string $value): ?Carbon
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

    private function sortRows(array &$rows): void
    {
        usort($rows, static function (array $a, array $b): int {
            $deptCmp = strcmp(
                strtolower(trim((string) ($a['department'] ?? ''))),
                strtolower(trim((string) ($b['department'] ?? '')))
            );
            if ($deptCmp !== 0) {
                return $deptCmp;
            }

            $nameCmp = strcmp(
                strtolower(trim((string) ($a['Nama'] ?? ''))),
                strtolower(trim((string) ($b['Nama'] ?? '')))
            );
            if ($nameCmp !== 0) {
                return $nameCmp;
            }

            return (int) ($a['active_date_value'] ?? 0) <=> (int) ($b['active_date_value'] ?? 0);
        });
    }

    private function groupRows(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $department = trim((string) ($row['department'] ?? '')) ?: 'Tanpa Departemen';
            $groups[$department]['department'] = $department;
            $groups[$department]['rows'][] = $row;
        }

        foreach ($groups as $department => $group) {
            $group['summary'] = $this->summary($group['rows']);
            $groups[$department] = $group;
        }

        uasort($groups, static function (array $left, array $right): int {
            $leftCount = (int) ($left['summary']['subtotal'] ?? 0);
            $rightCount = (int) ($right['summary']['subtotal'] ?? 0);

            return [$rightCount, (string) ($left['department'] ?? '')] <=> [$leftCount, (string) ($right['department'] ?? '')];
        });

        return array_values($groups);
    }

    private function summary(array $rows): array
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
            'sex' => $this->withPercents($sexCounts, $total),
            'status' => $this->withPercents($statusCounts, $total),
            'level' => $this->withPercents($levelCounts, $total),
            'sp' => $this->withPercents($spCounts, $total),
        ];
    }

    private function withPercents(array $counts, int $total): array
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
}
