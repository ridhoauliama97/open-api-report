<?php

namespace Tests\Unit;

use App\Services\DashboardSawnTimberReportService;
use Tests\TestCase;

class DashboardSawnTimberReportServiceTest extends TestCase
{
    public function test_chart_data_keeps_full_requested_date_range_and_stock_percentages(): void
    {
        config()->set('reports.dashboard_sawn_timber.type_order', ['JABON', 'PULAI']);
        config()->set('reports.dashboard_sawn_timber.ctr_divisor', 75);

        $service = new class extends DashboardSawnTimberReportService
        {
            public function fetch(string $startDate, string $endDate): array
            {
                return [
                    [
                        'Tanggal' => '2026-05-02',
                        'Jenis' => 'JABON',
                        'Masuk' => 1.0,
                        'Keluar' => 0.5,
                        'SAkhir' => 25.0,
                        'Ctr' => 0.33,
                    ],
                    [
                        'Tanggal' => '2026-05-15',
                        'Jenis' => 'PULAI',
                        'Masuk' => 2.0,
                        'Keluar' => 1.0,
                        'SAkhir' => 75.0,
                        'Ctr' => 1.0,
                    ],
                ];
            }
        };

        $reportData = $service->buildChartData('2026-05-01', '2026-05-15');

        $this->assertCount(15, $reportData['dates']);
        $this->assertSame('2026-05-01', $reportData['dates'][0]);
        $this->assertSame('2026-05-15', $reportData['dates'][14]);
        $this->assertSame(['JABON', 'PULAI'], $reportData['types']);
        $this->assertSame(25.0, $reportData['stock_percent_by_type']['JABON']);
        $this->assertSame(75.0, $reportData['stock_percent_by_type']['PULAI']);
    }
}
