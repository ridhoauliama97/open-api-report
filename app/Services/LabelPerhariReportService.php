<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class LabelPerhariReportService
{
    private const EXPECTED_COLUMNS = [
        'NoLabel',
        'NoUrut',
        'NoSPK',
        'NoSPKAsal',
        'Mesin',
        'Jenis',
        'Tebal',
        'Lebar',
        'Panjang',
        'JmlhBatang',
        'Berat',
        'Ket',
    ];

    private const CATEGORY_ORDER = ['ST', 'S4S', 'FJ', 'MLD', 'LMT', 'CCA', 'SAND', 'BJ'];

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetchRows($startDate, $endDate);
        $grouped = [];

        foreach ($rows as $row) {
            $ket = trim((string) ($row['Ket'] ?? ''));
            $category = $ket !== '' ? $ket : 'LAINNYA';
            $noLabel = trim((string) ($row['NoLabel'] ?? ''));
            $labelKey = $noLabel !== '' ? $noLabel : '__EMPTY__:' . md5(json_encode($row));

            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'name' => $category,
                    'label_groups' => [],
                    'total_pcs' => 0.0,
                    'total_berat' => 0.0,
                ];
            }

            $pcs = $this->nullableFloat($row['JmlhBatang'] ?? null);
            $berat = $this->nullableFloat($row['Berat'] ?? null);

            if (!isset($grouped[$category]['label_groups'][$labelKey])) {
                $grouped[$category]['label_groups'][$labelKey] = [
                    'NoLabel' => $noLabel,
                    'NoUrutValues' => [],
                    'NoSPK' => trim((string) ($row['NoSPK'] ?? '')),
                    'NoSPKAsal' => trim((string) ($row['NoSPKAsal'] ?? '')),
                    'MesinValues' => [],
                    'JenisValues' => [],
                    'TebalValues' => [],
                    'LebarValues' => [],
                    'PanjangValues' => [],
                    'JmlhBatang' => 0.0,
                    'Berat' => 0.0,
                    'Ket' => $category,
                    'DetailCount' => 0,
                ];
            }

            $labelGroup = &$grouped[$category]['label_groups'][$labelKey];
            $labelGroup['NoSPK'] = $labelGroup['NoSPK'] !== '' ? $labelGroup['NoSPK'] : trim((string) ($row['NoSPK'] ?? ''));
            $labelGroup['NoSPKAsal'] = $labelGroup['NoSPKAsal'] !== '' ? $labelGroup['NoSPKAsal'] : trim((string) ($row['NoSPKAsal'] ?? ''));
            $this->pushUniqueFormattedValue($labelGroup['NoUrutValues'], $this->nullableFloat($row['NoUrut'] ?? null), 0);
            $this->pushUniqueString($labelGroup['MesinValues'], trim((string) ($row['Mesin'] ?? '')));
            $this->pushUniqueString($labelGroup['JenisValues'], trim((string) ($row['Jenis'] ?? '')));
            $this->pushUniqueFormattedValue($labelGroup['TebalValues'], $this->nullableFloat($row['Tebal'] ?? null), 0);
            $this->pushUniqueFormattedValue($labelGroup['LebarValues'], $this->nullableFloat($row['Lebar'] ?? null), 0);
            $this->pushUniqueFormattedValue($labelGroup['PanjangValues'], $this->nullableFloat($row['Panjang'] ?? null), 1);
            $labelGroup['JmlhBatang'] += $pcs ?? 0.0;
            $labelGroup['Berat'] += $berat ?? 0.0;
            $labelGroup['DetailCount']++;
            unset($labelGroup);

            $grouped[$category]['total_pcs'] += $pcs ?? 0.0;
            $grouped[$category]['total_berat'] += $berat ?? 0.0;
        }

        $orderedCategories = [];
        foreach (self::CATEGORY_ORDER as $category) {
            if (isset($grouped[$category])) {
                $orderedCategories[] = $grouped[$category];
            }
        }

        foreach ($grouped as $category => $group) {
            if (!in_array($category, self::CATEGORY_ORDER, true)) {
                $orderedCategories[] = $group;
            }
        }

        $summaryRows = [];
        $grandTotals = [
            'pcs' => 0.0,
            'berat' => 0.0,
            'row_count' => count($rows),
            'label_count' => 0,
        ];

        foreach ($orderedCategories as $index => &$category) {
            $category['rows'] = array_map(function (array $labelGroup): array {
                return [
                    'NoLabel' => $labelGroup['NoLabel'],
                    'NoUrut' => $this->implodeValues($labelGroup['NoUrutValues']),
                    'NoSPK' => $labelGroup['NoSPK'],
                    'NoSPKAsal' => $labelGroup['NoSPKAsal'],
                    'Mesin' => $this->implodeValues($labelGroup['MesinValues']),
                    'Jenis' => $this->implodeValues($labelGroup['JenisValues']),
                    'Tebal' => $this->implodeValues($labelGroup['TebalValues']),
                    'Lebar' => $this->implodeValues($labelGroup['LebarValues']),
                    'Panjang' => $this->implodeValues($labelGroup['PanjangValues']),
                    'JmlhBatang' => $labelGroup['JmlhBatang'],
                    'Berat' => $labelGroup['Berat'],
                    'Ket' => $labelGroup['Ket'],
                    'DetailCount' => $labelGroup['DetailCount'],
                ];
            }, array_values($category['label_groups']));

            usort($category['rows'], static function (array $left, array $right): int {
                $labelCompare = strcmp((string) ($left['NoLabel'] ?? ''), (string) ($right['NoLabel'] ?? ''));
                if ($labelCompare !== 0) {
                    return $labelCompare;
                }

                return strcmp((string) ($left['NoUrut'] ?? ''), (string) ($right['NoUrut'] ?? ''));
            });

            $category['no'] = $index + 1;

            $summaryRows[] = [
                'No' => $index + 1,
                'Kategori' => $category['name'],
                'LabelCount' => count($category['rows']),
                'RowCount' => count($category['rows']),
                'TotalPcs' => $category['total_pcs'],
                'TotalBerat' => $category['total_berat'],
            ];

            $grandTotals['pcs'] += $category['total_pcs'];
            $grandTotals['berat'] += $category['total_berat'];
            $grandTotals['label_count'] += count($category['rows']);
        }
        unset($category);

        return [
            'categories' => $orderedCategories,
            'summary_rows' => $summaryRows,
            'grand_totals' => $grandTotals,
            'summary' => [
                'category_count' => count($orderedCategories),
                'row_count' => count($rows),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetchRows($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $missingColumns = array_values(array_diff(self::EXPECTED_COLUMNS, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, self::EXPECTED_COLUMNS));
        $reportData = $this->buildReportData($startDate, $endDate);

        return [
            'is_healthy' => $missingColumns === [],
            'expected_columns' => self::EXPECTED_COLUMNS,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
            'category_count' => (int) ($reportData['summary']['category_count'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.label_perhari.database_connection');
        $procedure = (string) config('reports.label_perhari.stored_procedure', 'SPWps_LapLabelPerhari');

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $rows = DB::connection($connectionName ?: null)
            ->select("EXEC {$procedure} ?, ?", [$startDate, $endDate]);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param array<int, string> $bucket
     */
    private function pushUniqueString(array &$bucket, string $value): void
    {
        if ($value === '' || in_array($value, $bucket, true)) {
            return;
        }

        $bucket[] = $value;
    }

    /**
     * @param array<int, string> $bucket
     */
    private function pushUniqueFormattedValue(array &$bucket, ?float $value, int $decimals): void
    {
        if ($value === null) {
            return;
        }

        $formatted = number_format($value, $decimals, '.', ',');
        if (!in_array($formatted, $bucket, true)) {
            $bucket[] = $formatted;
        }
    }

    /**
     * @param array<int, string> $values
     */
    private function implodeValues(array $values): string
    {
        return $values === [] ? '-' : implode(' / ', $values);
    }
}
