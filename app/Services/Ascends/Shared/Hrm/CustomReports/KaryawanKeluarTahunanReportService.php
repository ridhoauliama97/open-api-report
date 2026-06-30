<?php

namespace App\Services\Ascends\Shared\Hrm\CustomReports;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class KaryawanKeluarTahunanReportService
{
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data karyawan tidak ditemukan pada XML.');
        }

        $status = trim((string) ($filters['Status'] ?? $filters['status'] ?? ''));
        if ($status === '') {
            throw new RuntimeException('Parameter Status wajib diisi (ST atau KK/KT).');
        }

        $periodStart = $this->resolvePeriodFilter($filters, 'PeriodStart');
        $periodEnd = $this->resolvePeriodFilter($filters, 'PeriodEnd');

        if ($periodStart === null || $periodEnd === null) {
            throw new RuntimeException('Parameter PeriodStart dan PeriodEnd wajib diisi.');
        }

        $rows = $this->filterByPeriodRange($rows, $periodStart, $periodEnd);

        if ($rows === []) {
            throw new RuntimeException("Tidak ada data karyawan dengan status {$status}.");
        }

        $monthColumns = $this->buildMonthColumns($periodStart, $periodEnd);

        $byDepartment = $this->groupByDepartment($rows);
        $pivotRows = $this->buildPivotRows($byDepartment, $monthColumns);
        $totals = $this->buildTotals($pivotRows, $monthColumns);

        $minPeriodLabel = $monthColumns[0]['label'] ?? '';
        $maxPeriodLabel = $monthColumns[count($monthColumns) - 1]['label'] ?? '';

        return [
            'title' => 'Laporan Karyawan Keluar Tahunan ('.$status.')',
            'headerCompany' => trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? '')),
            'headerTitle' => 'Laporan Karyawan Keluar Tahunan ('.$status.')',
            'subtitle' => 'Periode : '.$minPeriodLabel.' s/d '.$maxPeriodLabel,
            'status_label' => $status,
            'month_columns' => $monthColumns,
            'rows' => $pivotRows,
            'totals' => $totals,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? '')),
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

    private function filterByPeriodRange(array $rows, Carbon $start, Carbon $end): array
    {
        $startPeriod = (int) $start->format('Ym');
        $endPeriod = (int) $end->format('Ym');

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => true
                && ($row['Period'] ?? null) !== null
                && (int) $row['Period'] >= $startPeriod
                && (int) $row['Period'] <= $endPeriod
        ));
    }

    private function resolvePeriodFilter(array $filters, string $suffix): ?Carbon
    {
        $value = trim((string) (
            $filters[$suffix]
            ?? $filters['Period.'.$suffix]
            ?? $filters['period_'.strtolower($suffix)]
            ?? $filters[strtolower($suffix)]
            ?? ''
        ));

        if ($value === '') {
            return null;
        }

        return $this->parsePeriod($value);
    }

    private function parsePeriod(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{6}$/', $value)) {
                return Carbon::createFromFormat('Ym', $value)->startOfMonth();
            }

            return Carbon::parse($value)->startOfMonth();
        } catch (Throwable) {
            return null;
        }
    }

    private function buildMonthColumns(Carbon $start, Carbon $end): array
    {
        $columns = [];
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            $columns[] = [
                'key' => (int) $cursor->format('Ymd'),  // e.g. 20260101 for sorting
                'period' => (int) $cursor->format('Ym'), // e.g. 202601
                'label' => $cursor->locale('id')->translatedFormat('M-y'),
            ];
            $cursor->addMonthNoOverflow();
        }

        return $columns;
    }

    private function groupByDepartment(array $rows): array
    {
        $byDepartment = [];
        foreach ($rows as $row) {
            $dept = trim((string) ($row['DepartmentName'] ?? ''));
            if ($dept === '') {
                continue;
            }

            $period = (int) ($row['Period'] ?? 0);
            $aktif = (int) ($row['AKTIF'] ?? 0);
            $keluar = (int) ($row['KELUAR'] ?? 0);

            if ($period === 0) {
                continue;
            }

            if (! isset($byDepartment[$dept])) {
                $byDepartment[$dept] = [];
            }

            if (! isset($byDepartment[$dept][$period])) {
                $byDepartment[$dept][$period] = ['aktif' => 0, 'keluar' => 0];
            }

            $byDepartment[$dept][$period]['aktif'] += $aktif;
            $byDepartment[$dept][$period]['keluar'] += $keluar;
        }

        return $byDepartment;
    }

    private function buildPivotRows(array $byDepartment, array $monthColumns): array
    {
        $departmentNames = array_keys($byDepartment);
        sort($departmentNames, SORT_NATURAL | SORT_FLAG_CASE);

        $rows = [];
        foreach ($departmentNames as $dept) {
            $values = [];
            $total = 0;

            foreach ($monthColumns as $col) {
                $period = $col['period'];
                $data = $byDepartment[$dept][$period] ?? null;

                $keluar = $data ? $data['keluar'] : 0;
                $aktif = $data ? $data['aktif'] : 0;
                $persen = $aktif > 0 ? round(($keluar / $aktif) * 100) : 0;

                $values[(string) $period] = [
                    'keluar' => $keluar,
                    'persen' => $persen,
                ];
                $total += $keluar;
            }

            $rows[] = [
                'departemen' => $dept,
                'values' => $values,
                'total' => $total,
            ];
        }

        return $rows;
    }

    private function buildTotals(array $rows, array $monthColumns): array
    {
        $byMonth = [];
        $grandTotal = 0;

        foreach ($monthColumns as $col) {
            $period = $col['period'];
            $totalKeluar = 0;

            foreach ($rows as $row) {
                $v = $row['values'][(string) $period] ?? null;
                if ($v !== null) {
                    $totalKeluar += $v['keluar'];
                }
            }

            $byMonth[(string) $period] = ['keluar' => $totalKeluar];
            $grandTotal += $totalKeluar;
        }

        return [
            'by_month' => $byMonth,
            'grand_total' => $grandTotal,
        ];
    }
}
