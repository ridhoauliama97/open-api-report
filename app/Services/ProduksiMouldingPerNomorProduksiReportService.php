<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProduksiMouldingPerNomorProduksiReportService
{
    /**
     * @return array<string, mixed>
     */
    public function fetch(string $noProduksi): array
    {
        $rows = $this->fetchRawRows($noProduksi);

        if ($rows === []) {
            throw new RuntimeException('Data produksi moulding tidak ditemukan untuk nomor produksi tersebut.');
        }

        return $this->buildReportData($noProduksi, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $noProduksi): array
    {
        $rows = $this->fetchRawRows($noProduksi);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.produksi_moulding_per_nomor_produksi.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));
        $report = $this->buildReportData($noProduksi, $rows);

        return [
            'is_healthy' => $missingColumns === [],
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
            'input_row_count' => count($report['input_rows'] ?? []),
            'output_row_count' => count($report['output_rows'] ?? []),
            'repair_row_count' => count($report['repair_rows'] ?? []),
            'afkir_row_count' => count($report['afkir_rows'] ?? []),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function buildReportData(string $noProduksi, array $rows): array
    {
        $firstRow = $rows[0] ?? [];
        $buckets = [
            'input' => [],
            'output' => [],
            'repair' => [],
            'afkir' => [],
        ];
        $labels = ['input' => null, 'output' => null];

        foreach ($rows as $row) {
            foreach ($this->extractCandidates($row) as [$detail, $direction, $label]) {
                $bucket = $this->resolveFlagBucket($row) ?? $direction;

                if ($bucket === null) {
                    continue;
                }

                $buckets[$bucket][] = $detail;

                if ($bucket === 'input' || $bucket === 'output') {
                    $labels[$bucket] ??= $label;
                }
            }
        }

        $inputRows = $this->sortDetailRows($buckets['input']);
        $outputRows = $this->sortDetailRows($buckets['output']);
        $repairRows = $this->sortDetailRows($buckets['repair']);
        $afkirRows = $this->sortDetailRows($buckets['afkir']);

        $inputTotals = $this->calculateTotals($inputRows);
        $outputTotals = $this->calculateTotals($outputRows);
        $repairTotals = $this->calculateTotals($repairRows);
        $afkirTotals = $this->calculateTotals($afkirRows);
        $rendemen = $inputTotals['kubik'] > 0
            ? ($outputTotals['kubik'] / $inputTotals['kubik']) * 100.0
            : null;

        return [
            'meta' => [
                'source' => 'stored_procedure',
                'stored_procedure' => (string) config('reports.produksi_moulding_per_nomor_produksi.stored_procedure', 'SPWps_LapProduksiMoulding'),
                'no_produksi' => $this->firstNonEmptyString($firstRow, ['NoProduksi', 'No_Produksi']) ?: $noProduksi,
                'tanggal' => $this->parseDate($this->firstNonEmptyString($firstRow, ['Tanggal', 'TglProduksi', 'Tgl', 'DateCreate'])),
                'shift' => $this->firstNonEmptyString($firstRow, ['Shift']),
                'nama_mesin' => $this->firstNonEmptyString($firstRow, ['NamaMesin', 'Mesin', 'Machine']) ?: 'MOULDING',
                'jam_kerja' => $this->firstNumber($firstRow, ['JamKerja', 'JmKerja', 'HK']),
                'anggota' => $this->firstInt($firstRow, ['JmlhAnggota', 'JumlahAnggota', 'Anggota']),
                'operator' => $this->firstNonEmptyString($firstRow, ['Operator', 'CreateBy', 'NamaOperator']),
                'input_label' => $labels['input'] ?: 'LAMINATING',
                'output_label' => $labels['output'] ?: 'MOULDING',
            ],
            'input_rows' => $inputRows,
            'output_rows' => $outputRows,
            'repair_rows' => $repairRows,
            'afkir_rows' => $afkirRows,
            'totals' => [
                'input' => $inputTotals,
                'output' => $outputTotals,
                'repair' => $repairTotals,
                'afkir' => $afkirTotals,
                'rendemen' => $rendemen,
            ],
            'raw_columns' => array_keys($firstRow),
            'raw_rows' => $rows,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRawRows(string $noProduksi): array
    {
        $configPath = 'reports.produksi_moulding_per_nomor_produksi';
        $connectionName = config("{$configPath}.database_connection");
        $procedure = (string) config("{$configPath}.stored_procedure", 'SPWps_LapProduksiMoulding');
        $syntax = (string) config("{$configPath}.call_syntax", 'exec');
        $customQuery = config("{$configPath}.query");
        $parameterCount = (int) config("{$configPath}.parameter_count", 1);

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan produksi moulding per nomor produksi belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan produksi moulding per nomor produksi dikonfigurasi untuk SQL Server. '
                .'Set PRODUKSI_MOULDING_PER_NOMOR_PRODUKSI_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('PRODUKSI_MOULDING_PER_NOMOR_PRODUKSI_REPORT_QUERY belum diisi.');

            $bindings = str_contains($query, '?') ? array_pad([$noProduksi], $parameterCount, null) : [];

            return array_map(static fn ($row): array => (array) $row, $connection->select($query, $bindings));
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'call' => $this->buildCallSql($procedure, $parameterCount),
            'exec' => $this->buildExecSql($procedure, $parameterCount),
            default => $driver === 'sqlsrv'
                ? $this->buildExecSql($procedure, $parameterCount)
                : $this->buildCallSql($procedure, $parameterCount),
        };

        $bindings = array_fill(0, max($parameterCount, 1), null);
        $bindings[0] = $noProduksi;

        return array_map(
            static fn ($row): array => (array) $row,
            $connection->select("SET NOCOUNT ON; {$sql}", $bindings),
        );
    }

    private function buildExecSql(string $procedure, int $parameterCount): string
    {
        if ($parameterCount <= 0) {
            return "EXEC {$procedure}";
        }

        return "EXEC {$procedure} ".implode(', ', array_fill(0, $parameterCount, '?'));
    }

    private function buildCallSql(string $procedure, int $parameterCount): string
    {
        if ($parameterCount <= 0) {
            return "CALL {$procedure}()";
        }

        return "CALL {$procedure}(".implode(', ', array_fill(0, $parameterCount, '?')).')';
    }

    /**
     * Menentukan bucket baris berdasarkan flag SP, tanpa mengubah bucket asal (input/output).
     * IsRepair lebih didahulukan karena repair adalah perbaikan, bukan pembuangan.
     *
     * @param  array<string, mixed>  $row
     */
    private function resolveFlagBucket(array $row): ?string
    {
        if ($this->isFlagSet($row, 'IsRepair')) {
            return 'repair';
        }

        if ($this->isFlagSet($row, 'IsReject')) {
            return 'afkir';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isFlagSet(array $row, string $column): bool
    {
        if (! array_key_exists($column, $row)) {
            return false;
        }

        $value = $row[$column];

        if (is_bool($value)) {
            return $value;
        }

        return $this->toFloat($value) === 1.0;
    }

    /**
     * Menghasilkan kandidat baris detail beserta arah (input/output) dan label section-nya.
     *
     * @param  array<string, mixed>  $row
     * @return array<int, array{0: array<string, mixed>, 1: ?string, 2: ?string}>
     */
    private function extractCandidates(array $row): array
    {
        [$prefixedInput, $prefixedOutput] = $this->extractPrefixedItems($row);

        if ($prefixedInput !== null || $prefixedOutput !== null) {
            $candidates = [];

            if ($prefixedInput !== null) {
                $candidates[] = [$prefixedInput['row'], 'input', $prefixedInput['label']];
            }

            if ($prefixedOutput !== null) {
                $candidates[] = [$prefixedOutput['row'], 'output', $prefixedOutput['label']];
            }

            return $candidates;
        }

        $detail = $this->mapDetailRow($row);

        if (! $this->hasDetailContent($detail)) {
            return [];
        }

        $direction = $this->detectDirection($row);

        return [[
            $detail,
            $direction,
            in_array($direction, ['input', 'output'], true)
                ? $this->extractSectionLabel($row, $direction)
                : null,
        ]];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{0: array{row: array<string, mixed>, label: ?string}|null, 1: array{row: array<string, mixed>, label: ?string}|null}
     */
    private function extractPrefixedItems(array $row): array
    {
        $input = $this->mapDetailRowFromAliases($row, [
            'no_label' => ['InputNoLabel', 'NoLabelInput', 'InNoLabel'],
            'tebal' => ['InputTebal', 'TebalInput', 'InTebal'],
            'lebar' => ['InputLebar', 'LebarInput', 'InLebar'],
            'panjang' => ['InputPanjang', 'PanjangInput', 'InPanjang'],
            'jmlh_batang' => ['InputJmlhBatang', 'InputJumlahBatang', 'JmlhBatangInput', 'InJmlhBatang'],
            'kubik' => ['InputKubik', 'KubikInput', 'InKubik', 'InputM3', 'InM3'],
        ]);

        $output = $this->mapDetailRowFromAliases($row, [
            'no_label' => ['OutputNoLabel', 'NoLabelOutput', 'OutNoLabel'],
            'tebal' => ['OutputTebal', 'TebalOutput', 'OutTebal'],
            'lebar' => ['OutputLebar', 'LebarOutput', 'OutLebar'],
            'panjang' => ['OutputPanjang', 'PanjangOutput', 'OutPanjang'],
            'jmlh_batang' => ['OutputJmlhBatang', 'OutputJumlahBatang', 'JmlhBatangOutput', 'OutJmlhBatang'],
            'kubik' => ['OutputKubik', 'KubikOutput', 'OutKubik', 'OutputM3', 'OutM3'],
        ]);

        return [
            $this->hasDetailContent($input)
                ? [
                    'row' => $input,
                    'label' => $this->firstNonEmptyString($row, ['Input', 'NamaInput', 'InputGroup', 'InputProses', 'InputSection']),
                ]
                : null,
            $this->hasDetailContent($output)
                ? [
                    'row' => $output,
                    'label' => $this->firstNonEmptyString($row, ['Output', 'NamaOutput', 'OutputGroup', 'OutputProses', 'OutputSection']),
                ]
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, array<int, string>>  $aliases
     * @return array<string, mixed>
     */
    private function mapDetailRowFromAliases(array $row, array $aliases): array
    {
        return [
            'no_label' => $this->firstNonEmptyString($row, $aliases['no_label']),
            'tebal' => $this->firstNumber($row, $aliases['tebal']),
            'lebar' => $this->firstNumber($row, $aliases['lebar']),
            'panjang' => $this->firstNumber($row, $aliases['panjang']),
            'jmlh_batang' => $this->firstInt($row, $aliases['jmlh_batang']),
            'kubik' => $this->firstNumber($row, $aliases['kubik']),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapDetailRow(array $row): array
    {
        return [
            'no_label' => $this->firstNonEmptyString($row, ['NoLabel', 'No_Label', 'NomorLabel', 'Label']),
            'tebal' => $this->firstNumber($row, ['Tebal']),
            'lebar' => $this->firstNumber($row, ['Lebar']),
            'panjang' => $this->firstNumber($row, ['Panjang']),
            'jmlh_batang' => $this->firstInt($row, ['JmlhBatang', 'JumlahBatang', 'Batang']),
            'kubik' => $this->firstNumber($row, ['Kubik', 'M3']),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function detectDirection(array $row): ?string
    {
        $value = strtolower((string) ($this->firstNonEmptyString($row, [
            'Tipe',
            'Type',
            'IO',
            'Arah',
            'Posisi',
            'Section',
            'Bagian',
            'KelompokData',
            'StatusData',
        ]) ?? ''));

        if ($value !== '') {
            if (str_contains($value, 'input') || str_contains($value, 'masuk')) {
                return 'input';
            }

            if (str_contains($value, 'output') || str_contains($value, 'keluar') || str_contains($value, 'hasil')) {
                return 'output';
            }
        }

        if ($this->firstNonEmptyString($row, ['Input', 'NamaInput', 'InputGroup', 'InputProses', 'InputSection']) !== null) {
            return 'input';
        }

        if ($this->firstNonEmptyString($row, ['Output', 'NamaOutput', 'OutputGroup', 'OutputProses', 'OutputSection']) !== null) {
            return 'output';
        }

        $process = strtolower((string) ($this->firstNonEmptyString($row, [
            'Proses',
            'NamaProses',
            'Group',
            'Kelompok',
            'Tujuan',
            'Dari',
            'Asal',
        ]) ?? ''));

        if ($process !== '') {
            if (str_contains($process, 'laminating') || $process === 'lmt' || str_contains($process, 'lamin')) {
                return 'input';
            }

            if (str_contains($process, 'moulding') || str_contains($process, 'molding') || $process === 'mld') {
                return 'output';
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function extractSectionLabel(array $row, string $direction): ?string
    {
        $aliases = $direction === 'input'
            ? ['Input', 'NamaInput', 'InputGroup', 'InputProses', 'InputSection', 'Asal', 'Dari', 'Group', 'Kelompok', 'Proses']
            : ['Output', 'NamaOutput', 'OutputGroup', 'OutputProses', 'OutputSection', 'Tujuan', 'Ke', 'Group', 'Kelompok', 'Proses'];

        return $this->firstNonEmptyString($row, $aliases);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function hasDetailContent(array $detail): bool
    {
        return ($detail['no_label'] ?? null) !== null
            || ($detail['tebal'] ?? null) !== null
            || ($detail['lebar'] ?? null) !== null
            || ($detail['panjang'] ?? null) !== null
            || ($detail['jmlh_batang'] ?? null) !== null
            || ($detail['kubik'] ?? null) !== null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function sortDetailRows(array $rows): array
    {
        usort($rows, static function (array $left, array $right): int {
            return strcmp((string) ($left['no_label'] ?? ''), (string) ($right['no_label'] ?? ''));
        });

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, float|int>
     */
    private function calculateTotals(array $rows): array
    {
        $totBatang = 0;
        $totKubik = 0.0;
        $uniqueLabels = [];

        foreach ($rows as $row) {
            $totBatang += (int) ($row['jmlh_batang'] ?? 0);
            $totKubik += (float) ($row['kubik'] ?? 0);

            $label = trim((string) ($row['no_label'] ?? ''));
            if ($label !== '') {
                $uniqueLabels[$label] = true;
            }
        }

        return [
            'count' => count($uniqueLabels),
            'jmlh_batang' => $totBatang,
            'kubik' => $totKubik,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $aliases
     */
    private function firstNonEmptyString(array $row, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            if (! array_key_exists($alias, $row)) {
                continue;
            }

            $value = trim((string) $row[$alias]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $aliases
     */
    private function firstNumber(array $row, array $aliases): ?float
    {
        foreach ($aliases as $alias) {
            if (! array_key_exists($alias, $row)) {
                continue;
            }

            $value = $this->toFloat($row[$alias]);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $aliases
     */
    private function firstInt(array $row, array $aliases): ?int
    {
        $value = $this->firstNumber($row, $aliases);

        return $value !== null ? (int) round($value) : null;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '' || $normalized === '-') {
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
