<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HasilProduksiMesinLemburDanNonLemburReportService
{
    private const EXPECTED_COLUMNS = [
        'NoProduksi',
        'Tanggal',
        'Hari',
        'Shift',
        'NamaMesin',
        'JamKerja',
        'JmlhAnggota',
        'Output',
        'OutputLembur',
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

    private const OPTIONAL_LEMBUR_TK_KEYS = [
        'JmlhAnggotaLembur',
        'TKLembur',
        'AnggotaLembur',
        'JmlhTKLembur',
    ];

    private const OPTIONAL_LEMBUR_HM_KEYS = [
        'JamKerjaLembur',
        'HMLembur',
        'LemburHM',
        'JamLembur',
    ];

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetchRows($startDate, $endDate);
        $machineMap = [];

        foreach ($rows as $row) {
            $machineName = trim((string) ($row['NamaMesin'] ?? ''));

            if ($machineName === '') {
                continue;
            }

            if (!isset($machineMap[$machineName])) {
                $machineMap[$machineName] = [
                    'name' => $machineName,
                    'rows' => [],
                    'total_output' => 0.0,
                    'total_output_lembur' => 0.0,
                    'total_produksi' => 0.0,
                    'hari_aktif' => [],
                ];
            }

            $output = $this->nullableFloat($row['Output'] ?? null);
            $outputLembur = $this->nullableFloat($row['OutputLembur'] ?? null);
            $jmlhAnggotaLembur = $this->extractOptionalFloat($row, self::OPTIONAL_LEMBUR_TK_KEYS);
            $jamKerjaLembur = $this->extractOptionalFloat($row, self::OPTIONAL_LEMBUR_HM_KEYS);
            $totalProduksi = ($output ?? 0.0) + ($outputLembur ?? 0.0);
            $tanggal = trim((string) ($row['Tanggal'] ?? ''));

            $machineMap[$machineName]['rows'][] = [
                'NoProduksi' => (string) ($row['NoProduksi'] ?? ''),
                'Tanggal' => $tanggal,
                'Hari' => (string) ($row['Hari'] ?? ''),
                'Shift' => (string) ($row['Shift'] ?? ''),
                'JamKerja' => $this->nullableFloat($row['JamKerja'] ?? null),
                'JmlhAnggota' => $this->nullableFloat($row['JmlhAnggota'] ?? null),
                'JamKerjaLembur' => $jamKerjaLembur,
                'JmlhAnggotaLembur' => $jmlhAnggotaLembur,
                'Output' => $output,
                'OutputLembur' => $outputLembur,
                'TotalProduksi' => $totalProduksi,
            ];

            $machineMap[$machineName]['total_output'] += $output ?? 0.0;
            $machineMap[$machineName]['total_output_lembur'] += $outputLembur ?? 0.0;
            $machineMap[$machineName]['total_produksi'] += $totalProduksi;

            if ($tanggal !== '' && abs($totalProduksi) > 0.0000001) {
                $machineMap[$machineName]['hari_aktif'][$tanggal] = true;
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

        $machines = [];
        $summaryRows = [];
        $flatRows = [];
        $grandTotals = [
            'output' => 0.0,
            'output_lembur' => 0.0,
            'total_produksi' => 0.0,
        ];

        foreach (array_values($orderedMachines) as $index => $machine) {
            usort($machine['rows'], static function (array $left, array $right): int {
                $dateCompare = strcmp((string) ($left['Tanggal'] ?? ''), (string) ($right['Tanggal'] ?? ''));
                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                return strcmp((string) ($left['Shift'] ?? ''), (string) ($right['Shift'] ?? ''));
            });

            $hariAktif = count($machine['hari_aktif']);
            $avgProduksi = $hariAktif > 0 ? $machine['total_produksi'] / $hariAktif : null;

            $machines[] = [
                'no' => $index + 1,
                'name' => $machine['name'],
                'rows' => $machine['rows'],
                'hari_aktif' => $hariAktif,
                'total_output' => $machine['total_output'],
                'total_output_lembur' => $machine['total_output_lembur'],
                'total_produksi' => $machine['total_produksi'],
                'avg_produksi' => $avgProduksi,
            ];

            foreach ($machine['rows'] as $row) {
                $flatRows[] = $row + [
                    'NamaMesin' => $machine['name'],
                    'MachineOrder' => $index,
                ];
            }

            $summaryRows[] = [
                'No' => $index + 1,
                'NamaMesin' => $machine['name'],
                'HariAktif' => $hariAktif,
                'Output' => $machine['total_output'],
                'OutputLembur' => $machine['total_output_lembur'],
                'TotalProduksi' => $machine['total_produksi'],
                'AvgProduksi' => $avgProduksi,
            ];

            $grandTotals['output'] += $machine['total_output'];
            $grandTotals['output_lembur'] += $machine['total_output_lembur'];
            $grandTotals['total_produksi'] += $machine['total_produksi'];
        }

        usort($flatRows, static function (array $left, array $right): int {
            $dateCompare = strcmp((string) ($left['Tanggal'] ?? ''), (string) ($right['Tanggal'] ?? ''));
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            $machineCompare = ((int) ($left['MachineOrder'] ?? 0)) <=> ((int) ($right['MachineOrder'] ?? 0));
            if ($machineCompare !== 0) {
                return $machineCompare;
            }

            return strcmp((string) ($left['Shift'] ?? ''), (string) ($right['Shift'] ?? ''));
        });

        $groupedRows = [];
        foreach ($flatRows as $row) {
            $tanggal = (string) ($row['Tanggal'] ?? '');

            if (!isset($groupedRows[$tanggal])) {
                $groupedRows[$tanggal] = [
                    'Tanggal' => $tanggal,
                    'Hari' => (string) ($row['Hari'] ?? ''),
                    'rows' => [],
                ];
            }

            $groupedRows[$tanggal]['rows'][] = $row;
        }

        $groupedRows = array_values($groupedRows);

        return [
            'machines' => $machines,
            'summary_rows' => $summaryRows,
            'flat_rows' => $flatRows,
            'grouped_rows' => $groupedRows,
            'grand_totals' => $grandTotals,
            'summary' => [
                'machine_count' => count($machines),
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
            'machine_count' => (int) ($reportData['summary']['machine_count'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.hasil_produksi_mesin_lembur_dan_non_lembur.database_connection');
        $procedure = (string) config(
            'reports.hasil_produksi_mesin_lembur_dan_non_lembur.stored_procedure',
            'SPWps_LapLemburPerMesin'
        );

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $rows = DB::connection($connectionName ?: null)
            ->select("EXEC {$procedure} ?, ?", [$startDate, $endDate]);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    public function formatTanggalDisplay(string $tanggal, string $hari): string
    {
        if ($tanggal === '') {
            return '';
        }

        $hariMap = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        $hariLabel = $hariMap[$hari] ?? $hari;

        return sprintf('%s, %s', $hariLabel, Carbon::parse($tanggal)->translatedFormat('d-M-y'));
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $keys
     */
    private function extractOptionalFloat(array $row, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && is_numeric($row[$key])) {
                return (float) $row[$key];
            }
        }

        return null;
    }
}
