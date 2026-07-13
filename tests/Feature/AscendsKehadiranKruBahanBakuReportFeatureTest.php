<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KehadiranKruBahanBakuReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKehadiranKruBahanBakuReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_kehadiran_kru_bahan_baku_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(KehadiranKruBahanBakuReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn(array $filters): bool => ($filters['start_date'] ?? null) === '2026-05-05'
                && ($filters['end_date'] ?? null) === '2026-05-06'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.kehadiran_kru_bahan_baku.pdf', Mockery::on(
                static fn(array $data): bool => ($data['company'] ?? null) === 'RU'
                && ($data['reportData']['title'] ?? null) === 'Laporan Kehadiran Kru Bahan Baku (RU)'
                && ($data['pdf_format'] ?? null) === 'A4'
                && ($data['pdf_orientation'] ?? null) === 'landscape'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KehadiranKruBahanBakuReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/kehadiran-kru-bahan-baku/pdf', [
            'DB_CompanyName' => 'RU',
            'start_date' => '2026-05-05',
            'end_date' => '2026-05-06',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition(
            $response,
            'attachment',
            'Attendance Full - Laporan Kehadiran Kru Bahan Baku (RU)'
        );
    }

    public function test_parser_builds_kru_bahan_baku_pivot_rows(): void
    {
        $reportData = app(KehadiranKruBahanBakuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
            ]);

        $this->assertSame('Laporan Kehadiran Kru Bahan Baku', $reportData['title']);
        $this->assertSame('Dari 05-Mei-26 Sampai 06-Mei-26', $reportData['period']['label']);
        $this->assertSame(1, $reportData['total_employees']);
        $this->assertSame(['131954'], array_column(array_column($reportData['rows'], 'employee'), 'employee_code'));
        $this->assertSame('Kru Borongan Penerimaan Bahan Baku', $reportData['rows'][0]['employee']['job_title']);
        $this->assertSame(1, $reportData['date_totals']['2026-05-05']);
        $this->assertSame(1, $reportData['date_totals']['2026-05-06']);
    }

    public function test_pdf_blade_renders_expected_layout(): void
    {
        $reportData = app(KehadiranKruBahanBakuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
            ]);
        $reportData['company'] = 'RU';
        $reportData['title'] = 'Laporan Kehadiran Kru Bahan Baku (RU)';

        $html = view('ascends.shared.hrm.attendance_full.kehadiran_kru_bahan_baku.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'headers' => $reportData['headers'],
            'rows' => $reportData['rows'],
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('<h1 class="report-companyTitle">RU</h1>', $html);
        $this->assertStringContainsString('Laporan Kehadiran Kru Bahan Baku', $html);
        $this->assertStringContainsString('Karyawan', $html);
        $this->assertStringContainsString('Masa Kerja', $html);
        $this->assertStringContainsString('Radot Manik', $html);
        $this->assertStringContainsString('Total Seluruh Karyawan/Kru', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'title' => 'Laporan Kehadiran Kru Bahan Baku',
            'printed_by' => 'Ridho',
            'period' => ['label' => 'Dari 05-Mei-26 Sampai 06-Mei-26'],
            'date_columns' => [
                ['date' => '2026-05-05', 'label' => '05-Mei-26'],
                ['date' => '2026-05-06', 'label' => '06-Mei-26'],
            ],
            'headers' => ['Karyawan', 'Nama', 'Tanggal Masuk', 'Masa Kerja', 'Jabatan'],
            'rows' => [],
            'date_totals' => [],
            'total_employees' => 0,
        ];
    }

    private function attendanceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>130097</Employee_x0020_Code>
        <Full_x0020_Name>Atoziduhu Hura</Full_x0020_Name>
        <Join_x0020_Date>2016-08-12T00:00:00+07:00</Join_x0020_Date>
        <Job_x0020_Title>Operator Penerimaan Bahan Baku</Job_x0020_Title>
        <Workgroup>Kary. Kontrak Shift Normal</Workgroup>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-05T06:29:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-05T11:36:00+07:00</Sign_x0020_Out>
        <HK>1</HK>
        <Created_x0020_By>Ridho</Created_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>131954</Employee_x0020_Code>
        <Full_x0020_Name>Radot Manik</Full_x0020_Name>
        <Join_x0020_Date>2021-05-17T00:00:00+07:00</Join_x0020_Date>
        <Job_x0020_Title>Kru Borongan Penerimaan Bahan Baku</Job_x0020_Title>
        <Workgroup>Borongan Stick</Workgroup>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-05T06:31:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-05T13:55:00+07:00</Sign_x0020_Out>
        <HK>1</HK>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>131954</Employee_x0020_Code>
        <Full_x0020_Name>Radot Manik</Full_x0020_Name>
        <Join_x0020_Date>2021-05-17T00:00:00+07:00</Join_x0020_Date>
        <Job_x0020_Title>Kru Borongan Penerimaan Bahan Baku</Job_x0020_Title>
        <Workgroup>Borongan Stick</Workgroup>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-05-06T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-06T06:23:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-06T16:19:00+07:00</Sign_x0020_Out>
        <HK>1</HK>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>131993</Employee_x0020_Code>
        <Full_x0020_Name>Satria Wiranata</Full_x0020_Name>
        <Join_x0020_Date>2025-03-17T00:00:00+07:00</Join_x0020_Date>
        <Job_x0020_Title>Kru Penerimaan Bahan Baku</Job_x0020_Title>
        <Workgroup>Kary. Kontrak Shift Normal</Workgroup>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-05T06:35:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-05T16:17:00+07:00</Sign_x0020_Out>
        <HK>1</HK>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130054</Employee_x0020_Code>
        <Full_x0020_Name>Lilis Roma Uli Br Pandiangan</Full_x0020_Name>
        <Join_x0020_Date>2018-12-10T00:00:00+07:00</Join_x0020_Date>
        <Job_x0020_Title>Ka. Div. Penerimaan Bahan Baku</Job_x0020_Title>
        <Workgroup>Staff Office I (08.00)</Workgroup>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-05T08:00:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-05T17:00:00+07:00</Sign_x0020_Out>
        <HK>1</HK>
    </Attendance>
</NewDataSet>
XML;
    }
}
