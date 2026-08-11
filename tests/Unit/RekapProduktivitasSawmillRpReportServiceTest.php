<?php

namespace Tests\Unit;

use App\Services\RekapProduktivitasSawmillRpReportService;
use Tests\TestCase;

class RekapProduktivitasSawmillRpReportServiceTest extends TestCase
{
    public function test_upah_racip_is_calculated_from_st_ton(): void
    {
        $service = new class extends RekapProduktivitasSawmillRpReportService
        {
            public function fetchMain(string $startDate, string $endDate): array
            {
                return [
                    [
                        'Tanggal' => '2026-05-22',
                        'NoPenST' => 'PEN-001',
                        'NoKB' => 'KB-001',
                        'InOut' => 'input',
                        'NamaGrade' => 'RAMBUNG',
                        'KB (Ton)' => 10.0,
                        'ST (Ton)' => 0.0,
                        'Harga' => 1000.0,
                    ],
                    [
                        'Tanggal' => '2026-05-22',
                        'NoPenST' => 'PEN-001',
                        'NoKB' => 'KB-001',
                        'InOut' => 'output',
                        'NamaGrade' => 'STD',
                        'KB (Ton)' => 0.0,
                        'ST (Ton)' => 2.5,
                        'Harga' => 5000.0,
                    ],
                ];
            }

            public function fetchSub(string $startDate, string $endDate): array
            {
                return [];
            }
        };

        $smallRate = $service->buildReportData('2026-05-22', '2026-05-22', 450.0);
        $perTon = $service->buildReportData('2026-05-22', '2026-05-22', 450000.0);

        $this->assertSame(1125.0, $smallRate['grand_totals']['money']['upah']);
        $this->assertSame(1125000.0, $perTon['grand_totals']['money']['upah']);
        $this->assertSame(450000.0, $perTon['summary']['upah_racip']);
    }

    public function test_receipt_generates_pie_chart_svg_and_chart_data(): void
    {
        $service = new class extends RekapProduktivitasSawmillRpReportService
        {
            public function fetchMain(string $startDate, string $endDate): array
            {
                return [
                    [
                        'Tanggal' => '2026-05-22',
                        'NoPenST' => 'PEN-001',
                        'NoKB' => 'KB-001',
                        'InOut' => 'input',
                        'NamaGrade' => 'RAMBUNG',
                        'KB (Ton)' => 10.0,
                        'ST (Ton)' => 0.0,
                        'Harga' => 1000.0,
                    ],
                    [
                        'Tanggal' => '2026-05-22',
                        'NoPenST' => 'PEN-001',
                        'NoKB' => 'KB-001',
                        'InOut' => 'output',
                        'NamaGrade' => 'STD',
                        'KB (Ton)' => 0.0,
                        'ST (Ton)' => 6.0,
                        'Harga' => 5000.0,
                    ],
                    [
                        'Tanggal' => '2026-05-22',
                        'NoPenST' => 'PEN-001',
                        'NoKB' => 'KB-001',
                        'InOut' => 'output',
                        'NamaGrade' => 'MC 1',
                        'KB (Ton)' => 0.0,
                        'ST (Ton)' => 4.0,
                        'Harga' => 5000.0,
                    ],
                ];
            }

            public function fetchSub(string $startDate, string $endDate): array
            {
                return [];
            }
        };

        $report = $service->buildReportData('2026-05-22', '2026-05-22', 450.0);
        $receipt = $report['date_groups'][0]['receipts'][0];

        $this->assertNotEmpty($receipt['chart_svg']);
        $this->assertStringStartsWith('<svg', $receipt['chart_svg']);
        $this->assertStringContainsString('polygon', $receipt['chart_svg']);
        $this->assertStringContainsString('MC 1', $receipt['chart_svg']);
        $this->assertStringContainsString('STD', $receipt['chart_svg']);
        $this->assertSame(2, count($receipt['chart_data']));
        $this->assertSame(60.0, $receipt['chart_data'][0]['percent']);
        $this->assertSame(40.0, $receipt['chart_data'][1]['percent']);
    }
}
