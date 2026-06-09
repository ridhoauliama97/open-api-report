<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\PersentaseKehadiranBulananReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsPersentaseKehadiranBulananReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(PersentaseKehadiranBulananReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['company'] ?? null) === 'RU'
                    && ($filters['Pilih Type'] ?? null) === 'Staff'
            ))
            ->andReturn($this->reportData('RU', 'Staff'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.persentase_kehadiran_bulanan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Persentase Kehadiran Bulanan Staff (RU)'
                    && ($data['reportData']['printed_by'] ?? null) === 'Windi'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PersentaseKehadiranBulananReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/persentase-kehadiran-bulanan/pdf', [
            'DB_CompanyName' => 'RU',
            'Sys_UserName' => 'Windi',
            'Pilih Type' => 'Staff',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Persentase Kehadiran Bulanan Staff (RU)');
    }

    public function test_parser_filters_kk_kt_br_and_excludes_odp_management(): void
    {
        $reportData = app(PersentaseKehadiranBulananReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'company' => 'RU',
                'Pilih Type' => 'KK/KT',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-02',
            ]);

        $this->assertSame('KK/KT', $reportData['type']);
        $this->assertSame(2, $reportData['total_rows']);
        $this->assertSame('Produksi', $reportData['grouped_rows'][0]['department']);
        $this->assertSame('Karyawan BR', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertSame('Karyawan KK', $reportData['grouped_rows'][0]['rows'][1]['Nama']);
        $this->assertSame(['Nama', 'Jabatan', 'Masa Kerja', 'Mei-26', 'Total < 93%'], $reportData['headers']);
    }

    public function test_parser_filters_staff(): void
    {
        $reportData = app(PersentaseKehadiranBulananReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'company' => 'RU',
                'Pilih Type' => 'Staff',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-02',
            ]);

        $this->assertSame('Staff', $reportData['type']);
        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame('Karyawan Staff', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
    }

    public function test_parser_accepts_normalized_pilih_type_key(): void
    {
        $reportData = app(PersentaseKehadiranBulananReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'company' => 'RU',
                'Pilih_x0020_Type' => 'Staff',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-02',
            ]);

        $this->assertSame('Staff', $reportData['type']);
        $this->assertSame(1, $reportData['total_rows']);
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
            'title' => "Laporan Persentase Kehadiran Bulanan {$type} ({$company})",
            'headers' => ['No', 'Nama', 'L/P', 'Jabatan', 'Status', 'Level', '%'],
            'rows' => [],
            'grouped_rows' => [],
            'grand_summary' => ['subtotal' => 0],
            'total_rows' => 0,
        ];
    }

    private function attendanceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan KK</Full_x0020_Name>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Sex>Male</Sex>
        <Level>1</Level>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Sign_x0020_In>2026-05-01T07:30:00+07:00</Sign_x0020_In>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130002</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan BR</Full_x0020_Name>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Job_x0020_Title>Operator Borongan</Job_x0020_Title>
        <Sex>Male</Sex>
        <Level>1</Level>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Sign_x0020_In>2026-05-01T07:30:00+07:00</Sign_x0020_In>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan KK</Full_x0020_Name>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Sex>Male</Sex>
        <Level>1</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130002</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan BR</Full_x0020_Name>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Job_x0020_Title>Operator Borongan</Job_x0020_Title>
        <Sex>Male</Sex>
        <Level>1</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130003</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Staff</Full_x0020_Name>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Job_x0020_Title>Staff</Job_x0020_Title>
        <Sex>Female</Sex>
        <Level>2</Level>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Sign_x0020_In>2026-05-01T07:30:00+07:00</Sign_x0020_In>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130003</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Staff</Full_x0020_Name>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Job_x0020_Title>Staff</Job_x0020_Title>
        <Sex>Female</Sex>
        <Level>2</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130004</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan ODP</Full_x0020_Name>
        <Department_x0020_Name>ODP Produksi</Department_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Sex>Male</Sex>
        <Level>1</Level>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Sign_x0020_In>2026-05-01T07:30:00+07:00</Sign_x0020_In>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130005</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Management</Full_x0020_Name>
        <Department_x0020_Name>Management</Department_x0020_Name>
        <Job_x0020_Title>Manager</Job_x0020_Title>
        <Sex>Male</Sex>
        <Level>6</Level>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Sign_x0020_In>2026-05-01T07:30:00+07:00</Sign_x0020_In>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
</NewDataSet>
XML;
    }
}
