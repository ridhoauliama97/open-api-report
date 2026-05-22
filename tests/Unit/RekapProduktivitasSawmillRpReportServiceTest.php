<?php

namespace Tests\Unit;

use App\Services\RekapProduktivitasSawmillRpReportService;
use Tests\TestCase;

class RekapProduktivitasSawmillRpReportServiceTest extends TestCase
{
    public function test_upah_racip_accepts_per_kg_and_per_ton_values(): void
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

        $perKg = $service->buildReportData('2026-05-22', '2026-05-22', 450.0);
        $perTon = $service->buildReportData('2026-05-22', '2026-05-22', 450000.0);

        $this->assertSame(
            $perKg['grand_totals']['money']['upah'],
            $perTon['grand_totals']['money']['upah'],
        );
        $this->assertSame(1125000.0, $perTon['grand_totals']['money']['upah']);
        $this->assertSame(450.0, $perTon['summary']['upah_racip']);
    }
}
