<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TotalBagusKulitRambungReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $reportDate): array
    {
        $rows = $this->fetch($reportDate);

        return [
            'report_date' => $reportDate,
            'rows' => $rows,
            'summary' => [
                'total_rows' => count($rows),
                'total_bagus' => array_sum(array_map(static fn (array $row): int => (int) ($row['Bagus'] ?? 0), $rows)),
                'total_kulit' => array_sum(array_map(static fn (array $row): int => (int) ($row['Kulit'] ?? 0), $rows)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $reportDate): array
    {
        $rows = $this->fetch($reportDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = ['Jenis', 'Kategori', 'Tebal', 'Lebar', 'Panjang', 'Bagus', 'Kulit'];

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
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $reportDate): array
    {
        $connectionName = config('reports.total_bagus_kulit_rambung.database_connection');
        $gradeId = (int) config('reports.total_bagus_kulit_rambung.grade_id', 9);

        if ($gradeId <= 0) {
            throw new RuntimeException('Grade laporan total bagus/kulit rambung tidak valid.');
        }

        $sql = <<<'SQL'
SELECT
    COALESCE(F.Jenis, '-') AS Jenis,
    COALESCE(B.NamaGrade, '-') AS Kategori,
    D.Tebal,
    D.Lebar,
    D.Panjang,
    SUM(CASE WHEN D.IsBagusKulit = 1 THEN ISNULL(D.JmlhBatang, 0) ELSE 0 END) AS Bagus,
    SUM(CASE WHEN D.IsBagusKulit = 2 THEN ISNULL(D.JmlhBatang, 0) ELSE 0 END) AS Kulit
FROM STSawmillKG_d A
LEFT JOIN MstGradeKB B ON B.IdGradeKB = A.IdGradeKB
LEFT JOIN STSawmill_h C ON C.NoSTSawmill = A.NoSTSawmill
LEFT JOIN STSawmill_d D ON D.NoSTSawmill = A.NoSTSawmill AND D.NoUrut = A.NoUrut
LEFT JOIN KayuBulat_h E ON E.NoKayuBulat = C.NoKayuBulat
LEFT JOIN MstJenisKayu F ON F.IdJenisKayu = E.IdJenisKayu
WHERE A.IdGradeKB = ?
    AND CAST(C.TglSawmill AS date) = ?
GROUP BY COALESCE(F.Jenis, '-'), COALESCE(B.NamaGrade, '-'), D.Tebal, D.Lebar, D.Panjang
ORDER BY Jenis, Kategori, D.Tebal, D.Lebar, D.Panjang
SQL;

        try {
            $rows = DB::connection($connectionName ?: null)->select($sql, [$gradeId, $reportDate]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan total bagus/kulit rambung: '.$exception->getMessage(), 0, $exception);
        }

        return array_map(static function (object $row): array {
            $item = (array) $row;

            return [
                'Jenis' => trim((string) ($item['Jenis'] ?? '-')) ?: '-',
                'Kategori' => trim((string) ($item['Kategori'] ?? '-')) ?: '-',
                'Tebal' => self::toFloat($item['Tebal'] ?? null),
                'Lebar' => self::toFloat($item['Lebar'] ?? null),
                'Panjang' => self::toFloat($item['Panjang'] ?? null),
                'Bagus' => (int) ($item['Bagus'] ?? 0),
                'Kulit' => (int) ($item['Kulit'] ?? 0),
            ];
        }, $rows);
    }

    private static function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
