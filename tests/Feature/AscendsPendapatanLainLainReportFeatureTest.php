<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\PendapatanLainLainReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsPendapatanLainLainReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_other_income_deduction_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->otherIncomeXml();

        $service = Mockery::mock(PendapatanLainLainReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: other-income.xml')
            ->andReturn($this->reportData('GSU'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.other_income_deduction.pendapatan_lain_lain.pdf', Mockery::on(
                static fn(array $data): bool => ($data['company'] ?? null) === 'GSU'
                && ($data['reportData']['title'] ?? null) === 'Laporan Pendapatan Lain-Lain'
                && ($data['reportData']['printed_by'] ?? null) === 'Windi'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PendapatanLainLainReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/other-income-deduction/pendapatan-lain-lain/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Windi',
            'xml_file' => UploadedFile::fake()->createWithContent('other-income.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Other Income Deduction - Laporan Pendapatan Lain Lain (GSU)');
    }

    public function test_parser_excludes_employeeother_and_sorts_by_full_name(): void
    {
        $reportData = app(PendapatanLainLainReportService::class)
            ->buildReportDataFromXml($this->otherIncomeXml(), 'test xml');

        $this->assertSame('Laporan Pendapatan Lain-Lain', $reportData['title']);
        $this->assertSame('Penambahan', $reportData['section_title']);
        $this->assertSame('Per : Juni-2026', $reportData['period']['label']);
        $this->assertSame(2, $reportData['total_rows']);
        $this->assertSame('Angraini', $reportData['rows'][0]['Nama Lengkap']);
        $this->assertSame('29-Mei-26', $reportData['rows'][0]['Tanggal']);
        $this->assertSame('Dina', $reportData['rows'][0]['Disetujui Oleh']);
        $this->assertSame('0', $reportData['total_amount']);
        $this->assertSame(['Angraini', 'Weni Asi Gaho'], array_column($reportData['rows'], 'Nama Lengkap'));
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company): array
    {
        return [
            'printed_at' => '31 May 2026 13:26',
            'printed_by' => 'Ridho',
            'company' => $company,
            'title' => 'Laporan Pendapatan Lain-Lain',
            'section_title' => 'Penambahan',
            'headers' => ['Nama Lengkap', 'Tanggal', 'Keterangan', 'Disetujui Oleh', 'Jumlah'],
            'rows' => [],
            'total_rows' => 0,
            'total_amount' => '0',
            'period' => ['label' => 'Per : Juni-2026'],
        ];
    }

    private function otherIncomeXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <income>
        <Full_x0020_Name>Weni Asi Gaho</Full_x0020_Name>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Approved_x0020_By>Dina</Approved_x0020_By>
        <Amount>0</Amount>
        <Remarks>Pot. II Kasus Short Door Denda Rp 1.000.000/ Orang (5 kali potong)</Remarks>
        <Payroll_x0020_Period_x0020__x0028_Start_x0029_>MAY-2026</Payroll_x0020_Period_x0020__x0028_Start_x0029_>
    </income>
    <income>
        <Full_x0020_Name>Charlie Kwekianto</Full_x0020_Name>
        <Date>2026-05-13T00:00:00+07:00</Date>
        <Approved_x0020_By>employeeother</Approved_x0020_By>
        <Amount>0</Amount>
        <Remarks>Pot. Seragam Batik 1 pcs</Remarks>
        <Payroll_x0020_Period_x0020__x0028_Start_x0029_>MAY-2026</Payroll_x0020_Period_x0020__x0028_Start_x0029_>
    </income>
    <income>
        <Full_x0020_Name>Angraini</Full_x0020_Name>
        <Date>2026-05-29T00:00:00+07:00</Date>
        <Approved_x0020_By>Dina</Approved_x0020_By>
        <Amount>0</Amount>
        <Remarks>Pot. Baju Seragam 1 Pcs</Remarks>
        <Payroll_x0020_Period_x0020__x0028_Start_x0029_>MAY-2026</Payroll_x0020_Period_x0020__x0028_Start_x0029_>
    </income>
</NewDataSet>
XML;
    }
}
