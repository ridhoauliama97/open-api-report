<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\PerbandinganJumlahKaryawanTahunanPerBulanReportService;
use App\Services\PdfGenerator;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsPerbandinganJumlahKaryawanTahunanPerBulanReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_shared_hrm_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(PerbandinganJumlahKaryawanTahunanPerBulanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.perbandingan_jumlah_karyawan_tahunan_per_bulan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'UC'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (UC)'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PerbandinganJumlahKaryawanTahunanPerBulanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/perbandingan-jumlah-karyawan-tahunan-per-bulan/pdf', [
            'company' => 'UC',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (UC)');
    }

    public function test_parser_builds_yearly_monthly_comparison_from_xml_year_month_fields(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-02 10:00:00'));

        $reportData = app(PerbandinganJumlahKaryawanTahunanPerBulanReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'Bulan',
            'Total Karyawan',
            'Karyawan Masuk',
            '% Masuk',
            'Karyawan Keluar',
            '% Keluar',
            '% Karyawan',
            'MPP',
            'GAP',
            '% GAP',
            'Remark',
        ], $reportData['headers']);
        $this->assertSame('2026-06-01', $reportData['per_date']);
        $this->assertCount(2, $reportData['yearly_rows']);
        $this->assertSame(2025, $reportData['yearly_rows'][0]['year']);
        $this->assertSame(2026, $reportData['yearly_rows'][1]['year']);

        $january2025 = $reportData['yearly_rows'][0]['rows'][0];
        $february2025 = $reportData['yearly_rows'][0]['rows'][1];
        $january2026 = $reportData['yearly_rows'][1]['rows'][0];

        $this->assertSame('Januari', $january2025['Bulan']);
        $this->assertSame(3, $january2025['Total Karyawan']);
        $this->assertSame(2, $january2025['Karyawan Masuk']);
        $this->assertSame(0, $january2025['Karyawan Keluar']);
        $this->assertSame(80, $january2025['MPP']);
        $this->assertSame(-77, $january2025['GAP']);

        $this->assertSame(3, $february2025['Total Karyawan']);
        $this->assertSame(1, $february2025['Karyawan Masuk']);
        $this->assertSame(1, $february2025['Karyawan Keluar']);

        $this->assertSame('Januari', $january2026['Bulan']);
        $this->assertSame(4, $january2026['Total Karyawan']);
        $this->assertSame(1, $january2026['Karyawan Masuk']);
        $this->assertSame(0, $january2026['Karyawan Keluar']);
        $this->assertSame('Ridho', $reportData['printed_by']);
        $this->assertSame(5, $reportData['total_rows']);
    }

    public function test_pdf_blade_renders_expected_tables_and_summaries(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-02 10:00:00'));

        $reportData = app(PerbandinganJumlahKaryawanTahunanPerBulanReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');
        $reportData['title'] = 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (UC)';

        $html = view('ascends.shared.hrm.perbandingan_jumlah_karyawan_tahunan_per_bulan.pdf', [
            'company' => 'UC',
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (UC)', $html);
        $this->assertStringContainsString('Per 01-Jun-26', $html);
        $this->assertStringContainsString('Tahun : 2025', $html);
        $this->assertStringContainsString('Tahun : 2026', $html);
        $this->assertStringContainsString('Total<br>Karyawan', $html);
        $this->assertStringContainsString('Karyawan Masuk', $html);
        $this->assertStringContainsString('Karyawan Keluar', $html);
        $this->assertStringContainsString('Akumulasi Karyawan Masuk Per Bulan', $html);
        $this->assertStringContainsString('Akumulasi Total Karyawan Akhir Per Bulan', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'printed_at' => '02 June 2026 16:34',
            'printed_by' => 'Ridho',
            'company' => 'UC',
            'title' => 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (UC)',
            'headers' => ['Bulan', 'Total Karyawan'],
            'yearly_rows' => [],
            'rows' => [],
            'total_rows' => 4,
        ];
    }

    private function employeeListXml(string $recordTag = 'Employees'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>1001</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Lama</Full_x0020_Name>
        <Join_x0020_Date_x0020__x0028_Year_x0029_>2024</Join_x0020_Date_x0020__x0028_Year_x0029_>
        <Join_x0020_Date_x0020__x0028_Month_x0029_>12</Join_x0020_Date_x0020__x0028_Month_x0029_>
        <Termination_x0020_Date_x0020__x0028_Year_x0029_>0</Termination_x0020_Date_x0020__x0028_Year_x0029_>
        <Termination_x0020_Date_x0020__x0028_Month_x0029_>0</Termination_x0020_Date_x0020__x0028_Month_x0029_>
        <Nama_x0020_User>Ridho</Nama_x0020_User>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1002</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Masuk Januari</Full_x0020_Name>
        <Join_x0020_Date_x0020__x0028_Year_x0029_>2025</Join_x0020_Date_x0020__x0028_Year_x0029_>
        <Join_x0020_Date_x0020__x0028_Month_x0029_>1</Join_x0020_Date_x0020__x0028_Month_x0029_>
        <Termination_x0020_Date_x0020__x0028_Year_x0029_>0</Termination_x0020_Date_x0020__x0028_Year_x0029_>
        <Termination_x0020_Date_x0020__x0028_Month_x0029_>0</Termination_x0020_Date_x0020__x0028_Month_x0029_>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1003</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Keluar Februari</Full_x0020_Name>
        <Join_x0020_Date_x0020__x0028_Year_x0029_>2025</Join_x0020_Date_x0020__x0028_Year_x0029_>
        <Join_x0020_Date_x0020__x0028_Month_x0029_>1</Join_x0020_Date_x0020__x0028_Month_x0029_>
        <Termination_x0020_Date_x0020__x0028_Year_x0029_>2025</Termination_x0020_Date_x0020__x0028_Year_x0029_>
        <Termination_x0020_Date_x0020__x0028_Month_x0029_>2</Termination_x0020_Date_x0020__x0028_Month_x0029_>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1004</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Masuk Februari</Full_x0020_Name>
        <Join_x0020_Date_x0020__x0028_Year_x0029_>2025</Join_x0020_Date_x0020__x0028_Year_x0029_>
        <Join_x0020_Date_x0020__x0028_Month_x0029_>2</Join_x0020_Date_x0020__x0028_Month_x0029_>
        <Termination_x0020_Date_x0020__x0028_Year_x0029_>0</Termination_x0020_Date_x0020__x0028_Year_x0029_>
        <Termination_x0020_Date_x0020__x0028_Month_x0029_>0</Termination_x0020_Date_x0020__x0028_Month_x0029_>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1005</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Masuk Tahun Ini</Full_x0020_Name>
        <Join_x0020_Date_x0020__x0028_Year_x0029_>2026</Join_x0020_Date_x0020__x0028_Year_x0029_>
        <Join_x0020_Date_x0020__x0028_Month_x0029_>1</Join_x0020_Date_x0020__x0028_Month_x0029_>
        <Termination_x0020_Date_x0020__x0028_Year_x0029_>0</Termination_x0020_Date_x0020__x0028_Year_x0029_>
        <Termination_x0020_Date_x0020__x0028_Month_x0029_>0</Termination_x0020_Date_x0020__x0028_Month_x0029_>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 1</Employee_x0020_Code>
        <Full_x0020_Name>Special User</Full_x0020_Name>
        <Join_x0020_Date_x0020__x0028_Year_x0029_>2026</Join_x0020_Date_x0020__x0028_Year_x0029_>
        <Join_x0020_Date_x0020__x0028_Month_x0029_>1</Join_x0020_Date_x0020__x0028_Month_x0029_>
        <Termination_x0020_Date_x0020__x0028_Year_x0029_>0</Termination_x0020_Date_x0020__x0028_Year_x0029_>
        <Termination_x0020_Date_x0020__x0028_Month_x0029_>0</Termination_x0020_Date_x0020__x0028_Month_x0029_>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
