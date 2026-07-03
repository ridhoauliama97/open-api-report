<?php

namespace App\Services\Ascends\Shared\Hrm\CustomReports;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class DiagramLemburTahunanReportService
{
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $parsed = $this->parseRows($xmlContents, $sourceLabel);
        $allRows = $parsed['data_rows'];

        if ($allRows === []) {
            throw new RuntimeException('Data lembur tidak ditemukan pada XML.');
        }

        $filteredRows = $this->filterByDateRange($allRows, $filters);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data lembur dalam rentang tanggal yang dipilih.');
        }

        $typeLabel = $this->resolveType($filteredRows);
        $period = $this->resolvePeriod($filteredRows, $filters);

        $monthlyData = $this->groupByMonthAndDepartment($filteredRows);
        $deptCosts = $this->calculateDepartmentCosts($filteredRows, array_column($monthlyData, 'departments')[0] ?? []);

        return [
            'title' => 'Laporan Diagram Lembur Tahunan Per Departemen',
            'headerTitle' => 'Laporan Diagram Lembur Tahunan Per Departemen',
            'type_label' => $typeLabel,
            'subtitle' => 'Dari '.$period['start'].' s/d '.$period['end'],
            'monthly_chart_data' => $monthlyData,
            'department_costs' => $deptCosts,
            'period' => $period,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
        ];
    }

    private function calculateDepartmentCosts(array $rows, array $orderedDepts): array
    {
        $costs = [];
        foreach ($rows as $row) {
            $dept = trim((string) ($row['DepartmentName'] ?? 'Tanpa Departemen'));
            $cost = (float) ($row['Total'] ?? 0);
            if (! isset($costs[$dept])) {
                $costs[$dept] = 0;
            }
            $costs[$dept] += $cost;
        }

        $result = [];
        foreach ($orderedDepts as $dept) {
            $name = $dept['name'] ?? $dept;
            if (isset($costs[$name])) {
                $result[$name] = round($costs[$name]);
            }
        }
        foreach ($costs as $name => $cost) {
            if (! isset($result[$name])) {
                $result[$name] = round($cost);
            }
        }

        return $result;
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

            if (($row['EmployeeID'] ?? '') !== '') {
                $dataRows[] = $row;
            }
        }

        $reader->close();

        return [
            'data_rows' => $dataRows,
        ];
    }

    private function filterByDateRange(array $rows, array $filters): array
    {
        $startDate = trim((string) ($filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['EndDate'] ?? $filters['end_date'] ?? ''));

        if ($startDate === '' && $endDate === '') {
            return $rows;
        }

        $start = $startDate !== '' ? $this->parseDate($startDate) : null;
        $end = $endDate !== '' ? $this->parseDate($endDate) : null;

        return array_values(array_filter($rows, static function (array $row) use ($start, $end): bool {
            $date = (string) ($row['Date'] ?? '');
            if ($date === '') {
                return false;
            }

            try {
                $rowDate = Carbon::parse($date);
            } catch (Throwable) {
                return false;
            }

            if ($start !== null && $rowDate->lt($start)) {
                return false;
            }

            if ($end !== null && $rowDate->gt($end->copy()->endOfDay())) {
                return false;
            }

            return true;
        }));
    }

    private function resolveType(array $rows): string
    {
        $types = array_unique(array_map(
            static fn (array $row): string => strtoupper(trim((string) ($row['DailyWorkerTypeCode'] ?? ''))),
            $rows
        ));

        $types = array_values(array_filter($types, static fn (string $v): bool => $v !== ''));

        if (count($types) === 0) {
            return '';
        }

        return implode('/', $types);
    }

    private function resolvePeriod(array $rows, array $filters): array
    {
        $startDate = trim((string) ($filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['EndDate'] ?? $filters['end_date'] ?? ''));

        if ($startDate !== '' && $endDate !== '') {
            try {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);

                $startLabel = $start->locale('id')->translatedFormat('d-M-y');
                $endLabel = $end->locale('id')->translatedFormat('d-M-y');

                return [
                    'start' => $startLabel,
                    'end' => $endLabel,
                    'label' => 'Dari '.$startLabel.' s/d '.$endLabel,
                ];
            } catch (Throwable) {
            }
        }

        $dates = array_filter(array_map(
            fn (array $row): ?Carbon => $this->parseDate($row['Date'] ?? ''),
            $rows
        ));

        if ($dates === []) {
            $today = Carbon::today();
            $todayLabel = $today->locale('id')->translatedFormat('d-M-y');

            return [
                'start' => $todayLabel,
                'end' => $todayLabel,
                'label' => 'Dari '.$todayLabel.' s/d '.$todayLabel,
            ];
        }

        $min = min($dates);
        $max = max($dates);

        $minLabel = $min->locale('id')->translatedFormat('d-M-y');
        $maxLabel = $max->locale('id')->translatedFormat('d-M-y');

        return [
            'start' => $minLabel,
            'end' => $maxLabel,
            'label' => 'Dari '.$minLabel.' s/d '.$maxLabel,
        ];
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

    private function groupByMonthAndDepartment(array $rows): array
    {
        $monthDeptHours = [];
        $deptGrandTotal = [];

        foreach ($rows as $row) {
            $dateStr = (string) ($row['Date'] ?? '');
            if ($dateStr === '') {
                continue;
            }

            try {
                $date = Carbon::parse($dateStr);
            } catch (Throwable) {
                continue;
            }

            $monthKey = $date->format('Y-m');
            $monthLabel = $date->locale('id')->translatedFormat('F Y');
            $department = trim((string) ($row['DepartmentName'] ?? 'Tanpa Departemen'));
            $hours = (float) ($row['ActualHours'] ?? 0);

            if (! isset($monthDeptHours[$monthKey])) {
                $monthDeptHours[$monthKey] = [
                    'month_label' => ucwords($monthLabel),
                    'month_key' => $monthKey,
                    'departments' => [],
                    'total_hours' => 0,
                ];
            }

            if (! isset($monthDeptHours[$monthKey]['departments'][$department])) {
                $monthDeptHours[$monthKey]['departments'][$department] = 0;
            }

            $monthDeptHours[$monthKey]['departments'][$department] += $hours;
            $monthDeptHours[$monthKey]['total_hours'] += $hours;

            if (! isset($deptGrandTotal[$department])) {
                $deptGrandTotal[$department] = 0;
            }
            $deptGrandTotal[$department] += $hours;
        }

        ksort($monthDeptHours);

        arsort($deptGrandTotal);
        $fixedDeptOrder = array_keys($deptGrandTotal);

        $result = [];
        foreach ($monthDeptHours as $monthKey => $data) {
            $totalHours = $data['total_hours'];
            $deptMap = $data['departments'];
            $deptList = [];

            foreach ($fixedDeptOrder as $deptName) {
                $deptHours = $deptMap[$deptName] ?? 0;
                if ($deptHours > 0) {
                    $deptList[] = [
                        'name' => $deptName,
                        'total_hours' => round($deptHours, 1),
                        'percentage' => $totalHours > 0 ? round(($deptHours / $totalHours) * 100, 1) : 0,
                    ];
                }
            }

            $maxHours = max(array_column($deptList, 'total_hours'));

            $result[] = [
                'month_label' => $data['month_label'],
                'month_key' => $monthKey,
                'departments' => $deptList,
                'total_hours' => round($totalHours, 1),
                'max_hours' => round($maxHours, 1),
            ];
        }

        return $result;
    }
}
