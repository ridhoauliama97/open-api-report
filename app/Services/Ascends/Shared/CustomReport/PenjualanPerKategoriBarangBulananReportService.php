<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PenjualanPerKategoriBarangBulananReportService
{
    private const TITLE = 'Laporan Penjualan Per Kategori Barang Bulanan (Breakdown Perhari)';

    private const CATEGORIES = ['pf', 'pk1', 'pk2', 'enamel', 'fl'];

    private const FAMILY_TO_CATEGORY = [
        'PLASTIK FURNITURE 1' => 'pf',
        'PLASTIK FURNITURE 2' => 'pf',
        'PLASTIK KABINET 1' => 'pk1',
        'PLASTIK KABINET 2' => 'pk2',
        'ENAMEL' => 'enamel',
        'FURNITURE LIPAT' => 'fl',
    ];

    private const PK2_FACTOR = 7 / 13;

    private const ENAMEL_FACTOR = 18 / 91;

    private const FL_FACTOR = 2 / 91;

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');
        $jumlahHariKerja = (int) ($filters['JumlahHariKerja'] ?? $filters['jumlah_hari_kerja'] ?? 25);
        if ($jumlahHariKerja <= 0) {
            $jumlahHariKerja = 25;
        }

        $filteredRows = $this->filterRows($rows, $startDate, $endDate);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data dalam rentang tanggal yang dipilih.');
        }

        [$dayNumbers, $weekByDay] = $this->resolvePeriod($filteredRows, $startDate, $endDate);

        $targetMap = $this->resolveTargetMap($filters);

        $sections = $this->buildSections($filteredRows, $dayNumbers, $weekByDay, $jumlahHariKerja, $targetMap);

        $grandTotals = [];
        $monthlyTargetGrand = [];
        foreach (self::CATEGORIES as $cat) {
            $grandTotals[$cat] = ['qty' => 0.0, 'rp' => 0.0];
            $monthlyTargetGrand[$cat] = 0.0;
        }
        $grandTotalAllQty = 0.0;
        $grandTotalAllRp = 0.0;

        foreach ($sections as $section) {
            foreach (self::CATEGORIES as $cat) {
                $grandTotals[$cat]['qty'] += $section['cat_totals'][$cat]['qty'];
                $grandTotals[$cat]['rp'] += $section['cat_totals'][$cat]['rp'];
                $monthlyTargetGrand[$cat] += $section['monthly_target'][$cat];
            }
            $grandTotalAllQty += $section['total_qty_all'];
            $grandTotalAllRp += $section['total_rp_all'];
        }

        $grandTotalSection = count($sections) > 1
            ? $this->buildGrandTotalSection($sections, $dayNumbers, $jumlahHariKerja, $monthlyTargetGrand)
            : null;

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));

        $totalHariKerjaActual = $this->countWorkingDays($startDate, $endDate);

        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        $startDateFormatted = $startDate ? $startDate->locale('id')->isoFormat('DD-MMM-YY') : '';
        $endDateFormatted = $endDate ? $endDate->locale('id')->isoFormat('DD-MMM-YY') : '';

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'start_date' => $startDateFormatted,
            'end_date' => $endDateFormatted,
            'period_label' => ($startDateFormatted && $endDateFormatted) ? 'Dari '.$startDateFormatted.' s/d '.$endDateFormatted : '',
            'sections' => $sections,
            'grand_total_section' => $grandTotalSection,
            'jumlah_hari_kerja' => $jumlahHariKerja,
            'total_hari_kerja_actual' => $totalHariKerjaActual,
            'grand_totals' => $grandTotals,
            'grand_total_all_qty' => $grandTotalAllQty,
            'grand_total_all_rp' => $grandTotalAllRp,
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
                $cleanKey = trim(str_replace('_x0020_', ' ', $key));
                $row[$cleanKey] = trim((string) $value);
            }

            $rows[] = $row;
        }

        $reader->close();

        return $rows;
    }

    private function resolveDateFilter(array $filters, string $key): ?Carbon
    {
        $value = trim((string) (
            $filters[$key]
            ?? $filters[strtolower($key)]
            ?? $filters['DateRange.'.$key]
            ?? $filters['DateRange_'.$key]
            ?? ''
        ));

        if ($value === '') {
            return null;
        }

        return $this->parseDate($value);
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

    private function filterRows(array $rows, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $filtered = [];
        foreach ($rows as $row) {
            $date = $this->parseDate((string) ($row['Date'] ?? ''));
            if ($date === null) {
                continue;
            }

            if ($startDate !== null && $date->lessThan($startDate->startOfDay())) {
                continue;
            }

            if ($endDate !== null && $date->greaterThan($endDate->endOfDay())) {
                continue;
            }

            $dayName = trim((string) ($row['Day'] ?? ''));
            if (strcasecmp($dayName, 'Sunday') === 0) {
                continue;
            }

            $filtered[] = $row;
        }

        return $filtered;
    }

    /**
     * @return array{0: int[], 1: array<int, int>} [dayNumbers, weekByDay]
     */
    private function resolvePeriod(array $rows, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $min = null;
        $max = null;
        $weekByDay = [];

        foreach ($rows as $row) {
            $date = $this->parseDate((string) ($row['Date'] ?? ''));
            if ($date === null) {
                continue;
            }

            $dayNum = (int) $date->format('j');
            $week = (int) ($row['Week'] ?? 0);
            if ($week >= 1 && $week <= 5) {
                $weekByDay[$dayNum] = $week;
            }

            if ($min === null || $date->lt($min)) {
                $min = $date->copy();
            }
            if ($max === null || $date->gt($max)) {
                $max = $date->copy();
            }
        }

        $from = ($startDate ?? $min)->copy()->startOfDay();
        $to = ($endDate ?? $max)->copy()->endOfDay();

        $dayNumbers = [];
        while ($from->lessThanOrEqualTo($to)) {
            if ($from->dayOfWeek !== Carbon::SUNDAY) {
                $dayNumbers[] = (int) $from->format('j');
            }
            $from->addDay();
        }

        return [$dayNumbers, $weekByDay];
    }

    private function resolveTargetMap(array $filters): array
    {
        $raw = $filters['Target'] ?? $filters['target'] ?? null;

        if (is_array($raw)) {
            return $this->normalizeTargetMap($raw);
        }

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $this->normalizeTargetMap($decoded);
            }
        }

        return [];
    }

    /**
     * @return array<string, array<string, float>>
     */
    private function normalizeTargetMap(array $map): array
    {
        $normalized = [];
        foreach ($map as $sp => $cats) {
            if (! is_array($cats)) {
                $normalized[(string) $sp] = $this->expandTargetFromPk1((float) $cats);

                continue;
            }

            $row = [];
            foreach (self::CATEGORIES as $cat) {
                $row[$cat] = (float) ($cats[$cat] ?? $cats[strtoupper($cat)] ?? 0);
            }
            $normalized[(string) $sp] = $row;
        }

        return $normalized;
    }

    /**
     * Menghitung target per kategori dari quota pk1 saja, mengikuti pola
     * PDF referensi: pk2 = 7/13 x pk1, enamel = 18/91 x pk1, fl = 2/91 x pk1, pf = 0.
     *
     * @return array<string, float>
     */
    private function expandTargetFromPk1(float $pk1): array
    {
        return [
            'pf' => 0.0,
            'pk1' => $pk1,
            'pk2' => round($pk1 * self::PK2_FACTOR),
            'enamel' => round($pk1 * self::ENAMEL_FACTOR),
            'fl' => round($pk1 * self::FL_FACTOR),
        ];
    }

    private function emptyCatTotals(): array
    {
        $totals = [];
        foreach (self::CATEGORIES as $cat) {
            $totals[$cat] = ['qty' => 0.0, 'rp' => 0.0, 'gross' => 0.0];
        }

        return $totals;
    }

    private function categorizeRow(array $row): ?string
    {
        $itemName = trim((string) ($row['Item Name'] ?? ''));
        if (str_starts_with(strtoupper($itemName), 'PROMO')) {
            return null;
        }

        $familyName = trim((string) ($row['Family Name'] ?? ''));

        return self::FAMILY_TO_CATEGORY[$familyName] ?? null;
    }

    private function buildSections(array $rows, array $dayNumbers, array $weekByDay, int $jumlahHariKerja, array $targetMap): array
    {
        $groupedBySales = [];
        foreach ($rows as $row) {
            $spName = trim((string) ($row['SP Name'] ?? ''));
            if ($spName === '') {
                $spName = 'Tanpa Sales';
            }
            $groupedBySales[$spName][] = $row;
        }

        ksort($groupedBySales);

        $sections = [];
        foreach ($groupedBySales as $salesName => $salesRows) {
            $sections[] = $this->buildSection($salesName, $salesRows, $dayNumbers, $weekByDay, $jumlahHariKerja, $targetMap[$salesName] ?? null);
        }

        return $sections;
    }

    private function buildSection(string $salesName, array $rows, array $dayNumbers, array $weekByDay, int $jumlahHariKerja, ?array $targetByCat): array
    {
        $catTotals = $this->emptyCatTotals();
        $rowsByDay = [];
        $dayHasData = [];

        foreach ($rows as $row) {
            $date = $this->parseDate((string) ($row['Date'] ?? ''));
            if ($date === null) {
                continue;
            }

            $dayNum = (int) $date->format('j');
            $cat = $this->categorizeRow($row);
            if ($cat === null) {
                continue;
            }

            $item = [
                'cat' => $cat,
                'qty' => (float) ($row['Quantity'] ?? 0),
                'rp' => (float) ($row['LineTotal'] ?? 0),
                'gross' => (float) ($row['LineGrossTotal'] ?? 0),
            ];

            $rowsByDay[$dayNum][] = $item;
            $dayHasData[$dayNum][$cat] = true;
            $catTotals[$cat]['qty'] += $item['qty'];
            $catTotals[$cat]['rp'] += $item['rp'];
        }

        $totalQtyAll = 0.0;
        $totalRpAll = 0.0;
        foreach (self::CATEGORIES as $cat) {
            $totalQtyAll += $catTotals[$cat]['qty'];
            $totalRpAll += $catTotals[$cat]['rp'];
        }

        $monthlyTarget = [];
        foreach (self::CATEGORIES as $cat) {
            $monthlyTarget[$cat] = $targetByCat !== null
                ? (float) ($targetByCat[$cat] ?? 0)
                : $catTotals[$cat]['rp'];
        }
        $monthlyTargetTotal = array_sum($monthlyTarget);

        $targetPerHari = [];
        foreach (self::CATEGORIES as $cat) {
            $targetPerHari[$cat] = round($monthlyTarget[$cat] / $jumlahHariKerja);
        }
        $targetTotalPerHari = round($monthlyTargetTotal / $jumlahHariKerja);

        $dailyRecords = $this->buildDailyRecords($rowsByDay, $dayNumbers, $weekByDay, $targetPerHari, $targetTotalPerHari);

        $subtotalDev = [];
        foreach (self::CATEGORIES as $cat) {
            $subtotalDev[$cat] = $cat === 'pk1'
                ? $monthlyTarget[$cat] - $totalRpAll
                : $monthlyTarget[$cat] - $catTotals[$cat]['rp'];
        }
        $totalDev = $monthlyTargetTotal - $totalRpAll;

        foreach (self::CATEGORIES as $cat) {
            $catTotals[$cat]['dev'] = $subtotalDev[$cat];
        }

        $weeklyAnalysis = $this->buildWeeklyAnalysis($dailyRecords, $monthlyTarget, $monthlyTargetTotal);
        $dailyAnalysis = $this->buildDailyAnalysis($dailyRecords, $dayHasData, count($dayNumbers));

        return [
            'sales_name' => $salesName,
            'cat_totals' => $catTotals,
            'total_qty_all' => $totalQtyAll,
            'total_rp_all' => $totalRpAll,
            'monthly_target' => $monthlyTarget,
            'monthly_target_total' => $monthlyTargetTotal,
            'target_per_hari' => $targetPerHari,
            'target_total_per_hari' => $targetTotalPerHari,
            'daily_records' => $dailyRecords,
            'day_has_data' => $dayHasData,
            'weekly_analysis' => $weeklyAnalysis,
            'daily_analysis' => $dailyAnalysis,
            'total_dev' => $totalDev,
        ];
    }

    private function buildDailyRecords(array $rowsByDay, array $dayNumbers, array $weekByDay, array $targetPerHari, float $targetTotalPerHari): array
    {
        $running = array_combine(self::CATEGORIES, array_fill(0, count(self::CATEGORIES), 0.0));
        $running['total'] = 0.0;
        $rowIndex = 0;
        $dailyRecords = [];

        foreach ($dayNumbers as $dayNum) {
            $rowIndex++;

            $dayCat = $this->emptyCatTotals();
            foreach (($rowsByDay[$dayNum] ?? []) as $item) {
                $dayCat[$item['cat']]['qty'] += $item['qty'];
                $dayCat[$item['cat']]['rp'] += $item['rp'];
                $dayCat[$item['cat']]['gross'] += $item['gross'];
            }

            $dayTotalQty = 0.0;
            $dayTotalRp = 0.0;
            foreach (self::CATEGORIES as $cat) {
                $dayTotalQty += $dayCat[$cat]['qty'];
                $dayTotalRp += $dayCat[$cat]['rp'];
                $running[$cat] += $dayCat[$cat]['rp'];
            }
            $running['total'] += $dayTotalRp;

            $record = [
                'day' => $dayNum,
                'week' => $weekByDay[$dayNum] ?? null,
                'total_qty' => $dayTotalQty,
                'total_rp' => $dayTotalRp,
                'total_running' => $running['total'],
                'total_dev' => $running['total'] - ($targetTotalPerHari * $rowIndex),
            ];

            foreach (self::CATEGORIES as $cat) {
                $record[$cat] = $dayCat[$cat];
                $record[$cat.'_running'] = $running[$cat];
                $record[$cat.'_dev'] = $running[$cat] - ($targetPerHari[$cat] * $rowIndex);
            }

            $dailyRecords[] = $record;
        }

        return $dailyRecords;
    }

    private function buildWeeklyAnalysis(array $dailyRecords, array $monthlyTarget, float $monthlyTargetTotal): array
    {
        $weeklyData = [];
        for ($w = 1; $w <= 5; $w++) {
            $weeklyData[$w] = array_combine(self::CATEGORIES, array_fill(0, count(self::CATEGORIES), 0.0));
        }

        foreach ($dailyRecords as $record) {
            $week = $record['week'] ?? null;
            if ($week === null || $week < 1 || $week > 5) {
                continue;
            }
            foreach (self::CATEGORIES as $cat) {
                $weeklyData[$week][$cat] += $record[$cat]['rp'];
            }
        }

        $analysis = [];
        foreach (self::CATEGORIES as $cat) {
            $target = $monthlyTarget[$cat];
            $row = ['category' => $cat, 'target' => $target, 'weeks' => []];
            for ($w = 1; $w <= 5; $w++) {
                $weekTotal = $weeklyData[$w][$cat];
                $row['weeks'][$w] = [
                    'penjualan' => $weekTotal,
                    'pct' => $target > 0 ? ($weekTotal / $target * 100) : 0.0,
                ];
            }
            $analysis[] = $row;
        }

        $totalRow = ['category' => 'total', 'target' => $monthlyTargetTotal, 'weeks' => []];
        for ($w = 1; $w <= 5; $w++) {
            $weekTotal = array_sum($weeklyData[$w]);
            $totalRow['weeks'][$w] = [
                'penjualan' => $weekTotal,
                'pct' => $monthlyTargetTotal > 0 ? ($weekTotal / $monthlyTargetTotal * 100) : 0.0,
            ];
        }
        $analysis[] = $totalRow;

        return $analysis;
    }

    private function buildDailyAnalysis(array $dailyRecords, array $dayHasData, int $workingDayCount, bool $fromLineTotal = false): array
    {
        $analysis = [];
        foreach (self::CATEGORIES as $cat) {
            $total = 0.0;
            $lineTotals = [];
            $grosses = [];
            foreach ($dailyRecords as $record) {
                $total += $record[$cat]['rp'];
                if (($dayHasData[$record['day']][$cat] ?? false)) {
                    $lineTotals[] = $record[$cat]['rp'];
                    $grosses[] = $record[$cat]['gross'];
                }
            }

            if ($fromLineTotal) {
                $terendah = $lineTotals !== [] ? min($lineTotals) : 0.0;
                $tertinggi = $lineTotals !== [] ? max($lineTotals) : 0.0;
            } else {
                $terendah = $grosses !== [] ? min($grosses) : 0.0;
                $tertinggi = $cat === 'pf'
                    ? ($lineTotals !== [] ? max($lineTotals) : 0.0)
                    : ($grosses !== [] ? max($grosses) : 0.0);
            }

            $analysis[] = [
                'category' => $cat,
                'rata_rata' => $workingDayCount > 0 ? $total / $workingDayCount : 0.0,
                'terendah' => $terendah,
                'tertinggi' => $tertinggi,
            ];
        }

        return $analysis;
    }

    private function buildGrandTotalSection(array $sections, array $dayNumbers, int $jumlahHariKerja, array $monthlyTargetGrand): array
    {
        $catTotals = $this->emptyCatTotals();
        $dayHasData = [];

        foreach ($sections as $section) {
            foreach (self::CATEGORIES as $cat) {
                $catTotals[$cat]['qty'] += $section['cat_totals'][$cat]['qty'];
                $catTotals[$cat]['rp'] += $section['cat_totals'][$cat]['rp'];
            }
            foreach ($section['day_has_data'] as $day => $cats) {
                foreach (array_keys($cats) as $cat) {
                    $dayHasData[(int) $day][$cat] = true;
                }
            }
        }

        $aggregatedDaily = [];
        foreach ($sections as $section) {
            foreach ($section['daily_records'] as $record) {
                $day = $record['day'];
                if (! isset($aggregatedDaily[$day])) {
                    $aggregatedDaily[$day] = $this->emptyCatTotals();
                    $aggregatedDaily[$day]['week'] = $record['week'] ?? null;
                }
                foreach (self::CATEGORIES as $cat) {
                    $aggregatedDaily[$day][$cat]['qty'] += $record[$cat]['qty'];
                    $aggregatedDaily[$day][$cat]['rp'] += $record[$cat]['rp'];
                    $aggregatedDaily[$day][$cat]['gross'] += $record[$cat]['gross'];
                }
            }
        }

        $totalQtyAll = 0.0;
        $totalRpAll = 0.0;
        foreach (self::CATEGORIES as $cat) {
            $totalQtyAll += $catTotals[$cat]['qty'];
            $totalRpAll += $catTotals[$cat]['rp'];
        }

        $monthlyTargetTotal = array_sum($monthlyTargetGrand);
        $targetPerHari = [];
        foreach (self::CATEGORIES as $cat) {
            $targetPerHari[$cat] = round($monthlyTargetGrand[$cat] / $jumlahHariKerja);
        }
        $targetTotalPerHari = round($monthlyTargetTotal / $jumlahHariKerja);

        $rowsByDay = [];
        $weekByDay = [];
        foreach ($aggregatedDaily as $day => $dayCat) {
            $rowsByDay[$day] = [];
            if (isset($dayCat['week'])) {
                $weekByDay[$day] = $dayCat['week'];
            }
            foreach (self::CATEGORIES as $cat) {
                $rowsByDay[$day][] = [
                    'cat' => $cat,
                    'qty' => $dayCat[$cat]['qty'],
                    'rp' => $dayCat[$cat]['rp'],
                    'gross' => $dayCat[$cat]['gross'],
                ];
            }
        }

        $dailyRecords = $this->buildDailyRecords($rowsByDay, $dayNumbers, $weekByDay, $targetPerHari, $targetTotalPerHari);

        foreach (self::CATEGORIES as $cat) {
            $catTotals[$cat]['dev'] = $monthlyTargetGrand[$cat] - $catTotals[$cat]['rp'];
        }
        $totalDev = $monthlyTargetTotal - $totalRpAll;

        $weeklyAnalysis = $this->buildWeeklyAnalysis($dailyRecords, $monthlyTargetGrand, $monthlyTargetTotal);
        $dailyAnalysis = $this->buildDailyAnalysis($dailyRecords, $dayHasData, count($dayNumbers), true);

        return [
            'sales_name' => 'Grand Total :',
            'cat_totals' => $catTotals,
            'total_qty_all' => $totalQtyAll,
            'total_rp_all' => $totalRpAll,
            'monthly_target' => $monthlyTargetGrand,
            'monthly_target_total' => $monthlyTargetTotal,
            'target_per_hari' => $targetPerHari,
            'target_total_per_hari' => $targetTotalPerHari,
            'daily_records' => $dailyRecords,
            'weekly_analysis' => $weeklyAnalysis,
            'daily_analysis' => $dailyAnalysis,
            'total_dev' => $totalDev,
            'is_grand_total' => true,
        ];
    }

    private function countWorkingDays(?Carbon $startDate, ?Carbon $endDate): int
    {
        if ($startDate === null || $endDate === null) {
            return 0;
        }

        $count = 0;
        $current = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        while ($current->lessThanOrEqualTo($end)) {
            if ($current->dayOfWeek !== Carbon::SUNDAY) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}
