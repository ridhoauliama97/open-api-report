<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class FingerJointHidupDetailReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();

        $normalized = array_map(function ($row): array {
            $item = (array) $row;

            $noFj = trim((string) ($item['NoFJ'] ?? $item['NoFj'] ?? $item['No_FJ'] ?? $item['NoFingerJoint'] ?? ''));
            $dateCreate = trim((string) ($item['DateCreate'] ?? $item['TglCreate'] ?? $item['Tanggal'] ?? $item['Tgl'] ?? ''));
            $noSpk = trim((string) ($item['NoSPK'] ?? $item['NoSpk'] ?? $item['SPK'] ?? ''));

            $jenis = trim((string) ($item['Jenis'] ?? $item['JenisKayu'] ?? $item['GroupKayu'] ?? ''));
            $grade = trim((string) ($item['NamaGrade'] ?? $item['Grade'] ?? ''));
            $jenisDisplay = $jenis !== ''
                ? trim($jenis . ($grade !== '' ? ' - ' . $grade : ''))
                : $grade;

            $tebal = $this->toFloat($item['Tebal'] ?? null);
            $lebar = $this->toFloat($item['Lebar'] ?? null);
            $panjang = $this->toFloat($item['Panjang'] ?? null);

            $jmlhBatang = $this->toInt($item['JmlhBatang'] ?? $item['JumlahBatang'] ?? $item['Batang'] ?? null);
            $m3 = $this->toFloat($item['M3'] ?? $item['Kubik'] ?? $item['M3Kubik'] ?? null);
            $lokasi = trim((string) ($item['Lokasi'] ?? $item['IdLokasi'] ?? $item['IDLokasi'] ?? $item['Lok'] ?? ''));

            return [
                'NoFJ' => $noFj !== '' ? $noFj : null,
                'DateCreate' => $dateCreate !== '' ? $dateCreate : null,
                'NoSPK' => $noSpk !== '' ? $noSpk : null,
                'Jenis' => $jenisDisplay !== '' ? $jenisDisplay : null,
                'Tebal' => $tebal,
                'Lebar' => $lebar,
                'Panjang' => $panjang,
                'JmlhBatang' => $jmlhBatang,
                'M3' => $m3,
                'Lokasi' => $lokasi !== '' ? $lokasi : null,
            ];
        }, $rows);

        // Match the typical "Hidup Detail" expectation: DateCreate DESC, then NoFJ ASC.
        usort($normalized, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($b['DateCreate'] ?? ''), (string) ($a['DateCreate'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) ($a['NoFJ'] ?? ''), (string) ($b['NoFJ'] ?? ''));
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
        $expectedColumns = config('reports.finger_joint_hidup_detail.expected_columns', []);
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
        $connectionName = config('reports.finger_joint_hidup_detail.database_connection');
        $procedure = (string) config('reports.finger_joint_hidup_detail.stored_procedure', 'SP_LapFingerJointHidupDetail');
        $syntax = (string) config('reports.finger_joint_hidup_detail.call_syntax', 'exec');
        $customQuery = config('reports.finger_joint_hidup_detail.query');
        $parameterCount = (int) config('reports.finger_joint_hidup_detail.parameter_count', 0);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Finger Joint (Hidup) detail belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        $safeParameterCount = $parameterCount > 0 ? $parameterCount : 0;
        $bindings = [];

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Finger Joint (Hidup) detail dikonfigurasi untuk SQL Server. '
                . 'Set FINGER_JOINT_HIDUP_DETAIL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'FINGER_JOINT_HIDUP_DETAIL_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan FINGER_JOINT_HIDUP_DETAIL_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => $this->buildExecSql($procedure, $safeParameterCount),
            'call' => $this->buildCallSql($procedure, $safeParameterCount),
            default => $driver === 'sqlsrv'
                ? $this->buildExecSql($procedure, $safeParameterCount)
                : $this->buildCallSql($procedure, $safeParameterCount),
        };

        return $connection->select("SET NOCOUNT ON; {$sql}", $bindings);
    }

    private function buildExecSql(string $procedure, int $parameterCount): string
    {
        if ($parameterCount <= 0) {
            return "EXEC {$procedure}";
        }

        $placeholders = implode(', ', array_fill(0, $parameterCount, '?'));

        return "EXEC {$procedure} {$placeholders}";
    }

    private function buildCallSql(string $procedure, int $parameterCount): string
    {
        if ($parameterCount <= 0) {
            return "CALL {$procedure}()";
        }

        $placeholders = implode(', ', array_fill(0, $parameterCount, '?'));

        return "CALL {$procedure}({$placeholders})";
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

