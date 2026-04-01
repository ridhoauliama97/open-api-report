<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProduksiHuluHilirReportService
{
    private const EXPECTED_COLUMNS = [
        'Tanggal',
        'NamaMesin',
        'Target',
        'Input',
        'Output',
        'Rend',
        'RendPerMesin',
        'Tebal',
    ];

    private const MACHINE_ORDER = [
        'S4S LINE 1',
        'MULTI RIPSAW',
        'FINGER JOINT 1',
        'FINGER JOINT 2',
        'FINGER JOINT 3',
        'MOULDING 1',
        'MOULDING 2',
        'ROTARY COMPOSER 1 Shift 1',
        'ROTARY COMPOSER 1 Shift 2',
        'ROTARY COMPOSER 2 Shift 1',
        'ROTARY COMPOSER 2 Shift 2',
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
        'ROTARY COMPOSER 1 Shift 1' => 'ROTARY COMPOSER 1<br>Shift 1',
        'ROTARY COMPOSER 1 Shift 2' => 'ROTARY COMPOSER 1<br>Shift 2',
        'ROTARY COMPOSER 2 Shift 1' => 'ROTARY COMPOSER 2<br>Shift 1',
        'ROTARY COMPOSER 2 Shift 2' => 'ROTARY COMPOSER 2<br>Shift 2',
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
        $dayMap = [];

        foreach ($rows as $row) {
            $machineName = trim((string) ($row['NamaMesin'] ?? ''));
            $dateValue = trim((string) ($row['Tanggal'] ?? ''));

            if ($machineName === '' || $dateValue === '') {
                continue;
            }

            if (!isset($machineMap[$machineName])) {
                $machineMap[$machineName] = [
                    'name' => $machineName,
                    'label' => self::MACHINE_LABELS[$machineName] ?? $machineName,
                    'target' => null,
                    'days' => [],
                    'outputs' => [],
                    'rends' => [],
                    'machine_rend' => null,
                ];
            }

            $dayLabel = Carbon::parse($dateValue)->format('d');
            $dayMap[$dayLabel] = true;

            $target = $this->nullableFloat($row['Target'] ?? null);
            $output = $this->nullableFloat($row['Output'] ?? null);
            $rend = $this->nullableFloat($row['Rend'] ?? null);
            $machineRend = $this->nullableFloat($row['RendPerMesin'] ?? null);
            $tebal = $this->nullableFloat($row['Tebal'] ?? null);

            if ($machineMap[$machineName]['target'] === null && $target !== null) {
                $machineMap[$machineName]['target'] = $target;
            }

            if (!isset($machineMap[$machineName]['days'][$dayLabel])) {
                $machineMap[$machineName]['days'][$dayLabel] = [
                    'tebal' => 0.0,
                    'output' => 0.0,
                    'rend' => 0.0,
                    'has_tebal' => false,
                    'has_output' => false,
                    'has_rend' => false,
                ];
            }

            if ($tebal !== null) {
                $machineMap[$machineName]['days'][$dayLabel]['tebal'] += $tebal;
                $machineMap[$machineName]['days'][$dayLabel]['has_tebal'] = true;
            }

            if ($output !== null) {
                $machineMap[$machineName]['days'][$dayLabel]['output'] += $output;
                $machineMap[$machineName]['days'][$dayLabel]['has_output'] = true;
            }

            if ($rend !== null) {
                $machineMap[$machineName]['days'][$dayLabel]['rend'] += $rend;
                $machineMap[$machineName]['days'][$dayLabel]['has_rend'] = true;
            }

            if ($machineRend !== null && $machineMap[$machineName]['machine_rend'] === null) {
                $machineMap[$machineName]['machine_rend'] = $machineRend;
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

        ksort($dayMap, SORT_NATURAL);

        $rowsByDay = [];
        foreach (array_keys($dayMap) as $dayLabel) {
            $cells = [];

            foreach ($orderedMachines as $machine) {
                $daily = $machine['days'][$dayLabel] ?? null;
                $tebal = $daily !== null && $daily['has_tebal'] ? $daily['tebal'] : null;
                $output = $daily !== null && $daily['has_output'] ? $daily['output'] : null;
                $rend = $daily !== null && $daily['has_rend'] ? $daily['rend'] : null;

                $cells[$machine['name']] = [
                    'tebal' => $tebal,
                    'output' => $output,
                    'rend' => $rend,
                ];
            }

            $rowsByDay[] = [
                'label' => $dayLabel,
                'cells' => $cells,
            ];
        }

        $statRows = [];
        foreach (['Total', 'Avg', 'Min', 'Max'] as $label) {
            $cells = [];

            foreach ($orderedMachines as $machine) {
                $outputs = [];
                $rends = [];

                foreach ($machine['days'] as $daily) {
                    if (($daily['has_output'] ?? false) && abs((float) $daily['output']) > 0.0000001) {
                        $outputs[] = (float) $daily['output'];
                    }

                    if (($daily['has_rend'] ?? false) && abs((float) $daily['rend']) > 0.0000001) {
                        $rends[] = (float) $daily['rend'];
                    }
                }

                $outputValue = null;
                $rendValue = null;

                if ($label === 'Total') {
                    $outputValue = $outputs !== [] ? array_sum($outputs) : null;
                    $rendValue = $machine['machine_rend'];
                } elseif ($label === 'Avg') {
                    $outputValue = $outputs !== [] ? array_sum($outputs) / count($outputs) : null;
                    $rendValue = $rends !== [] ? array_sum($rends) / count($rends) : null;
                } elseif ($label === 'Min') {
                    $outputValue = $outputs !== [] ? min($outputs) : null;
                    $rendValue = $rends !== [] ? min($rends) : null;
                } elseif ($label === 'Max') {
                    $outputValue = $outputs !== [] ? max($outputs) : null;
                    $rendValue = $rends !== [] ? max($rends) : null;
                }

                $cells[$machine['name']] = [
                    'output' => $outputValue,
                    'rend' => $rendValue,
                ];
            }

            $statRows[] = [
                'label' => $label,
                'cells' => $cells,
            ];
        }

        $targetRow = [
            'label' => 'Target',
            'cells' => [],
        ];

        foreach ($orderedMachines as $machine) {
            $targetRow['cells'][$machine['name']] = $machine['target'];
        }

        return [
            'columns' => array_map(
                static fn(array $machine): array => [
                    'key' => $machine['name'],
                    'label' => $machine['label'],
                ],
                $orderedMachines
            ),
            'rows' => $rowsByDay,
            'stat_rows' => $statRows,
            'target_row' => $targetRow,
            'summary' => [
                'machine_count' => count($orderedMachines),
                'row_count' => count($rows),
                'day_count' => count($rowsByDay),
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
        $connectionName = config('reports.produksi_hulu_hilir.database_connection');
        $procedure = (string) config('reports.produksi_hulu_hilir.stored_procedure', 'SPWps_LapProduksiSemuaMesinV2');

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
