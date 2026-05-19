<?php

namespace Tests\Unit;

use App\Services\UmurCrossCutAkhirDetailReportService;
use Tests\TestCase;

class UmurCrossCutAkhirDetailReportServiceTest extends TestCase
{
    public function test_fetch_groups_same_jenis_dimension_rows_and_sums_age_periods(): void
    {
        config()->set('reports.umur_cross_cut_akhir_detail.database_connection', null);
        config()->set('reports.umur_cross_cut_akhir_detail.call_syntax', 'query');
        config()->set('reports.umur_cross_cut_akhir_detail.parameter_count', 0);
        config()->set('reports.umur_cross_cut_akhir_detail.query', implode(' UNION ALL ', [
            $this->selectAgeRow("'JABON'", "'A/A'", 22, 43, 1000, 'NULL', 1.0000, 'NULL', 'NULL', 'NULL', 99.9999),
            $this->selectAgeRow("'JABON'", "'A/A'", 22, 43, 1000, 'NULL', 0.6308, 'NULL', 'NULL', 'NULL', 88.8888),
            $this->selectAgeRow("'JABON'", "'A/A'", 22, 43, 900, 0.2500, 'NULL', 'NULL', 'NULL', 'NULL', 77.7777),
        ]));

        $rows = (new UmurCrossCutAkhirDetailReportService)->fetch([
            'Umur1' => 15,
            'Umur2' => 30,
            'Umur3' => 60,
            'Umur4' => 90,
        ]);

        $this->assertCount(2, $rows);

        $groupedRow = collect($rows)->firstWhere('Panjang', 1000.0);

        $this->assertNotNull($groupedRow);
        $this->assertSame('JABON - A/A', $groupedRow['Jenis']);
        $this->assertSame(22.0, $groupedRow['Tebal']);
        $this->assertSame(43.0, $groupedRow['Lebar']);
        $this->assertEqualsWithDelta(1.6308, $groupedRow['Period2'], 0.00001);
        $this->assertEqualsWithDelta(1.6308, $groupedRow['Total'], 0.00001);

        $separateRow = collect($rows)->firstWhere('Panjang', 900.0);

        $this->assertNotNull($separateRow);
        $this->assertEqualsWithDelta(0.2500, $separateRow['Period1'], 0.00001);
        $this->assertEqualsWithDelta(0.2500, $separateRow['Total'], 0.00001);
    }

    private function selectAgeRow(
        string $jenis,
        string $namaGrade,
        int $tebal,
        int $lebar,
        int $panjang,
        string|float $period1,
        string|float $period2,
        string|float $period3,
        string|float $period4,
        string|float $period5,
        float $total,
    ): string {
        return sprintf(
            'SELECT %s AS Jenis, %s AS NamaGrade, %d AS Tebal, %d AS Lebar, %d AS Panjang, %s AS Period1, %s AS Period2, %s AS Period3, %s AS Period4, %s AS Period5, %F AS Total',
            $jenis,
            $namaGrade,
            $tebal,
            $lebar,
            $panjang,
            $period1,
            $period2,
            $period3,
            $period4,
            $period5,
            $total,
        );
    }
}
