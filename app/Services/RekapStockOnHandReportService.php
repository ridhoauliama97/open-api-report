<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapStockOnHandReportService
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const SECTION_DEFINITIONS = [
        'kb' => [
            'label' => 'Kayu Bulat',
            'config' => 'rekap_stock_on_hand_sub_kb',
            'columns' => ['NoKayuBulat', 'Jenis', 'Grade', 'Tebal', 'Lebar', 'Panjang', 'Ton'],
            'compact_columns' => ['Jenis', 'Grade', 'Tebal', 'Lebar', 'Panjang', 'Ton'],
            'id_column' => 'NoKayuBulat',
            'value_column' => 'Ton',
            'pcs_column' => null,
            'unit' => 'Ton',
        ],
        'st' => [
            'label' => 'Sawn Timber',
            'config' => 'rekap_stock_on_hand_sub_st',
            'columns' => ['NoST', 'Jenis', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Ton'],
            'compact_columns' => ['Jenis', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Ton'],
            'id_column' => 'NoST',
            'value_column' => 'Ton',
            'pcs_column' => 'JmlhBatang',
            'unit' => 'Ton',
        ],
        's4s' => [
            'label' => 'S4S',
            'config' => 'rekap_stock_on_hand_sub_s4s',
            'columns' => ['NoS4S', 'Jenis', 'NamaGrade', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Kubik'],
            'id_column' => 'NoS4S',
            'value_column' => 'Kubik',
            'pcs_column' => 'JmlhBatang',
            'unit' => 'M3',
        ],
        'fj' => [
            'label' => 'Finger Joint',
            'config' => 'rekap_stock_on_hand_sub_fj',
            'columns' => ['NoFJ', 'Jenis', 'NamaGrade', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Kubik'],
            'id_column' => 'NoFJ',
            'value_column' => 'Kubik',
            'pcs_column' => 'JmlhBatang',
            'unit' => 'M3',
        ],
        'moulding' => [
            'label' => 'Moulding',
            'config' => 'rekap_stock_on_hand_sub_moulding',
            'columns' => ['NoMoulding', 'Jenis', 'NamaGrade', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Kubik'],
            'id_column' => 'NoMoulding',
            'value_column' => 'Kubik',
            'pcs_column' => 'JmlhBatang',
            'unit' => 'M3',
        ],
        'lmt' => [
            'label' => 'Laminating',
            'config' => 'rekap_stock_on_hand_sub_lmt',
            'columns' => ['NoLaminating', 'Jenis', 'NamaGrade', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Kubik'],
            'id_column' => 'NoLaminating',
            'value_column' => 'Kubik',
            'pcs_column' => 'JmlhBatang',
            'unit' => 'M3',
        ],
        'cca_akhir' => [
            'label' => 'CCA Akhir',
            'config' => 'rekap_stock_on_hand_sub_cca_akhir',
            'columns' => ['NoCCAkhir', 'Jenis', 'NamaGrade', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Kubik'],
            'id_column' => 'NoCCAkhir',
            'value_column' => 'Kubik',
            'pcs_column' => 'JmlhBatang',
            'unit' => 'M3',
        ],
        'sanding' => [
            'label' => 'Sanding',
            'config' => 'rekap_stock_on_hand_sub_sanding',
            'columns' => ['NoSanding', 'Jenis', 'NamaGrade', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Kubik'],
            'id_column' => 'NoSanding',
            'value_column' => 'Kubik',
            'pcs_column' => 'JmlhBatang',
            'unit' => 'M3',
        ],
        'bj' => [
            'label' => 'Barang Jadi',
            'config' => 'rekap_stock_on_hand_sub_bj',
            'columns' => ['NoBJ', 'Jenis', 'NamaBarangJadi', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Kubik'],
            'id_column' => 'NoBJ',
            'value_column' => 'Kubik',
            'pcs_column' => 'JmlhBatang',
            'unit' => 'M3',
        ],
        'reproses' => [
            'label' => 'Reproses',
            'config' => 'rekap_stock_on_hand_sub_reproses',
            'columns' => ['NoReproses', 'Jenis', 'NamaGrade', 'Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Kubik'],
            'id_column' => 'NoReproses',
            'value_column' => 'Kubik',
            'pcs_column' => 'JmlhBatang',
            'unit' => 'M3',
        ],
    ];

    private const COMPACT_THRESHOLD = 1000;

    /**
     * @return array<int, array{key:string,label:string}>
     */
    public function availableSections(): array
    {
        $sections = [];

        foreach (self::SECTION_DEFINITIONS as $key => $definition) {
            $sections[] = [
                'key' => $key,
                'label' => (string) $definition['label'],
            ];
        }

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate, ?array $selectedSections = null): array
    {
        $selectedKeys = $this->normalizeSelectedSections($selectedSections);
        $sections = [];
        $summaryRows = [];

        foreach (self::SECTION_DEFINITIONS as $key => $definition) {
            if (!in_array($key, $selectedKeys, true)) {
                continue;
            }

            $rows = $this->fetchRows((string) $definition['config'], $startDate, $endDate);
            $normalizedRows = array_map(
                static fn(object $row): array => (array) $row,
                $rows,
            );

            $totalValue = 0.0;
            $totalPcs = 0.0;
            $uniqueDocuments = [];

            foreach ($normalizedRows as $row) {
                $valueColumn = (string) $definition['value_column'];
                $pcsColumn = $definition['pcs_column'];
                $idColumn = (string) $definition['id_column'];

                $totalValue += $this->toFloat($row[$valueColumn] ?? null) ?? 0.0;
                if (is_string($pcsColumn) && $pcsColumn !== '') {
                    $totalPcs += $this->toFloat($row[$pcsColumn] ?? null) ?? 0.0;
                }

                $documentNo = trim((string) ($row[$idColumn] ?? ''));
                if ($documentNo !== '') {
                    $uniqueDocuments[$documentNo] = true;
                }
            }

            [$displayColumns, $displayRows, $isCompacted] = $this->buildDisplayRows($definition, $normalizedRows);

            $sections[] = [
                'key' => $key,
                'label' => $definition['label'],
                'unit' => $definition['unit'],
                'columns' => $displayColumns,
                'rows' => $displayRows,
                'row_count' => count($normalizedRows),
                'displayed_row_count' => count($displayRows),
                'document_count' => count($uniqueDocuments),
                'total_pcs' => $totalPcs,
                'total_value' => $totalValue,
                'is_compacted' => $isCompacted,
                'compact_note' => $isCompacted
                    ? 'Detail diringkas per ukuran agar PDF lebih ringan.'
                    : null,
            ];

            $summaryRows[] = [
                'Kategori' => $definition['label'],
                'Dokumen' => count($uniqueDocuments),
                'Baris' => count($normalizedRows),
                'TotalPcs' => $totalPcs,
                'TotalValue' => $totalValue,
                'Unit' => $definition['unit'],
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_sections' => $selectedKeys,
            'sections' => $sections,
            'summary_rows' => $summaryRows,
            'summary' => [
                'section_count' => count($sections),
                'row_count' => array_sum(array_map(static fn(array $section): int => (int) $section['row_count'], $sections)),
                'document_count' => array_sum(array_map(static fn(array $section): int => (int) $section['document_count'], $sections)),
                'total_pcs' => array_sum(array_map(static fn(array $section): float => (float) $section['total_pcs'], $sections)),
                'total_value' => array_sum(array_map(static fn(array $section): float => (float) $section['total_value'], $sections)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate, ?array $selectedSections = null): array
    {
        $selectedKeys = $this->normalizeSelectedSections($selectedSections);
        $results = [];
        $isHealthy = true;

        foreach (self::SECTION_DEFINITIONS as $key => $definition) {
            if (!in_array($key, $selectedKeys, true)) {
                continue;
            }

            $rows = $this->fetchRows((string) $definition['config'], $startDate, $endDate);
            $detectedColumns = array_keys((array) ($rows[0] ?? []));
            $expectedColumns = $definition['columns'];
            $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));

            $results[$key] = [
                'label' => $definition['label'],
                'row_count' => count($rows),
                'expected_columns' => $expectedColumns,
                'detected_columns' => $detectedColumns,
                'missing_columns' => $missingColumns,
            ];

            if ($missingColumns !== []) {
                $isHealthy = false;
            }
        }

        return [
            'is_healthy' => $isHealthy,
            'sections' => $results,
        ];
    }

    /**
     * @return array<int, object>
     */
    private function fetchRows(string $configKey, string $startDate, string $endDate): array
    {
        $connectionName = config("reports.{$configKey}.database_connection");
        $procedure = (string) config("reports.{$configKey}.stored_procedure", '');

        if ($procedure === '' || !preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException("Stored procedure untuk {$configKey} belum valid.");
        }

        return DB::connection($connectionName ?: null)
            ->select("EXEC {$procedure} ?, ?", [$startDate, $endDate]);
    }

    private function toFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{0: array<int, string>, 1: array<int, array<string, mixed>>, 2: bool}
     */
    private function buildDisplayRows(array $definition, array $rows): array
    {
        $defaultColumns = array_values($definition['columns']);
        $compactColumns = $definition['compact_columns'] ?? null;

        if (!is_array($compactColumns) || count($rows) <= self::COMPACT_THRESHOLD) {
            return [$defaultColumns, $rows, false];
        }

        $valueColumn = (string) $definition['value_column'];
        $pcsColumn = is_string($definition['pcs_column'] ?? null) ? (string) $definition['pcs_column'] : null;
        $groupColumns = array_values(array_filter(
            $compactColumns,
            static fn(string $column): bool => $column !== $valueColumn && $column !== $pcsColumn
        ));

        $grouped = [];

        foreach ($rows as $row) {
            $groupKey = implode('|', array_map(
                static fn(string $column): string => trim((string) ($row[$column] ?? '')),
                $groupColumns
            ));

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [];
                foreach ($groupColumns as $column) {
                    $grouped[$groupKey][$column] = $row[$column] ?? null;
                }
                if ($pcsColumn !== null) {
                    $grouped[$groupKey][$pcsColumn] = 0.0;
                }
                $grouped[$groupKey][$valueColumn] = 0.0;
            }

            if ($pcsColumn !== null) {
                $grouped[$groupKey][$pcsColumn] += $this->toFloat($row[$pcsColumn] ?? null) ?? 0.0;
            }

            $grouped[$groupKey][$valueColumn] += $this->toFloat($row[$valueColumn] ?? null) ?? 0.0;
        }

        return [array_values($compactColumns), array_values($grouped), true];
    }

    /**
     * @param  array<int, mixed>|null  $selectedSections
     * @return array<int, string>
     */
    private function normalizeSelectedSections(?array $selectedSections): array
    {
        $available = array_keys(self::SECTION_DEFINITIONS);

        if ($selectedSections === null || $selectedSections === []) {
            return $available;
        }

        $selected = array_values(array_filter(
            array_map(static fn(mixed $value): string => strtolower(trim((string) $value)), $selectedSections),
            static fn(string $value): bool => in_array($value, $available, true)
        ));

        return $selected !== [] ? array_values(array_unique($selected)) : $available;
    }
}
