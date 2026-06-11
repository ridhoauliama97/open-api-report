<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\DurasiDendaKeterlambatanReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsDurasiDendaKeterlambatanReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_late_sign_in_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->lateEarlyXml();

        $service = Mockery::mock(DurasiDendaKeterlambatanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: late.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['company'] ?? null) === 'RU'
                    && ($filters['Pilih Type'] ?? null) === 'Staff'
                    && ($filters['DateInput'] ?? null) === '2026-05-01'
                    && ($filters['start_date'] ?? null) === '2026-06-01'
                    && ($filters['end_date'] ?? null) === '2026-06-30'
            ))
            ->andReturn($this->reportData('RU', 'Staff'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.late_sign_in.durasi_denda_keterlambatan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Durasi & Denda Keterlambatan (Staff) Per Departemen (RU)'
                    && ($data['reportData']['printed_by'] ?? null) === 'Windi'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DurasiDendaKeterlambatanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/late-sign-in/durasi-denda-keterlambatan/pdf', [
            'DB_CompanyName' => 'RU',
            'Sys_Username' => 'Windi',
            'Pilih Type' => 'Staff',
            'DateInput' => '2026-05-01',
            'StartDate' => '2026-06-01',
            'EndDate' => '2026-06-30',
            'xml_file' => UploadedFile::fake()->createWithContent('late.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Late Sign In - Laporan Durasi & Denda Keterlambatan Staff Per Departemen (RU)');
    }

    public function test_parser_filters_staff_late_sign_in_and_calculates_nominal(): void
    {
        $reportData = app(DurasiDendaKeterlambatanReportService::class)
            ->buildReportDataFromXml($this->lateEarlyXml(), 'test xml', [
                'Pilih Type' => 'Staff',
                'DateInput' => '2026-05-01',
            ]);

        $this->assertSame('Staff', $reportData['type']);
        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame('Karyawan Staff', $reportData['rows'][0]['Nama']);
        $this->assertSame(30, $reportData['rows'][0]['Total Menit']);
        $this->assertSame('0 Jam 30 Menit', $reportData['rows'][0]['Durasi']);
        $this->assertSame('Rp 45,000', $reportData['rows'][0]['Denda']);
        $this->assertSame(1, count($reportData['rows'][0]['details']));
        $this->assertNotContains('Karyawan Staff Tanpa Departemen', array_column($reportData['rows'], 'Nama'));
    }

    public function test_parser_filters_kk_kt_br_and_excludes_ignore_management_and_0101(): void
    {
        $reportData = app(DurasiDendaKeterlambatanReportService::class)
            ->buildReportDataFromXml($this->lateEarlyXml(), 'test xml', [
                'Pilih Type' => 'KK/KT',
                'DateInput' => '2026-05-01',
            ]);

        $names = array_column($reportData['rows'], 'Nama');

        $this->assertSame('KK/KT', $reportData['type']);
        $this->assertSame(2, $reportData['total_rows']);
        $this->assertContains('Karyawan KK', $names);
        $this->assertContains('Karyawan BR', $names);
        $this->assertNotContains('Karyawan Ignore', $names);
        $this->assertNotContains('Karyawan Management', $names);
        $this->assertNotContains('Karyawan ODP', $names);
    }

    public function test_parser_uses_full_month_period_from_xml_when_dates_are_not_sent(): void
    {
        $reportData = app(DurasiDendaKeterlambatanReportService::class)
            ->buildReportDataFromXml($this->lateEarlyXml(), 'test xml', [
                'Pilih Type' => 'KK/KT',
                'DateInput' => '2026-05-01',
            ]);

        $this->assertSame('2026-05-01', $reportData['period']['start_date']);
        $this->assertSame('2026-05-31', $reportData['period']['end_date']);
        $this->assertSame('Dari 01-Mei-26 Sampai 31-Mei-26', $reportData['period']['label']);
    }

    public function test_parser_uses_explicit_period_aliases_before_xml_month_fallback(): void
    {
        $reportData = app(DurasiDendaKeterlambatanReportService::class)
            ->buildReportDataFromXml($this->lateEarlyXml(), 'test xml', [
                'Pilih Type' => 'KK/KT',
                'AttendanceDate.StartDate' => '2026-06-01',
                'AttendanceDate.EndDate' => '2026-06-30',
            ]);

        $this->assertSame('2026-06-01', $reportData['period']['start_date']);
        $this->assertSame('2026-06-30', $reportData['period']['end_date']);
        $this->assertSame('Dari 01-Jun-26 Sampai 30-Jun-26', $reportData['period']['label']);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company, string $type): array
    {
        return [
            'printed_at' => '31 May 2026 13:26',
            'printed_by' => 'Ridho',
            'company' => $company,
            'type' => $type,
            'title' => "Laporan Durasi & Denda Keterlambatan ({$type}) Per Departemen ({$company})",
            'headers' => ['Nama', 'Jabatan', 'Level', 'Absen Masuk', 'Telat (Menit)'],
            'rows' => [],
            'grouped_rows' => [],
            'grand_summary' => ['total_minutes' => 0, 'total_nominal' => 0],
            'total_rows' => 0,
        ];
    }

    private function lateEarlyXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <absen>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Staff</Full_x0020_Name>
        <Department_x0020_Code>800</Department_x0020_Code>
        <Department_x0020_Name>HRGA</Department_x0020_Name>
        <Job_x0020_Title>Staff HR</Job_x0020_Title>
        <Level>2</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-02T08:30:00+07:00</Sign_x0020_In>
        <Late_x0020_Sign_x0020_In>30</Late_x0020_Sign_x0020_In>
        <Ignore_x0020_Late></Ignore_x0020_Late>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </absen>
    <absen>
        <Employee_x0020_Code>130002</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan KK</Full_x0020_Name>
        <Department_x0020_Code>800</Department_x0020_Code>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Level>1</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-02T08:20:00+07:00</Sign_x0020_In>
        <Late_x0020_Sign_x0020_In>20</Late_x0020_Sign_x0020_In>
        <Ignore_x0020_Late></Ignore_x0020_Late>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </absen>
    <absen>
        <Employee_x0020_Code>130003</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan BR</Full_x0020_Name>
        <Department_x0020_Code>800</Department_x0020_Code>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Job_x0020_Title>Operator Borongan</Job_x0020_Title>
        <Level>1</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-02T08:15:00+07:00</Sign_x0020_In>
        <Late_x0020_Sign_x0020_In>15</Late_x0020_Sign_x0020_In>
        <Ignore_x0020_Late></Ignore_x0020_Late>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </absen>
    <absen>
        <Employee_x0020_Code>130007</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Staff Tanpa Departemen</Full_x0020_Name>
        <Department_x0020_Code></Department_x0020_Code>
        <Department_x0020_Name>Tanpa Departemen</Department_x0020_Name>
        <Job_x0020_Title>Staff HR</Job_x0020_Title>
        <Level>2</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-02T08:45:00+07:00</Sign_x0020_In>
        <Late_x0020_Sign_x0020_In>45</Late_x0020_Sign_x0020_In>
        <Ignore_x0020_Late></Ignore_x0020_Late>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </absen>
    <absen>
        <Employee_x0020_Code>130004</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Ignore</Full_x0020_Name>
        <Department_x0020_Code>800</Department_x0020_Code>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Level>1</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Late_x0020_Sign_x0020_In>99</Late_x0020_Sign_x0020_In>
        <Ignore_x0020_Late>Ignore</Ignore_x0020_Late>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </absen>
    <absen>
        <Employee_x0020_Code>130005</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Management</Full_x0020_Name>
        <Department_x0020_Code>999</Department_x0020_Code>
        <Department_x0020_Name>Management</Department_x0020_Name>
        <Job_x0020_Title>Manager</Job_x0020_Title>
        <Level>5</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Late_x0020_Sign_x0020_In>99</Late_x0020_Sign_x0020_In>
        <Ignore_x0020_Late></Ignore_x0020_Late>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </absen>
    <absen>
        <Employee_x0020_Code>130006</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan ODP</Full_x0020_Name>
        <Department_x0020_Code>0101</Department_x0020_Code>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Level>1</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Late_x0020_Sign_x0020_In>99</Late_x0020_Sign_x0020_In>
        <Ignore_x0020_Late></Ignore_x0020_Late>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </absen>
</NewDataSet>
XML;
    }
}
