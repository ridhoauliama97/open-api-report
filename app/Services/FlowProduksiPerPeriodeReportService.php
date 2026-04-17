<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class FlowProduksiPerPeriodeReportService
{
    private const EXPECTED_COLUMNS = [
        'Group',
        'KBTonBeli',
        'KBRacip',
        'STRacipan',
        'STVacuumStick',
        'STKDIn',
        'STKDOut',
        'STm3Input',
        'WIPBersihOutput',
        'WIPFJInput',
        'WIPFJOutput',
        'WIPMouldingInput',
        'WIPMouldingOutput',
    ];

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetchRows($startDate, $endDate);

        $normalizedRows = [];
        $totals = array_fill_keys(array_slice(self::EXPECTED_COLUMNS, 1), 0.0);

        foreach ($rows as $index => $row) {
            $normalized = [
                'No' => $index + 1,
                'Group Kayu' => trim((string) ($row['Group'] ?? '')),
            ];

            foreach (array_slice(self::EXPECTED_COLUMNS, 1) as $column) {
                $value = $this->toFloat($row[$column] ?? null);
                $normalized[$column] = $value;
                $totals[$column] += $value;
            }

            $normalizedRows[] = $normalized;
        }

        return [
            'rows' => $normalizedRows,
            'totals' => $totals,
            'summary_lines' => [
                [
                    'label' => 'Kayu Bulat (KB)',
                    'text' => 'Pembelian - Racip = ' . $this->formatSignedTon($totals['KBTonBeli'] - $totals['KBRacip']),
                ],
                [
                    'label' => 'Sawn Timber (ST)',
                    'text' => 'ST Hasil Racip - ST Siap Vaccum Stick = ' . $this->formatSignedTon($totals['STRacipan'] - $totals['STVacuumStick']),
                ],
                [
                    'label' => '',
                    'text' => 'ST Hasil Racip - ST Masuk KD = ' . $this->formatSignedTon($totals['STRacipan'] - $totals['STKDIn']),
                ],
                [
                    'label' => 'WIP',
                    'text' => 'ST Keluar KD - ST Pakai di S4S = ' . $this->formatSignedTon($totals['STKDOut'] - $totals['STm3Input']),
                ],
                [
                    'label' => '',
                    'text' => 'WIP Bersih S4S - WIP Pakai di FJ = ' . $this->formatSignedM3($totals['WIPBersihOutput'] - $totals['WIPFJInput']),
                ],
                [
                    'label' => '',
                    'text' => 'WIP Hasil FJ - WIP Moulding = ' . $this->formatSignedM3($totals['WIPFJOutput'] - $totals['WIPMouldingInput']),
                ],
            ],
            'summary' => [
                'row_count' => count($normalizedRows),
                'group_count' => count(array_filter(array_map(static fn(array $row): string => (string) ($row['Group Kayu'] ?? ''), $normalizedRows))),
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
            'group_count' => (int) ($reportData['summary']['group_count'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.flow_produksi_per_periode.database_connection');
        $procedure = (string) config('reports.flow_produksi_per_periode.stored_procedure', 'SPWps_LapFlowProduksiPerPeriode');

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $rows = DB::connection($connectionName ?: null)
            ->select("EXEC {$procedure} ?, ?", [$startDate, $endDate]);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    private function toFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function formatSignedTon(float $value): string
    {
        $formatted = number_format(abs($value), 4, '.', ',');

        return $value < 0 ? "({$formatted}) (Ton)" : "{$formatted} (Ton)";
    }

    private function formatSignedM3(float $value): string
    {
        if (abs($value) < 0.0000001) {
            return '(m3)';
        }

        $formatted = number_format(abs($value), 4, '.', ',');

        return $value < 0 ? "({$formatted}) (m3)" : "{$formatted} (m3)";
    }
}
