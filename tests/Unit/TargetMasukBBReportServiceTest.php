<?php

namespace Tests\Unit;

use App\Services\TargetMasukBBReportService;
use Tests\TestCase;

class TargetMasukBBReportServiceTest extends TestCase
{
    public function test_daily_under_target_flags_use_cumulative_working_day_target(): void
    {
        config()->set('reports.target_masuk_bb.database_connection', null);
        config()->set('reports.target_masuk_bb.call_syntax', 'query');
        config()->set('reports.target_masuk_bb.parameter_count', 0);
        config()->set('reports.target_masuk_bb.query', implode(' UNION ALL ', [
            "SELECT 'JABON' AS NamaGroup, 8 AS TgtPerHari, 200 AS TargetBulanan, 0 AS hasil, '2026-01-01' AS Date, '' AS Keterangan",
            "SELECT 'JABON' AS NamaGroup, 8 AS TgtPerHari, 200 AS TargetBulanan, 0 AS hasil, '2026-01-02' AS Date, '' AS Keterangan",
            "SELECT 'RAMBUNG' AS NamaGroup, 8 AS TgtPerHari, 200 AS TargetBulanan, 129 AS hasil, '2026-01-01' AS Date, '' AS Keterangan",
            "SELECT 'RAMBUNG' AS NamaGroup, 8 AS TgtPerHari, 200 AS TargetBulanan, 26 AS hasil, '2026-01-02' AS Date, '' AS Keterangan",
        ]));

        $reportData = (new TargetMasukBBReportService)->buildReportData('2026-01-01', '2026-01-02');
        $rowsByJenis = collect($reportData['table_rows'])->keyBy('jenis');

        $this->assertSame([true, true], $rowsByJenis['JABON']['daily_under_target_flags']);
        $this->assertSame([false, false], $rowsByJenis['RAMBUNG']['daily_under_target_flags']);
    }

    public function test_lb_columns_can_be_flagged_without_adding_working_day_target(): void
    {
        config()->set('reports.target_masuk_bb.database_connection', null);
        config()->set('reports.target_masuk_bb.call_syntax', 'query');
        config()->set('reports.target_masuk_bb.parameter_count', 0);
        config()->set('reports.target_masuk_bb.query', implode(' UNION ALL ', [
            "SELECT 'JABON' AS NamaGroup, 8 AS TgtPerHari, 200 AS TargetBulanan, 0 AS hasil, '2026-01-01' AS Date, '' AS Keterangan",
            "SELECT 'JABON' AS NamaGroup, 8 AS TgtPerHari, 200 AS TargetBulanan, 0 AS hasil, '2026-01-02' AS Date, 'LIBUR' AS Keterangan",
        ]));

        $reportData = (new TargetMasukBBReportService)->buildReportData('2026-01-01', '2026-01-02');
        $row = collect($reportData['table_rows'])->firstWhere('jenis', 'JABON');

        $this->assertSame([true, true], $row['daily_under_target_flags']);
    }
}
