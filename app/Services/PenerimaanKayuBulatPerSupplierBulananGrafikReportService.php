<?php

namespace App\Services;

use Carbon\Carbon;
use RuntimeException;

class PenerimaanKayuBulatPerSupplierBulananGrafikReportService
{
    public function __construct(
        private readonly PenerimaanKayuBulatBulananPerSupplierReportService $baseService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->baseService->fetch($startDate, $endDate);
        $grouped = [];

        foreach ($rows as $row) {
            $group = trim((string) ($row['NamaGroup'] ?? ''));
            $group = $group !== '' ? $group : 'Tanpa Group';

            $supplier = trim((string) ($row['NmSupplier'] ?? ''));
            $supplier = $supplier !== '' ? $supplier : 'Tanpa Supplier';

            $monthKey = $this->resolveMonthKey($row['Date'] ?? null);
            if ($monthKey === null) {
                continue;
            }

            $value = $this->toFloat($row['Hasil'] ?? 0) ?? 0.0;

            if (!isset($grouped[$group])) {
                $grouped[$group] = [
                    'months' => [],
                    'suppliers' => [],
                ];
            }

            $grouped[$group]['months'][$monthKey] = true;
            $grouped[$group]['suppliers'][$supplier][$monthKey] = ($grouped[$group]['suppliers'][$supplier][$monthKey] ?? 0.0) + $value;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        $groups = [];
        foreach ($grouped as $groupName => $groupData) {
            $monthKeys = array_keys($groupData['months']);
            sort($monthKeys);

            $supplierRows = [];
            foreach ($groupData['suppliers'] as $supplierName => $monthMap) {
                $monthValues = [];
                $total = 0.0;
                foreach ($monthKeys as $monthKey) {
                    $val = (float) ($monthMap[$monthKey] ?? 0.0);
                    $monthValues[$monthKey] = $val;
                    $total += $val;
                }

                if ($total <= 0.0000001) {
                    continue;
                }

                $supplierRows[] = [
                    'supplier' => $supplierName,
                    'month_values' => $monthValues,
                    'total' => $total,
                ];
            }

            usort($supplierRows, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

            $monthTotals = [];
            foreach ($monthKeys as $monthKey) {
                $monthTotals[$monthKey] = 0.0;
                foreach ($supplierRows as $supplierRow) {
                    $monthTotals[$monthKey] += (float) ($supplierRow['month_values'][$monthKey] ?? 0.0);
                }
            }

            $totals = array_column($supplierRows, 'total');
            $sumTotal = array_sum($totals);
            $avgTotal = count($totals) > 0 ? $sumTotal / count($totals) : 0.0;
            $minTotal = count($totals) > 0 ? min($totals) : 0.0;
            $maxTotal = count($totals) > 0 ? max($totals) : 0.0;

            $groups[] = [
                'name' => $groupName,
                'month_keys' => $monthKeys,
                'month_labels' => array_map(
                    static fn (string $m): string => Carbon::createFromFormat('Y-m', $m)->locale('id')->translatedFormat('M Y'),
                    $monthKeys
                ),
                'suppliers' => $supplierRows,
                'month_totals' => $monthTotals,
                'summary' => [
                    'supplier_count' => count($supplierRows),
                    'total' => $sumTotal,
                    'avg' => $avgTotal,
                    'min' => $minTotal,
                    'max' => $maxTotal,
                ],
            ];
        }

        return [
            'groups' => $groups,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'raw_row_count' => count($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        return $this->baseService->healthCheck($startDate, $endDate);
    }

    private function resolveMonthKey(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $normalized);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
