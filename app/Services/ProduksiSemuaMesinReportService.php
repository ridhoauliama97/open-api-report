<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProduksiSemuaMesinReportService
{
    private const EXPECTED_COLUMNS = [
        'Tanggal',
        'NamaMesin',
        'Target',
        'TargetRend',
        'Input',
        'Output',
        'Rend',
        'RendPerMesin',
    ];

    private const MACHINE_ORDER = [
        'S4S LINE 1',
        'MULTI RIPSAW',
        'FINGER JOINT 1',
        'FINGER JOINT 2',
        'FINGER JOINT 3',
        'MOULDING 1',
        'MOULDING 2',
        'ROTARY COMPOSER 1',
        'ROTARY COMPOSER 2',
        'CROSSCUT AKHIR',
        'DOUBLE END CUTTER',
        'SANDING',
        'PACKING',
    ];

    private const MACHINE_LABELS = [
        'S4S LINE 1' => 'S4S LINE 1',
        'MULTI RIPSAW' => 'MULTI RIPSAW',
        'FINGER JOINT 1' => 'FINGER JOINT 1',
        'FINGER JOINT 2' => 'FINGER JOINT 2',
        'FINGER JOINT 3' => 'FINGER JOINT 3',
        'MOULDING 1' => 'MOULDING 1',
        'MOULDING 2' => 'MOULDING 2',
        'ROTARY COMPOSER 1' => 'ROTARY COMPOSER 1',
        'ROTARY COMPOSER 2' => 'ROTARY COMPOSER 2',
        'CROSSCUT AKHIR' => 'CROSSCUT AKHIR',
        'DOUBLE END CUTTER' => 'DOUBLE END CUTTER',
        'SANDING' => 'SANDING',
        'PACKING' => 'PACKING',
    ];

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetchRows($startDate, $endDate);
        $machineMap = [];
        $dailyLabels = [];

        foreach ($rows as $row) {
            $machineName = trim((string) ($row['NamaMesin'] ?? ''));
            $dateValue = trim((string) ($row['Tanggal'] ?? ''));

            if ($machineName === '' || $dateValue === '') {
                continue;
            }

            $dayLabel = Carbon::parse($dateValue)->format('d');
            $dailyLabels[$dayLabel] = true;

            if (!isset($machineMap[$machineName])) {
                $machineMap[$machineName] = [
                    'name' => $machineName,
                    'label' => self::MACHINE_LABELS[$machineName] ?? $machineName,
                    'target' => null,
                    'days' => [],
                    'non_zero_outputs' => [],
                ];
            }

            $target = $this->nullableFloat($row['Target'] ?? null);
            $output = $this->nullableFloat($row['Output'] ?? null);

            if ($machineMap[$machineName]['target'] === null && $target !== null) {
                $machineMap[$machineName]['target'] = $target;
            }

            $machineMap[$machineName]['days'][$dayLabel] = $output;

            if ($output !== null && abs($output) > 0.0000001) {
                $machineMap[$machineName]['non_zero_outputs'][] = $output;
            }
        }

        $orderedMachines = [];
        foreach (self::MACHINE_ORDER as $machineName) {
            if (isset($machineMap[$machineName])) {
                $orderedMachines[] = $machineMap[$machineName];
            }
        }

        foreach ($machineMap as $machineName => $machine) {
            if (!in_array($machineName, self::MACHINE_ORDER, true)) {
                $orderedMachines[] = $machine;
            }
        }

        ksort($dailyLabels, SORT_NATURAL);
        $dayRows = [];

        foreach (array_keys($dailyLabels) as $dayLabel) {
            $cells = [];
            foreach ($orderedMachines as $machine) {
                $cells[$machine['name']] = $machine['days'][$dayLabel] ?? null;
            }

            $dayRows[] = [
                'label' => $dayLabel,
                'cells' => $cells,
            ];
        }

        $statRows = [];
        foreach (['Total', 'Avg', 'Min', 'Max', 'Target'] as $label) {
            $cells = [];

            foreach ($orderedMachines as $machine) {
                $values = $machine['non_zero_outputs'];
                $value = null;

                if ($label === 'Total') {
                    $value = $values !== [] ? array_sum($values) : null;
                } elseif ($label === 'Avg') {
                    $value = $values !== [] ? array_sum($values) / count($values) : null;
                } elseif ($label === 'Min') {
                    $value = $values !== [] ? min($values) : null;
                } elseif ($label === 'Max') {
                    $value = $values !== [] ? max($values) : null;
                } elseif ($label === 'Target') {
                    $value = $machine['target'];
                }

                $cells[$machine['name']] = $value;
            }

            $statRows[] = [
                'label' => $label,
                'cells' => $cells,
            ];
        }

        return [
            'columns' => array_map(
                static fn(array $machine): array => [
                    'key' => $machine['name'],
                    'label' => $machine['label'],
                ],
                $orderedMachines
            ),
            'rows' => $dayRows,
            'stat_rows' => $statRows,
            'summary' => [
                'machine_count' => count($orderedMachines),
                'row_count' => count($rows),
                'day_count' => count($dayRows),
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
            'machine_count' => (int) ($reportData['summary']['machine_count'] ?? 0),
            'day_count' => (int) ($reportData['summary']['day_count'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.produksi_semua_mesin.database_connection');
        $procedure = (string) config('reports.produksi_semua_mesin.stored_procedure', 'SPWps_LapProduksiSemuaMesin');

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
}
