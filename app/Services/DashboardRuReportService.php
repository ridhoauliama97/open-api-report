<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DashboardRuReportService
{
    private const GROUP_DEFINITIONS = [
        [
            'source' => 'Penerimaan Kayu Bulat',
            'label' => 'Penerimaan Kayu Bulat',
            'subs' => ['JB', 'JTG', 'PL', 'RB'],
        ],
        [
            'source' => 'Saldo ST PBL Hidup',
            'label' => 'ST PBL<br>Hidup',
            'subs' => ['RB'],
        ],
        [
            'source' => 'Stock Kayu Bulat Hidup',
            'label' => 'Stock Kayu Bulat Hidup',
            'subs' => ['JB', 'JB-UR', 'JB-UT', 'JTG', 'JTG-UR', 'JTG-UT', 'PL', 'PL-UR', 'PL-UT', 'RB', 'RB-UR', 'RB-UT'],
        ],
        [
            'source' => 'Kiln & Dryer',
            'label' => 'Saldo Kiln &amp; Dryer',
            'subs' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10'],
        ],
        [
            'source' => 'Sawmill Bansaw',
            'label' => 'Sawmill Bansaw',
            'subs' => ['Meja', 'Ton', '-/+MJ'],
        ],
        [
            'source' => 'Sawmill SLP',
            'label' => 'Sawmill SLP',
            'subs' => ['Meja', 'H.M', 'Ton'],
        ],
        [
            'source' => 'Sawmill SLP 1',
            'label' => 'Sawmill SLP 1',
            'subs' => ['Meja', 'H.M', 'Btg'],
        ],
        [
            'source' => 'Vacuum Tube 1',
            'label' => 'Vacuum Tube 1',
            'subs' => ['Chr', 'Mnt'],
        ],
        [
            'source' => 'Vacuum Tube 2',
            'label' => 'Vacuum Tube 2',
            'subs' => ['Chr', 'Mnt'],
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $reportDate): array
    {
        $rows = $this->fetchRows($reportDate);
        $matrix = [];

        foreach ($rows as $row) {
            $tanggal = trim((string) ($row['Tanggal'] ?? ''));
            $group = trim((string) ($row['Seleksi_1'] ?? ''));
            $sub = trim((string) ($row['Seleksi_1_Isi'] ?? ''));
            $value = $this->normalizeValue($row['ValueNya'] ?? null);

            if ($tanggal === '' || $group === '' || $sub === '') {
                continue;
            }

            $matrix[$tanggal][$group][$sub] = $value;
        }

        $subColumns = $this->buildSubColumns();
        $orderedRowLabels = $this->buildRowLabels(array_keys($matrix));
        $normalizedRows = [];

        foreach ($orderedRowLabels as $label) {
            $cells = [];

            foreach ($subColumns as $column) {
                $cells[$column['key']] = $matrix[$label][$column['group_source']][$column['label']] ?? '';
            }

            $normalizedRows[] = [
                'label' => $label,
                'is_footer' => in_array($label, ['G.T.', 'AVG'], true),
                'cells' => $cells,
            ];
        }

        $grandTotalRow = null;
        foreach ($normalizedRows as $row) {
            if (($row['label'] ?? '') === 'G.T.') {
                $grandTotalRow = $row;
                break;
            }
        }

        $stockNonPulaiTronton = $this->calculateStockNonPulaiTronton($grandTotalRow['cells'] ?? []);

        return [
            'period_label' => Carbon::parse($reportDate)->locale('id')->translatedFormat('F Y'),
            'report_date' => $reportDate,
            'column_groups' => array_map(
                static fn(array $group): array => [
                    'label' => $group['label'],
                    'span' => count($group['subs']),
                ],
                self::GROUP_DEFINITIONS
            ),
            'sub_columns' => $subColumns,
            'rows' => $normalizedRows,
            'summary_lines' => [
                ['label' => 'Stock KB Non Pulai', 'value' => '(tronton)'],
                ['label' => 'Total', 'value' => $this->formatSummaryDecimal($stockNonPulaiTronton)],
            ],
            'summary' => [
                'row_count' => count($normalizedRows),
                'daily_row_count' => count(array_filter($normalizedRows, static fn(array $row): bool => !$row['is_footer'])),
                'group_count' => count(self::GROUP_DEFINITIONS),
                'sub_column_count' => count($subColumns),
                'stock_kb_non_pulai_tronton' => $stockNonPulaiTronton,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $reportDate): array
    {
        $rows = $this->fetchRows($reportDate);
        $detectedGroups = [];
        $detectedColumns = [];

        foreach ($rows as $row) {
            $group = trim((string) ($row['Seleksi_1'] ?? ''));
            $sub = trim((string) ($row['Seleksi_1_Isi'] ?? ''));

            if ($group === '' || $sub === '') {
                continue;
            }

            $detectedGroups[$group] = true;
            $detectedColumns[$group][] = $sub;
        }

        $expectedGroups = array_map(static fn(array $group): string => $group['source'], self::GROUP_DEFINITIONS);
        $missingGroups = array_values(array_diff($expectedGroups, array_keys($detectedGroups)));
        $expectedColumns = [];
        $missingColumns = [];

        foreach (self::GROUP_DEFINITIONS as $group) {
            $expectedColumns[$group['source']] = $group['subs'];
            $detected = array_values(array_unique($detectedColumns[$group['source']] ?? []));
            $missing = array_values(array_diff($group['subs'], $detected));

            if ($missing !== []) {
                $missingColumns[$group['source']] = $missing;
            }
        }

        return [
            'is_healthy' => $missingGroups === [] && $missingColumns === [],
            'expected_groups' => $expectedGroups,
            'missing_groups' => $missingGroups,
            'expected_columns' => $expectedColumns,
            'missing_columns' => $missingColumns,
            'row_count' => count($rows),
            'group_count' => count($detectedGroups),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(string $reportDate): array
    {
        $connectionName = config('reports.dashboard_ru.database_connection');
        $procedure = (string) config('reports.dashboard_ru.stored_procedure', 'SP_LapProduktivitasDashboard');

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $rows = DB::connection($connectionName ?: null)->select("EXEC {$procedure} ?", [$reportDate]);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @return array<int, array{key:string,group_source:string,label:string}>
     */
    private function buildSubColumns(): array
    {
        $columns = [];

        foreach (self::GROUP_DEFINITIONS as $group) {
            foreach ($group['subs'] as $sub) {
                $columns[] = [
                    'key' => $group['source'] . '::' . $sub,
                    'group_source' => $group['source'],
                    'label' => $sub,
                ];
            }
        }

        return $columns;
    }

    /**
     * @param  array<int, string>  $labels
     * @return array<int, string>
     */
    private function buildRowLabels(array $labels): array
    {
        $daily = [];
        $footer = [];

        foreach ($labels as $label) {
            if (preg_match('/^\d{2}$/', $label) === 1) {
                $daily[] = $label;
                continue;
            }

            $footer[] = $label;
        }

        sort($daily, SORT_NATURAL);

        $orderedFooter = [];
        foreach (['G.T.', 'AVG'] as $label) {
            if (in_array($label, $footer, true)) {
                $orderedFooter[] = $label;
            }
        }

        return array_values(array_merge($daily, $orderedFooter));
    }

    private function normalizeValue(mixed $value): string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '0' ? '' : $normalized;
    }

    /**
     * @param  array<string, string>  $cells
     */
    private function calculateStockNonPulaiTronton(array $cells): float
    {
        $rb = $this->parseNumeric($cells['Stock Kayu Bulat Hidup::RB'] ?? null);

        return $rb / 100;
    }

    private function parseNumeric(mixed $value): float
    {
        $normalized = trim((string) ($value ?? ''));

        if ($normalized === '') {
            return 0.0;
        }

        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function formatSummaryDecimal(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}
