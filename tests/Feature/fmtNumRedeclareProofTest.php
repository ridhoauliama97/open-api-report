<?php

namespace Tests\Feature;

use App\Services\PdfGenerator;
use Tests\TestCase;

class fmtNumRedeclareProofTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
  <Table>
    <VoucherDate>2026-01-15T00:00:00+07:00</VoucherDate>
    <LowestDescription>Solar</LowestDescription>
    <AccountName>Bahan Bakar</AccountName>
    <Amt>500000</Amt>
  </Table>
</NewDataSet>
XML;

    public function test_rendering_two_custom_report_views_in_one_process_does_not_crash(): void
    {
        $pdfGenerator = app(PdfGenerator::class);

        // Render view 1: biaya_mobil_truk (defines fmtNum_biaya_mobil_truk)
        $html1 = view('ascends.shared.custom_report.biaya_mobil_truk.pdf', [
            'company' => 'GSU',
            'headerCompany' => 'GSU',
            'headerTitle' => 'Test Report 1',
            'reportData' => [
                'sections' => [],
                'grand_totals' => ['values' => [], 'total' => 0, 'rata2' => 0, 'terendah' => 0, 'tertinggi' => 0],
                'months' => [],
                'start_date' => '',
                'end_date' => '',
            ],
            'generatedAt' => now(),
        ])->render();

        $this->assertNotEmpty($html1);

        // Render view 2: pengiriman_per_kategori_harian (defines fmtNum_pengiriman_per_kategori_harian)
        $html2 = view('ascends.shared.custom_report.pengiriman_per_kategori_harian.pdf', [
            'company' => 'GSU',
            'headerCompany' => 'GSU',
            'headerTitle' => 'Test Report 2',
            'reportData' => [
                'rows' => [],
                'day_numbers' => [],
                'day_labels' => [],
                'totals' => [],
                'grand_total' => [],
                'start_date' => '',
                'end_date' => '',
            ],
            'generatedAt' => now(),
        ])->render();

        $this->assertNotEmpty($html2);

        // If we got here without "Cannot redeclare" error, the guard works
        $this->assertTrue(true);
    }
}
