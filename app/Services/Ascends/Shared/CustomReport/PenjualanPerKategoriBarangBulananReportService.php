<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PenjualanPerKategoriBarangBulananReportService
{
    private const TITLE = 'Laporan Penjualan Per Kategori Barang Bulanan (Breakdown Perhari)';

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

        $sections = $this->buildSections($filteredRows, $jumlahHariKerja);

        // Add weekly analysis and daily analysis to each section
        foreach ($sections as &$section) {
            $section['weekly_analysis'] = $this->buildWeeklyAnalysis($section['daily_records'], $section['cat_totals']);
            $section['daily_analysis'] = $this->buildDailyAnalysis($section['daily_records']);
        }
        unset($section);

        // Calculate grand totals across all sections
        $grandTotals = [];
        foreach (['pf', 'pk1', 'pk2', 'enamel', 'fl'] as $cat) {
            $grandTotals[$cat] = ['qty' => 0.0, 'rp' => 0.0];
        }
        $grandTotalAllQty = 0;
        $grandTotalAllRp = 0;
        foreach ($sections as $section) {
            foreach (['pf', 'pk1', 'pk2', 'enamel', 'fl'] as $cat) {
                $grandTotals[$cat]['qty'] += $section['cat_totals'][$cat]['qty'];
                $grandTotals[$cat]['rp'] += $section['cat_totals'][$cat]['rp'];
            }
            $grandTotalAllQty += $section['total_qty_all'];
            $grandTotalAllRp += $section['total_rp_all'];
        }

        // Recalculate section targets and daily deviations using grand totals
        $this->recalculateSectionTargets($sections, $grandTotals, $jumlahHariKerja);

        // Build grand total if multiple sections
        $grandTotalSection = count($sections) > 1
            ? $this->buildGrandTotalSection($sections, $jumlahHariKerja)
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

    private function buildSections(array $rows, int $jumlahHariKerja): array
    {
        // Group by Salesperson (SP Name)
        $groupedBySales = [];
        foreach ($rows as $row) {
            $spName = trim((string) ($row['SP Name'] ?? $row['SP_x0020_Name'] ?? ''));
            if ($spName === '') {
                $spName = 'Tanpa Sales';
            }
            $groupedBySales[$spName][] = $row;
        }

        ksort($groupedBySales);

        $sections = [];

        foreach ($groupedBySales as $salesName => $salesRows) {
            // Collect all unique days present for this salesperson
            $days = [];
            foreach ($salesRows as $row) {
                $date = $this->parseDate((string) ($row['Date'] ?? ''));
                if ($date !== null) {
                    $dayNum = (int) $date->format('j');
                    $days[$dayNum] = true;
                }
            }

            ksort($days);
            $dayNumbers = array_keys($days);

            // Calculate totals per category for the salesperson
            $catTotals = [
                'pf' => ['qty' => 0.0, 'rp' => 0.0],   // Plastik Furniture 1 & 2
                'pk1' => ['qty' => 0.0, 'rp' => 0.0],  // Plastik Kabinet 1
                'pk2' => ['qty' => 0.0, 'rp' => 0.0],  // Plastik Kabinet 2 (Sapu)
                'enamel' => ['qty' => 0.0, 'rp' => 0.0], // Enamel
                'fl' => ['qty' => 0.0, 'rp' => 0.0],   // Furniture Lipat
            ];

            // Daily records breakdown
            $dailyRecords = [];
            $runningRp = [
                'pf' => 0.0,
                'pk1' => 0.0,
                'pk2' => 0.0,
                'enamel' => 0.0,
                'fl' => 0.0,
                'total' => 0.0,
            ];

            // Target per hari per category (Total / jumlahHariKerja)
            // Let's first accumulate total per category across all days
            $catSales = [
                'pf' => 0.0,
                'pk1' => 0.0,
                'pk2' => 0.0,
                'enamel' => 0.0,
                'fl' => 0.0,
            ];

            // Map rows by day number
            $rowsByDay = [];
            foreach ($salesRows as $row) {
                $date = $this->parseDate((string) ($row['Date'] ?? ''));
                if ($date === null) {
                    continue;
                }
                $dayNum = (int) $date->format('j');
                $rowsByDay[$dayNum][] = $row;
            }

            // Calculate overall category totals for target calculation
            foreach ($salesRows as $row) {
                $itemName = trim((string) ($row['Item Name'] ?? ''));
                $isPromo = str_starts_with(strtoupper($itemName), 'PROMO');
                $familyName = trim((string) ($row['Family Name'] ?? ''));
                $qty = (float) ($row['Quantity'] ?? 0);
                $rp = (float) ($row['LineTotal'] ?? 0);

                if (! $isPromo) {
                    if ($familyName === 'PLASTIK FURNITURE 1' || $familyName === 'PLASTIK FURNITURE 2') {
                        $catSales['pf'] += $rp;
                    } elseif ($familyName === 'PLASTIK KABINET 1') {
                        $catSales['pk1'] += $rp;
                    } elseif ($familyName === 'PLASTIK KABINET 2') {
                        $catSales['pk2'] += $rp;
                    } elseif ($familyName === 'ENAMEL') {
                        $catSales['enamel'] += $rp;
                    } elseif ($familyName === 'FURNITURE LIPAT') {
                        $catSales['fl'] += $rp;
                    }
                }
            }

            $targetPerHari = [
                'pf' => $catSales['pf'] / $jumlahHariKerja,
                'pk1' => $catSales['pk1'] / $jumlahHariKerja,
                'pk2' => $catSales['pk2'] / $jumlahHariKerja,
                'enamel' => $catSales['enamel'] / $jumlahHariKerja,
                'fl' => $catSales['fl'] / $jumlahHariKerja,
            ];
            $targetTotalPerHari = array_sum($targetPerHari);

            foreach ($dayNumbers as $dayNum) {
                $dayRows = $rowsByDay[$dayNum] ?? [];
                $dayCat = [
                    'pf' => ['qty' => 0.0, 'rp' => 0.0],
                    'pk1' => ['qty' => 0.0, 'rp' => 0.0],
                    'pk2' => ['qty' => 0.0, 'rp' => 0.0],
                    'enamel' => ['qty' => 0.0, 'rp' => 0.0],
                    'fl' => ['qty' => 0.0, 'rp' => 0.0],
                ];

                foreach ($dayRows as $row) {
                    $itemName = trim((string) ($row['Item Name'] ?? ''));
                    $isPromo = str_starts_with(strtoupper($itemName), 'PROMO');
                    $familyName = trim((string) ($row['Family Name'] ?? ''));
                    $qty = (float) ($row['Quantity'] ?? 0);
                    $rp = (float) ($row['LineTotal'] ?? 0);

                    if (! $isPromo) {
                        if ($familyName === 'PLASTIK FURNITURE 1' || $familyName === 'PLASTIK FURNITURE 2') {
                            $dayCat['pf']['qty'] += $qty;
                            $dayCat['pf']['rp'] += $rp;
                        } elseif ($familyName === 'PLASTIK KABINET 1') {
                            $dayCat['pk1']['qty'] += $qty;
                            $dayCat['pk1']['rp'] += $rp;
                        } elseif ($familyName === 'PLASTIK KABINET 2') {
                            $dayCat['pk2']['qty'] += $qty;
                            $dayCat['pk2']['rp'] += $rp;
                        } elseif ($familyName === 'ENAMEL') {
                            $dayCat['enamel']['qty'] += $qty;
                            $dayCat['enamel']['rp'] += $rp;
                        } elseif ($familyName === 'FURNITURE LIPAT') {
                            $dayCat['fl']['qty'] += $qty;
                            $dayCat['fl']['rp'] += $rp;
                        }
                    }
                }

                // Update running totals
                $runningRp['pf'] += $dayCat['pf']['rp'];
                $runningRp['pk1'] += $dayCat['pk1']['rp'];
                $runningRp['pk2'] += $dayCat['pk2']['rp'];
                $runningRp['enamel'] += $dayCat['enamel']['rp'];
                $runningRp['fl'] += $dayCat['fl']['rp'];

                $dayTotalRp = $dayCat['pf']['rp'] + $dayCat['pk1']['rp'] + $dayCat['pk2']['rp'] + $dayCat['enamel']['rp'] + $dayCat['fl']['rp'];
                $dayTotalQty = $dayCat['pf']['qty'] + $dayCat['pk1']['qty'] + $dayCat['pk2']['qty'] + $dayCat['enamel']['qty'] + $dayCat['fl']['qty'];
                $runningRp['total'] += $dayTotalRp;

                // Lebih (Kurang) = Running Rp - (Target Perhari * dayIndex or cumulative target)
                // In Crystal Reports formula: xLebihKurangPEnamel = RpEnamel - (TargetPerhariEnamel * TotalHari)
                $lebihKurangPf = $runningRp['pf'] - ($targetPerHari['pf'] * $dayNum);
                $lebihKurangPk1 = $runningRp['pk1'] - ($targetPerHari['pk1'] * $dayNum);
                $lebihKurangPk2 = $runningRp['pk2'] - ($targetPerHari['pk2'] * $dayNum);
                $lebihKurangEnamel = $runningRp['enamel'] - ($targetPerHari['enamel'] * $dayNum);
                $lebihKurangFl = $runningRp['fl'] - ($targetPerHari['fl'] * $dayNum);
                $lebihKurangTotal = $runningRp['total'] - ($targetTotalPerHari * $dayNum);

                // Accumulate category totals
                $catTotals['pf']['qty'] += $dayCat['pf']['qty'];
                $catTotals['pf']['rp'] += $dayCat['pf']['rp'];
                $catTotals['pk1']['qty'] += $dayCat['pk1']['qty'];
                $catTotals['pk1']['rp'] += $dayCat['pk1']['rp'];
                $catTotals['pk2']['qty'] += $dayCat['pk2']['qty'];
                $catTotals['pk2']['rp'] += $dayCat['pk2']['rp'];
                $catTotals['enamel']['qty'] += $dayCat['enamel']['qty'];
                $catTotals['enamel']['rp'] += $dayCat['enamel']['rp'];
                $catTotals['fl']['qty'] += $dayCat['fl']['qty'];
                $catTotals['fl']['rp'] += $dayCat['fl']['rp'];

                $dailyRecords[] = [
                    'day' => $dayNum,
                    'pf' => $dayCat['pf'],
                    'pf_running' => $runningRp['pf'],
                    'pf_dev' => $lebihKurangPf,
                    'pk1' => $dayCat['pk1'],
                    'pk1_running' => $runningRp['pk1'],
                    'pk1_dev' => $lebihKurangPk1,
                    'pk2' => $dayCat['pk2'],
                    'pk2_running' => $runningRp['pk2'],
                    'pk2_dev' => $lebihKurangPk2,
                    'enamel' => $dayCat['enamel'],
                    'enamel_running' => $runningRp['enamel'],
                    'enamel_dev' => $lebihKurangEnamel,
                    'fl' => $dayCat['fl'],
                    'fl_running' => $runningRp['fl'],
                    'fl_dev' => $lebihKurangFl,
                    'total_qty' => $dayTotalQty,
                    'total_rp' => $dayTotalRp,
                    'total_running' => $runningRp['total'],
                    'total_dev' => $lebihKurangTotal,
                ];
            }

            $totalQtyAll = $catTotals['pf']['qty'] + $catTotals['pk1']['qty'] + $catTotals['pk2']['qty'] + $catTotals['enamel']['qty'] + $catTotals['fl']['qty'];
            $totalRpAll = $catTotals['pf']['rp'] + $catTotals['pk1']['rp'] + $catTotals['pk2']['rp'] + $catTotals['enamel']['rp'] + $catTotals['fl']['rp'];

            $sections[] = [
                'sales_name' => $salesName,
                'cat_totals' => $catTotals,
                'total_qty_all' => $totalQtyAll,
                'total_rp_all' => $totalRpAll,
                'target_per_hari' => $targetPerHari,
                'target_total_per_hari' => $targetTotalPerHari,
                'daily_records' => $dailyRecords,
            ];
        }

        return $sections;
    }

    private function recalculateSectionTargets(array &$sections, array $grandTotals, int $jumlahHariKerja): void
    {
        $grandTargetPerHari = [];
        foreach (['pf', 'pk1', 'pk2', 'enamel', 'fl'] as $cat) {
            $grandTargetPerHari[$cat] = $grandTotals[$cat]['rp'] / $jumlahHariKerja;
        }
        $grandTargetTotalPerHari = array_sum($grandTargetPerHari);

        foreach ($sections as &$section) {
            $section['target_per_hari'] = $grandTargetPerHari;
            $section['target_total_per_hari'] = $grandTargetTotalPerHari;

            $runningRp = [
                'pf' => 0.0, 'pk1' => 0.0, 'pk2' => 0.0,
                'enamel' => 0.0, 'fl' => 0.0, 'total' => 0.0,
            ];

            foreach ($section['daily_records'] as &$rec) {
                $runningRp['pf'] += $rec['pf']['rp'];
                $runningRp['pk1'] += $rec['pk1']['rp'];
                $runningRp['pk2'] += $rec['pk2']['rp'];
                $runningRp['enamel'] += $rec['enamel']['rp'];
                $runningRp['fl'] += $rec['fl']['rp'];
                $runningRp['total'] += $rec['total_rp'];

                $rec['pf_dev'] = $runningRp['pf'] - ($grandTargetPerHari['pf'] * $rec['day']);
                $rec['pk1_dev'] = $runningRp['pk1'] - ($grandTargetPerHari['pk1'] * $rec['day']);
                $rec['pk2_dev'] = $runningRp['pk2'] - ($grandTargetPerHari['pk2'] * $rec['day']);
                $rec['enamel_dev'] = $runningRp['enamel'] - ($grandTargetPerHari['enamel'] * $rec['day']);
                $rec['fl_dev'] = $runningRp['fl'] - ($grandTargetPerHari['fl'] * $rec['day']);
                $rec['total_dev'] = $runningRp['total'] - ($grandTargetTotalPerHari * $rec['day']);

                $rec['pf_running'] = $runningRp['pf'];
                $rec['pk1_running'] = $runningRp['pk1'];
                $rec['pk2_running'] = $runningRp['pk2'];
                $rec['enamel_running'] = $runningRp['enamel'];
                $rec['fl_running'] = $runningRp['fl'];
                $rec['total_running'] = $runningRp['total'];
            }
            unset($rec);
        }
        unset($section);
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

    private function getWeekNumber(int $day): int
    {
        if ($day <= 6) {
            return 1;
        }
        if ($day <= 13) {
            return 2;
        }
        if ($day <= 20) {
            return 3;
        }
        if ($day <= 27) {
            return 4;
        }

        return 5;
    }

    private function buildWeeklyAnalysis(array $dailyRecords, array $catTotals): array
    {
        $categories = ['enamel', 'fl', 'pf', 'pk1', 'pk2'];
        $weeklyData = [];

        for ($w = 1; $w <= 5; $w++) {
            $weeklyData[$w] = array_combine($categories, array_fill(0, 5, 0.0));
        }

        foreach ($dailyRecords as $rec) {
            $weekNum = $this->getWeekNumber((int) $rec['day']);
            foreach ($categories as $cat) {
                $weeklyData[$weekNum][$cat] += $rec[$cat]['rp'];
            }
        }

        $analysis = [];
        foreach ($categories as $cat) {
            $catTotal = $catTotals[$cat]['rp'];
            $row = ['category' => $cat, 'total' => $catTotal, 'weeks' => []];
            for ($w = 1; $w <= 5; $w++) {
                $weekTotal = $weeklyData[$w][$cat];
                $pct = $catTotal > 0 ? ($weekTotal / $catTotal * 100) : 0;
                $row['weeks'][$w] = ['penjualan' => $weekTotal, 'pct' => $pct];
            }
            $analysis[] = $row;
        }

        // Total row
        $grandTotal = 0.0;
        foreach ($categories as $cat) {
            $grandTotal += $catTotals[$cat]['rp'];
        }
        $totalRow = ['category' => 'total', 'total' => $grandTotal, 'weeks' => []];
        for ($w = 1; $w <= 5; $w++) {
            $weekTotal = array_sum($weeklyData[$w]);
            $pct = $grandTotal > 0 ? ($weekTotal / $grandTotal * 100) : 0;
            $totalRow['weeks'][$w] = ['penjualan' => $weekTotal, 'pct' => $pct];
        }
        $analysis[] = $totalRow;

        return $analysis;
    }

    private function buildDailyAnalysis(array $dailyRecords): array
    {
        $categories = ['enamel', 'fl', 'pf', 'pk1', 'pk2'];
        $analysis = [];

        foreach ($categories as $cat) {
            $values = array_map(fn ($r) => $r[$cat]['rp'], $dailyRecords);
            $nonZero = array_values(array_filter($values, fn ($v) => $v > 0));
            $total = array_sum($values);
            $count = count($values);

            $analysis[] = [
                'category' => $cat,
                'rata_rata' => $count > 0 ? $total / $count : 0,
                'terendah' => $nonZero !== [] ? min($nonZero) : 0,
                'tertinggi' => $count > 0 ? max($values) : 0,
            ];
        }

        // Total row
        $totalValues = array_map(fn ($r) => $r['total_rp'], $dailyRecords);
        $nonZeroTotal = array_values(array_filter($totalValues, fn ($v) => $v > 0));
        $grandTotal = array_sum($totalValues);
        $count = count($totalValues);

        $analysis[] = [
            'category' => 'total',
            'rata_rata' => $count > 0 ? $grandTotal / $count : 0,
            'terendah' => $nonZeroTotal !== [] ? min($nonZeroTotal) : 0,
            'tertinggi' => $count > 0 ? max($totalValues) : 0,
        ];

        return $analysis;
    }

    private function buildGrandTotalSection(array $sections, int $jumlahHariKerja): array
    {
        $categories = ['enamel', 'fl', 'pf', 'pk1', 'pk2'];

        $catTotals = [];
        $targetPerHari = [];
        foreach ($categories as $cat) {
            $catTotals[$cat] = ['qty' => 0.0, 'rp' => 0.0];
            $targetPerHari[$cat] = 0.0;
        }

        $aggregatedDaily = [];

        foreach ($sections as $section) {
            foreach ($categories as $cat) {
                $catTotals[$cat]['qty'] += $section['cat_totals'][$cat]['qty'];
                $catTotals[$cat]['rp'] += $section['cat_totals'][$cat]['rp'];
            }
            foreach ($section['daily_records'] as $rec) {
                $day = $rec['day'];
                if (! isset($aggregatedDaily[$day])) {
                    $aggregatedDaily[$day] = [];
                    foreach ($categories as $cat) {
                        $aggregatedDaily[$day][$cat] = ['qty' => 0.0, 'rp' => 0.0];
                    }
                }
                foreach ($categories as $cat) {
                    $aggregatedDaily[$day][$cat]['qty'] += $rec[$cat]['qty'];
                    $aggregatedDaily[$day][$cat]['rp'] += $rec[$cat]['rp'];
                }
            }
        }

        foreach ($categories as $cat) {
            $targetPerHari[$cat] = $catTotals[$cat]['rp'] / $jumlahHariKerja;
        }
        $targetTotalPerHari = array_sum($targetPerHari);

        ksort($aggregatedDaily);

        $dailyRecords = [];
        $runningRp = array_merge(array_combine($categories, array_fill(0, 5, 0.0)), ['total' => 0.0]);

        foreach ($aggregatedDaily as $dayNum => $dayCat) {
            foreach ($categories as $cat) {
                $runningRp[$cat] += $dayCat[$cat]['rp'];
            }

            $dayTotalRp = 0.0;
            $dayTotalQty = 0.0;
            foreach ($categories as $cat) {
                $dayTotalRp += $dayCat[$cat]['rp'];
                $dayTotalQty += $dayCat[$cat]['qty'];
            }
            $runningRp['total'] += $dayTotalRp;

            $lebihKurangPf = $runningRp['pf'] - ($targetPerHari['pf'] * $dayNum);
            $lebihKurangPk1 = $runningRp['pk1'] - ($targetPerHari['pk1'] * $dayNum);
            $lebihKurangPk2 = $runningRp['pk2'] - ($targetPerHari['pk2'] * $dayNum);
            $lebihKurangEnamel = $runningRp['enamel'] - ($targetPerHari['enamel'] * $dayNum);
            $lebihKurangFl = $runningRp['fl'] - ($targetPerHari['fl'] * $dayNum);
            $lebihKurangTotal = $runningRp['total'] - ($targetTotalPerHari * $dayNum);

            $dailyRecords[] = [
                'day' => $dayNum,
                'pf' => $dayCat['pf'],
                'pf_running' => $runningRp['pf'],
                'pf_dev' => $lebihKurangPf,
                'pk1' => $dayCat['pk1'],
                'pk1_running' => $runningRp['pk1'],
                'pk1_dev' => $lebihKurangPk1,
                'pk2' => $dayCat['pk2'],
                'pk2_running' => $runningRp['pk2'],
                'pk2_dev' => $lebihKurangPk2,
                'enamel' => $dayCat['enamel'],
                'enamel_running' => $runningRp['enamel'],
                'enamel_dev' => $lebihKurangEnamel,
                'fl' => $dayCat['fl'],
                'fl_running' => $runningRp['fl'],
                'fl_dev' => $lebihKurangFl,
                'total_qty' => $dayTotalQty,
                'total_rp' => $dayTotalRp,
                'total_running' => $runningRp['total'],
                'total_dev' => $lebihKurangTotal,
            ];
        }

        $totalQtyAll = array_sum(array_map(fn ($d) => $d['total_qty'], $dailyRecords));
        $totalRpAll = array_sum(array_map(fn ($d) => $d['total_rp'], $dailyRecords));

        $weeklyAnalysis = $this->buildWeeklyAnalysis($dailyRecords, $catTotals);
        $dailyAnalysis = $this->buildDailyAnalysis($dailyRecords);

        return [
            'sales_name' => 'Grand Total :',
            'cat_totals' => $catTotals,
            'total_qty_all' => $totalQtyAll,
            'total_rp_all' => $totalRpAll,
            'target_per_hari' => $targetPerHari,
            'target_total_per_hari' => $targetTotalPerHari,
            'daily_records' => $dailyRecords,
            'weekly_analysis' => $weeklyAnalysis,
            'daily_analysis' => $dailyAnalysis,
            'is_grand_total' => true,
        ];
    }
}
