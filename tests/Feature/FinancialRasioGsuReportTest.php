<?php

namespace Tests\Feature;

use App\Services\Ascends\Shared\GeneralLedger\TrialBalanceMonthly\FinancialRasioGsuReportService;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class FinancialRasioGsuReportTest extends TestCase
{
    public function test_ebitda_uses_depreciation_from_general_expenses_per_month(): void
    {
        $reportData = app(FinancialRasioGsuReportService::class)->buildReportDataFromXml($this->trialBalanceXml());
        $ebitda = $this->ratio($reportData, 'ebitda');

        $this->assertSame('Januari', $ebitda['rows'][0]['bulan']);
        $this->assertSame(25.0, $ebitda['rows'][0]['nilai_y']);
        $this->assertSame(845.0, $ebitda['rows'][0]['nilai_x']);
        $this->assertSame(870.0, $ebitda['rows'][0]['rasio']);

        $this->assertSame('Februari', $ebitda['rows'][1]['bulan']);
        $this->assertSame(50.0, $ebitda['rows'][1]['nilai_y']);
        $this->assertSame(1950.0, $ebitda['rows'][1]['nilai_x']);
        $this->assertSame(2000.0, $ebitda['rows'][1]['rasio']);
    }

    public function test_bank_interest_tax_expense_is_not_treated_as_depreciation(): void
    {
        $reportData = app(FinancialRasioGsuReportService::class)->buildReportDataFromXml(<<<'XML'
<NewDataSet>
    <Table1><AccountCode1>411.000.001</AccountCode1><PeriodDate>2026-01-01</PeriodDate><Ending>1000</Ending></Table1>
    <Table1><AccountCode1>721.000.213</AccountCode1><PeriodDate>2026-01-01</PeriodDate><Ending>77</Ending></Table1>
</NewDataSet>
XML);

        $ebitda = $this->ratio($reportData, 'ebitda');

        // 721.000.213 = "BU - Beban Bank - Pajak Bunga Bank", bukan penyusutan.
        $this->assertSame(0.0, $ebitda['rows'][0]['nilai_y']);

        // Tetap diperlakukan sebagai operating expense pada rasio Opex.
        $opex = $this->ratio($reportData, 'opex_ratio');
        $this->assertSame(77.0, $opex['rows'][0]['nilai_x']);
        $this->assertEqualsWithDelta(7.7, $opex['rows'][0]['rasio'], 0.00001);
    }

    public function test_revenue_run_rate_rasio_shows_month_over_month_growth_percentage(): void
    {
        $reportData = app(FinancialRasioGsuReportService::class)->buildReportDataFromXml($this->trialBalanceXml());
        $runRate = $this->ratio($reportData, 'revenue_run_rate');

        $this->assertSame(1000.0, $runRate['rows'][0]['nilai_x']);
        $this->assertSame(12000.0, $runRate['rows'][0]['nilai_y']);
        $this->assertEqualsWithDelta(0.0, $runRate['rows'][0]['rasio'], 0.00001);

        $this->assertSame(2000.0, $runRate['rows'][1]['nilai_x']);
        $this->assertSame(24000.0, $runRate['rows'][1]['nilai_y']);
        $this->assertSame(100.0, $runRate['rows'][1]['rasio']);
    }

    public function test_pdf_renders_five_columns_for_run_rate_with_percentage(): void
    {
        $reportData = app(FinancialRasioGsuReportService::class)->buildReportDataFromXml($this->trialBalanceXml());

        $xpath = $this->renderReport($reportData);
        $sections = $xpath->query('//div[contains(@class, "ratio-section")][p[contains(@class, "ratio-title") and normalize-space()="Revenue Run Rate"]]');

        $this->assertCount(1, $sections);
        $section = $sections->item(0);
        $headers = $xpath->query('.//thead/tr/th', $section);
        $cells = $xpath->query('.//tbody/tr[2]/td', $section);

        $this->assertCount(5, $headers);
        $this->assertSame('Rasio %', trim($headers->item(4)->textContent));
        $this->assertCount(5, $cells);
        $this->assertSame('2,000', trim($cells->item(2)->textContent));
        $this->assertSame('24,000', trim($cells->item(3)->textContent));
        $this->assertSame('100.00%', trim($cells->item(4)->textContent));
    }

    private function renderReport(array $reportData): DOMXPath
    {
        $html = view('ascends.shared.general_ledger.trial_balance_monthly.financial_rasio_gsu.pdf', [
            'reportData' => $reportData,
        ])->render();
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }

    private function ratio(array $reportData, string $id): array
    {
        foreach ($reportData['ratios'] as $ratio) {
            if ($ratio['id'] === $id) {
                return $ratio;
            }
        }

        $this->fail("Rasio {$id} tidak ditemukan.");
    }

    private function trialBalanceXml(): string
    {
        return <<<'XML'
<NewDataSet>
    <Table1><AccountCode1>411.000.001</AccountCode1><PeriodDate>2026-01-01</PeriodDate><Ending>1000</Ending></Table1>
    <Table1><AccountCode1>516.000.001</AccountCode1><PeriodDate>2026-01-01</PeriodDate><Ending>100</Ending></Table1>
    <Table1><AccountCode1>721.000.201</AccountCode1><PeriodDate>2026-01-01</PeriodDate><Ending>25</Ending></Table1>
    <Table1><AccountCode1>721.000.171</AccountCode1><PeriodDate>2026-01-01</PeriodDate><Ending>5</Ending></Table1>
    <Table1><AccountCode1>721.000.212</AccountCode1><PeriodDate>2026-01-01</PeriodDate><Ending>30</Ending></Table1>
    <Table1><AccountCode1>500.001.000</AccountCode1><PeriodDate>2026-01-01</PeriodDate><Ending>120</Ending></Table1>
    <Table1><AccountCode1>411.000.001</AccountCode1><PeriodDate>2026-02-01</PeriodDate><Ending>2000</Ending></Table1>
    <Table1><AccountCode1>721.000.201</AccountCode1><PeriodDate>2026-02-01</PeriodDate><Ending>50</Ending></Table1>
</NewDataSet>
XML;
    }
}
