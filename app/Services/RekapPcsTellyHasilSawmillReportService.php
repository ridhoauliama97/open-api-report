<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapPcsTellyHasilSawmillReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        return $this->normalizeRows($this->runProcedureQuery($startDate, $endDate));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);

        if ($rows === []) {
            throw new RuntimeException('Data rekap jumlah pcs telly hasil sawmill tidak ditemukan untuk rentang tanggal yang dipilih.');
        }

        $documents = $this->buildDocuments($rows);

        return [
            'rows' => $rows,
            'documents' => $documents,
            'summary' => [
                'total_rows' => count($rows),
                'total_documents' => count($documents),
                'total_pcs' => array_sum(array_map(static fn(array $document): int => (int) ($document['summary']['total_pcs'] ?? 0), $documents)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.rekap_pcs_telly_hasil_sawmill.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($rows),
        ];
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        $normalized = array_map(function (object $row): array {
            $item = (array) $row;

            return [
                'NmSupplier' => trim((string) ($item['NmSupplier'] ?? '')),
                'TglSawmill' => trim((string) ($item['TglSawmill'] ?? '')),
                'NoKayuBulat' => trim((string) ($item['NoKayuBulat'] ?? '')),
                'NoPlat' => trim((string) ($item['NoPlat'] ?? '')),
                'Suket' => trim((string) ($item['Suket'] ?? '')),
                'Jenis' => trim((string) ($item['Jenis'] ?? '')),
                'NoSTSawmill' => trim((string) ($item['NoSTSawmill'] ?? '')),
                'NoMeja' => trim((string) ($item['NoMeja'] ?? '')),
                'NamaGrade' => trim((string) ($item['NamaGrade'] ?? '')),
                'Tebal' => $this->toFloat($item['Tebal'] ?? null) ?? 0.0,
                'Lebar' => $this->toFloat($item['Lebar'] ?? null) ?? 0.0,
                'JmlhBatang' => (int) round($this->toFloat($item['JmlhBatang'] ?? null) ?? 0),
            ];
        }, $rows);

        usort($normalized, function (array $a, array $b): int {
            $headerCompare = strcmp($this->documentKey($a), $this->documentKey($b));
            if ($headerCompare !== 0) {
                return $headerCompare;
            }

            $gradeCompare = $this->gradeSortWeight((string) ($a['NamaGrade'] ?? '')) <=> $this->gradeSortWeight((string) ($b['NamaGrade'] ?? ''));
            if ($gradeCompare !== 0) {
                return $gradeCompare;
            }

            $tebalCompare = ((float) ($a['Tebal'] ?? 0)) <=> ((float) ($b['Tebal'] ?? 0));
            if ($tebalCompare !== 0) {
                return $tebalCompare;
            }

            return ((float) ($a['Lebar'] ?? 0)) <=> ((float) ($b['Lebar'] ?? 0));
        });

        return array_values($normalized);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildDocuments(array $rows): array
    {
        $documents = [];

        foreach ($rows as $row) {
            $documentKey = $this->documentKey($row);
            $gradeName = trim((string) ($row['NamaGrade'] ?? ''));
            $tebalKey = (string) ((float) ($row['Tebal'] ?? 0));

            if (!isset($documents[$documentKey])) {
                $documents[$documentKey] = [
                    'header' => [
                        'supplier' => (string) ($row['NmSupplier'] ?? '-'),
                        'tanggal' => (string) ($row['TglSawmill'] ?? ''),
                        'no_kayu_bulat' => (string) ($row['NoKayuBulat'] ?? '-'),
                        'suket' => (string) ($row['Suket'] ?? '-'),
                        'jenis_kayu' => (string) ($row['Jenis'] ?? '-'),
                        'no_plat' => (string) ($row['NoPlat'] ?? '-'),
                    ],
                    'grades' => [],
                    'summary' => [
                        'total_pcs' => 0,
                    ],
                ];
            }

            if (!isset($documents[$documentKey]['grades'][$gradeName])) {
                $documents[$documentKey]['grades'][$gradeName] = [
                    'name' => $gradeName !== '' ? $gradeName : 'Tanpa Grade',
                    'tebal_groups' => [],
                    'total_pcs' => 0,
                ];
            }

            if (!isset($documents[$documentKey]['grades'][$gradeName]['tebal_groups'][$tebalKey])) {
                $documents[$documentKey]['grades'][$gradeName]['tebal_groups'][$tebalKey] = [
                    'tebal' => (float) ($row['Tebal'] ?? 0),
                    'rows' => [],
                    'total_pcs' => 0,
                ];
            }

            $documents[$documentKey]['grades'][$gradeName]['tebal_groups'][$tebalKey]['rows'][] = [
                'tebal' => (float) ($row['Tebal'] ?? 0),
                'lebar' => (float) ($row['Lebar'] ?? 0),
                'pcs' => (int) ($row['JmlhBatang'] ?? 0),
            ];

            $documents[$documentKey]['grades'][$gradeName]['tebal_groups'][$tebalKey]['total_pcs'] += (int) ($row['JmlhBatang'] ?? 0);
            $documents[$documentKey]['grades'][$gradeName]['total_pcs'] += (int) ($row['JmlhBatang'] ?? 0);
            $documents[$documentKey]['summary']['total_pcs'] += (int) ($row['JmlhBatang'] ?? 0);
        }

        $result = array_values($documents);

        foreach ($result as &$document) {
            $grades = array_values($document['grades']);
            usort($grades, fn(array $a, array $b): int => $this->gradeSortWeight((string) ($a['name'] ?? '')) <=> $this->gradeSortWeight((string) ($b['name'] ?? '')));

            foreach ($grades as &$grade) {
                $tebalGroups = array_values($grade['tebal_groups']);
                usort($tebalGroups, static fn(array $a, array $b): int => ((float) ($a['tebal'] ?? 0)) <=> ((float) ($b['tebal'] ?? 0)));

                foreach ($tebalGroups as &$tebalGroup) {
                    usort($tebalGroup['rows'], static fn(array $a, array $b): int => ((float) ($a['lebar'] ?? 0)) <=> ((float) ($b['lebar'] ?? 0)));
                }
                unset($tebalGroup);

                $grade['tebal_groups'] = $tebalGroups;
            }
            unset($grade);

            $document['grades'] = $grades;
        }
        unset($document);

        return $result;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.rekap_pcs_telly_hasil_sawmill';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SPWps_RekapPcsTellyHasilSawmill');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap pcs telly hasil sawmill belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = array_slice([$startDate, $endDate], 0, max(0, min($parameterCount, 2)));

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap pcs telly hasil sawmill dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PCS_TELLY_HASIL_SAWMILL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan rekap pcs telly hasil sawmill belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?"
                : "CALL {$procedure}(?, ?)",
        };

        try {
            return $connection->select($sql, $bindings);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan rekap pcs telly hasil sawmill: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param array<string,mixed> $row
     */
    private function documentKey(array $row): string
    {
        return implode('|', [
            (string) ($row['NmSupplier'] ?? ''),
            (string) ($row['TglSawmill'] ?? ''),
            (string) ($row['NoKayuBulat'] ?? ''),
            (string) ($row['NoPlat'] ?? ''),
            (string) ($row['Suket'] ?? ''),
            (string) ($row['Jenis'] ?? ''),
        ]);
    }

    private function gradeSortWeight(string $gradeName): int
    {
        return match (strtoupper(trim($gradeName))) {
            'STD' => 10,
            'MC 2' => 20,
            'MC2' => 20,
            'MC 1' => 30,
            'MC1' => 30,
            'KAYU LAT' => 40,
            default => 90,
        };
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim(str_replace(' ', '', $value));
        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.');
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
