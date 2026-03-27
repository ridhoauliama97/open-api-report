<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SandingHidupDetailReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();

        $normalized = array_map(function ($row): array {
            $item = (array) $row;

            $noSanding = trim((string) ($item['NoSanding'] ?? $item['No_Sanding'] ?? $item['No'] ?? ''));
            $dateCreate = trim((string) ($item['DateCreate'] ?? $item['TglCreate'] ?? $item['Tanggal'] ?? $item['Tgl'] ?? ''));
            $noSpk = trim((string) ($item['NoSPK'] ?? $item['NoSpk'] ?? $item['SPK'] ?? ''));

            $jenis = trim((string) ($item['Jenis'] ?? $item['JenisKayu'] ?? $item['GroupKayu'] ?? ''));
            $grade = trim((string) ($item['NamaGrade'] ?? $item['Grade'] ?? ''));
            $jenisDisplay = $jenis !== ''
                ? trim($jenis . ($grade !== '' ? ' - ' . $grade : ''))
                : $grade;

            return [
                'NoSanding' => $noSanding !== '' ? $noSanding : null,
                'Tanggal' => $dateCreate !== '' ? $dateCreate : null,
                'NoSPK' => $noSpk !== '' ? $noSpk : null,
                'Jenis' => $jenisDisplay !== '' ? $jenisDisplay : null,
                'Tebal' => $this->toFloat($item['Tebal'] ?? null),
                'Lebar' => $this->toFloat($item['Lebar'] ?? null),
                'Panjang' => $this->toFloat($item['Panjang'] ?? null),
                'JmlhBatang' => $this->toInt($item['JmlhBatang'] ?? $item['JumlahBatang'] ?? $item['Batang'] ?? null),
                'M3' => $this->toFloat($item['M3'] ?? $item['Kubik'] ?? null),
                'Lokasi' => trim((string) ($item['Lokasi'] ?? $item['IdLokasi'] ?? $item['IDLokasi'] ?? $item['Lok'] ?? '')) ?: null,
            ];
        }, $rows);

        usort($normalized, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($b['Tanggal'] ?? ''), (string) ($a['Tanggal'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) ($a['NoSanding'] ?? ''), (string) ($b['NoSanding'] ?? ''));
        });

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $rows = $this->fetch();
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.sanding_hidup_detail.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(): array
    {
        $connectionName = config('reports.sanding_hidup_detail.database_connection');
        $procedure = (string) config('reports.sanding_hidup_detail.stored_procedure', 'SP_LapSandingHidupDetail');
        $syntax = (string) config('reports.sanding_hidup_detail.call_syntax', 'exec');
        $customQuery = config('reports.sanding_hidup_detail.query');
        $parameterCount = (int) config('reports.sanding_hidup_detail.parameter_count', 0);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Sanding (Hidup) detail belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Sanding (Hidup) detail dikonfigurasi untuk SQL Server. '
                . 'Set SANDING_HIDUP_DETAIL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'SANDING_HIDUP_DETAIL_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan SANDING_HIDUP_DETAIL_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? array_fill(0, $parameterCount, null) : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => $this->buildExecSql($procedure, $parameterCount),
            'call' => $this->buildCallSql($procedure, $parameterCount),
            default => $driver === 'sqlsrv'
                ? $this->buildExecSql($procedure, $parameterCount)
                : $this->buildCallSql($procedure, $parameterCount),
        };

        return $connection->select("SET NOCOUNT ON; {$sql}", []);
    }

    private function buildExecSql(string $procedure, int $parameterCount): string
    {
        if ($parameterCount <= 0) {
            return "EXEC {$procedure}";
        }

        return "EXEC {$procedure} " . implode(', ', array_fill(0, $parameterCount, '?'));
    }

    private function buildCallSql(string $procedure, int $parameterCount): string
    {
        if ($parameterCount <= 0) {
            return "CALL {$procedure}()";
        }

        return "CALL {$procedure}(" . implode(', ', array_fill(0, $parameterCount, '?')) . ")";
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
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

    private function toInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        $f = $this->toFloat($value);
        if ($f === null) {
            return null;
        }

        return (int) round($f);
    }
}
