<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\PersentaseKehadiranMingguanPerDepartemenReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsPersentaseKehadiranMingguanPerDepartemenReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(PersentaseKehadiranMingguanPerDepartemenReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['start_date'] ?? null) === '2026-05-01'
                    && ($filters['end_date'] ?? null) === '2026-05-31'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.persentase_kehadiran_mingguan_per_departemen.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                    && str_contains((string) ($data['reportData']['title'] ?? ''), 'Laporan Persentase Kehadiran Mingguan Per Departemen')
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PersentaseKehadiranMingguanPerDepartemenReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/persentase-kehadiran-mingguan-per-departemen/pdf', [
            'company' => 'RU',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Persentase Kehadiran Mingguan Per Departemen');
    }

    public function test_shared_attendance_full_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(PersentaseKehadiranMingguanPerDepartemenReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body', Mockery::type('array'))
            ->andReturn($this->reportData('UC'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.persentase_kehadiran_mingguan_per_departemen.pdf', Mockery::type('array'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PersentaseKehadiranMingguanPerDepartemenReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/shared/hrm/attendance-full/persentase-kehadiran-mingguan-per-departemen/pdf',
                ['company' => 'UC', 'start_date' => '2026-05-01', 'end_date' => '2026-05-31'],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/xml',
                    'HTTP_ACCEPT' => 'application/pdf',
                ],
                $xml
            )
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Persentase Kehadiran Mingguan Per Departemen (UC)');
    }

    public function test_shared_attendance_full_api_uses_db_company_name_parameter(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(PersentaseKehadiranMingguanPerDepartemenReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['company'] ?? null) === 'GSU'
            ))
            ->andReturn($this->reportData('GSU'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.persentase_kehadiran_mingguan_per_departemen.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'GSU'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Persentase Kehadiran Mingguan Per Departemen (GSU)'
                    && ($data['reportData']['printed_by'] ?? null) === 'Windi'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PersentaseKehadiranMingguanPerDepartemenReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/persentase-kehadiran-mingguan-per-departemen/pdf', [
            'company' => 'RU',
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Windi',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Persentase Kehadiran Mingguan Per Departemen (GSU)');
    }

    public function test_shared_attendance_full_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(PersentaseKehadiranMingguanPerDepartemenReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(PersentaseKehadiranMingguanPerDepartemenReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/attendance-full/persentase-kehadiran-mingguan-per-departemen/pdf', [
            'company' => 'RU',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_groups_department_and_calculates_summary(): void
    {
        $reportData = app(PersentaseKehadiranMingguanPerDepartemenReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-02',
            ]);

        $this->assertSame(['No', 'Nama', 'L/P', 'Jabatan', 'Status', 'Level', '%'], $reportData['headers']);
        $this->assertSame('Dari 01-Mei-26 s/d 02-Mei-26', $reportData['period']['label']);
        $this->assertSame(3, $reportData['total_rows']);
        $this->assertSame('Departemen : Finance & Accounting', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Betty', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertSame('2', $reportData['grouped_rows'][0]['rows'][0]['Level']);
        $this->assertSame('100%', $reportData['grouped_rows'][0]['rows'][0]['%']);
        $this->assertSame('Aulia', $reportData['grouped_rows'][0]['rows'][1]['Nama']);
        $this->assertSame('1', $reportData['grouped_rows'][0]['rows'][1]['Level']);
        $this->assertSame('50%', $reportData['grouped_rows'][0]['rows'][1]['%']);
        $this->assertSame(2, $reportData['grouped_rows'][0]['summary']['subtotal']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['gender']['L']['count']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['status']['KK']['count']);
        $this->assertSame(50, $reportData['grouped_rows'][0]['summary']['attendance_percentage']['min']);
        $this->assertSame(100, $reportData['grouped_rows'][0]['summary']['attendance_percentage']['max']);
        $this->assertSame(75, $reportData['grouped_rows'][0]['summary']['attendance_percentage']['avg']);
        $this->assertSame(3, $reportData['grand_summary']['subtotal']);
    }

    public function test_pdf_blade_renders_expected_layout(): void
    {
        $reportData = app(PersentaseKehadiranMingguanPerDepartemenReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-02',
            ]);
        $reportData['title'] = 'Laporan Persentase Kehadiran Mingguan Per Departemen';

        $html = view('ascends.shared.hrm.attendance_full.persentase_kehadiran_mingguan_per_departemen.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Persentase Kehadiran Mingguan Per Departemen', $html);
        $this->assertStringContainsString('Dari 01-Mei-26 s/d 02-Mei-26', $html);
        $this->assertStringContainsString('Departemen : Finance &amp; Accounting', $html);
        $this->assertStringContainsString('Akumulasi Persentase Kehadiran', $html);
        $this->assertStringContainsString('Grand Total : 3', $html);
    }

    public function test_parser_uses_gsu_attendance_percentage_rules(): void
    {
        $reportData = app(PersentaseKehadiranMingguanPerDepartemenReportService::class)
            ->buildReportDataFromXml($this->gsuAttendanceXml(), 'gsu test xml', [
                'company' => 'GSU',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-04',
            ]);

        $this->assertSame('25%', $reportData['grouped_rows'][0]['rows'][0]['%']);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company = 'RU'): array
    {
        return [
            'printed_at' => '31 May 2026 13:26',
            'printed_by' => 'Ridho',
            'company' => $company,
            'title' => "Laporan Persentase Kehadiran Mingguan Per Departemen ({$company})",
            'headers' => ['No', 'Nama', 'L/P', 'Jabatan', 'Status', 'Level', '%'],
            'rows' => [],
            'grouped_rows' => [],
            'grand_summary' => ['subtotal' => 0],
            'total_rows' => 0,
        ];
    }

    private function attendanceXml(string $recordTag = 'Attendance'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Aulia</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Staff Accounting</Job_x0020_Title>
        <Sex>Male</Sex>
        <Level>1</Level>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Sign_x0020_In>2026-05-01T07:30:00+07:00</Sign_x0020_In>
        <HK>1.0000</HK>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Created_x0020_By>Ridho</Created_x0020_By>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Aulia</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Staff Accounting</Job_x0020_Title>
        <Sex>Male</Sex>
        <Level>1</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130002</Employee_x0020_Code>
        <Full_x0020_Name>Betty</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Kasir</Job_x0020_Title>
        <Sex>Female</Sex>
        <Level>2</Level>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Sign_x0020_In>2026-05-01T07:30:00+07:00</Sign_x0020_In>
        <HK>1.0000</HK>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130003</Employee_x0020_Code>
        <Full_x0020_Name>Candra</Full_x0020_Name>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Sex>Male</Sex>
        <Level>1</Level>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </{$recordTag}>
</NewDataSet>
XML;
    }

    private function gsuAttendanceXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>GSU001</Employee_x0020_Code>
        <Full_x0020_Name>Desi Marziana</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Ka. Div Accounting</Job_x0020_Title>
        <Sex>Female</Sex>
        <Level>4</Level>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Off</Scheduled_x0020_Shift>
        <Shift>Off</Shift>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>GSU001</Employee_x0020_Code>
        <Full_x0020_Name>Desi Marziana</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Ka. Div Accounting</Job_x0020_Title>
        <Sex>Female</Sex>
        <Level>4</Level>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Staff Office</Scheduled_x0020_Shift>
        <Shift>Staff Office</Shift>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Sign_x0020_In>2026-05-02T07:30:00+07:00</Sign_x0020_In>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>GSU001</Employee_x0020_Code>
        <Full_x0020_Name>Desi Marziana</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Ka. Div Accounting</Job_x0020_Title>
        <Sex>Female</Sex>
        <Level>4</Level>
        <Date>2026-05-03T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Off</Scheduled_x0020_Shift>
        <Shift>Off</Shift>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>GSU001</Employee_x0020_Code>
        <Full_x0020_Name>Desi Marziana</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Ka. Div Accounting</Job_x0020_Title>
        <Sex>Female</Sex>
        <Level>4</Level>
        <Date>2026-05-04T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Off</Scheduled_x0020_Shift>
        <Shift>Off</Shift>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
</NewDataSet>
XML;
    }
}
