<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\AbsensiBriefingHarianGsuReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsAbsensiBriefingHarianGsuReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_gsu_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(AbsensiBriefingHarianGsuReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['Pilih Group'] ?? null) === 'Sales'
                    && ($filters['report_date'] ?? null) === '2026-06-04'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.absensi_briefing_harian_gsu.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'GSU'
                    && str_contains((string) ($data['reportData']['title'] ?? ''), 'Laporan Absensi Briefing Harian')
                    && str_contains((string) ($data['reportData']['title'] ?? ''), 'Sales')
                    && ($data['reportData']['printed_by'] ?? null) === 'Ridho'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(AbsensiBriefingHarianGsuReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/absensi-briefing-harian-gsu/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'Pilih Group' => 'Sales',
            'report_date' => '2026-06-04',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Absensi Briefing Harian (GSU) Sales');
    }

    public function test_shared_attendance_full_gsu_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(AbsensiBriefingHarianGsuReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(AbsensiBriefingHarianGsuReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/attendance-full/absensi-briefing-harian-gsu/pdf', [
            'DB_CompanyName' => 'GSU',
            'Pilih Group' => 'Sales',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_filters_gsu_bahan_baku_group(): void
    {
        $reportData = app(AbsensiBriefingHarianGsuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih Group' => 'Bahan Baku, Washing & Broker',
                'report_date' => '2026-06-04',
            ]);

        $this->assertSame('WNB', $reportData['group']);
        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame('Evi Yunita', $reportData['rows'][0]['Nama']);
        $this->assertStringNotContainsString('Indah Karina', json_encode($reportData['rows']));
    }

    public function test_parser_adds_gsu_sales_virtual_rows_from_crystal_formula(): void
    {
        $reportData = app(AbsensiBriefingHarianGsuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih Group' => 'Sales',
                'report_date' => '2026-06-04',
            ]);

        $names = array_column($reportData['rows'], 'Nama');

        $this->assertSame('Sales', $reportData['group']);
        $this->assertContains('Indah Karina', $names);
        $this->assertContains('Erikson Roni Rumapea', $names);
        $this->assertContains('Laksana Febri Wijaya Laia', $names);
        $this->assertContains('Bambang Paldawan', $names);
        $this->assertContains('Tita Andriani', $names);
        $this->assertContains('Viqih Rizky Lubis', $names);
        $this->assertContains('Nurusysyafillah', $names);
        $this->assertContains('Marina Mentari Br Kaban', $names);
        $this->assertSame(8, $reportData['total_rows']);
        $this->assertSame(1, $reportData['summary']['late']['count']);
        $this->assertSame(7, $reportData['summary']['not_present']['count']);
        $this->assertLessThan(
            array_search('Erikson Roni Rumapea', $names, true),
            array_search('Laksana Febri Wijaya Laia', $names, true)
        );
    }

    public function test_parser_filters_gsu_ekstrusi_groups_before_broker_fallback(): void
    {
        $reportData = app(AbsensiBriefingHarianGsuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih Group' => 'Prod Ekstrusi Pagi',
                'report_date' => '2026-06-04',
            ]);

        $this->assertSame('Prod Ekstrusi Pagi', $reportData['group']);
        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame('Operator Broker Pagi', $reportData['rows'][0]['Nama']);
    }

    public function test_parser_uses_gsu_ekstrusi_responsible_person_and_malam_order(): void
    {
        $reportData = app(AbsensiBriefingHarianGsuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih Group' => 'Prod Ekstrusi Malam',
                'report_date' => '2026-06-04',
            ]);

        $this->assertSame('Ilham', $reportData['responsible_person']);
        $this->assertSame('Ilham', $reportData['rows'][0]['Nama']);
        $this->assertSame('1', $reportData['rows'][0]['is_not_present']);
    }

    public function test_parser_filters_gsu_pin_hulu_hilir_group_like_crystal_report(): void
    {
        $reportData = app(AbsensiBriefingHarianGsuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih Group' => 'PIN HULU & HILIR',
                'report_date' => '2026-06-04',
            ]);

        $names = array_column($reportData['rows'], 'Nama');

        $this->assertSame('PIN HULU & HILIR', $reportData['group']);
        $this->assertContains('Kru PIN Hilir Packing', $names);
        $this->assertNotContains('Kru PIN Hilir Regu', $names);
    }

    public function test_gsu_pdf_blade_renders_expected_layout(): void
    {
        $reportData = app(AbsensiBriefingHarianGsuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih Group' => 'Sales',
                'report_date' => '2026-06-04',
            ]);
        $reportData['title'] = 'Laporan Absensi Briefing Harian (GSU) - Sales';

        $html = view('ascends.shared.hrm.attendance_full.absensi_briefing_harian_gsu.pdf', [
            'company' => 'GSU',
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('GSU', $html);
        $this->assertStringContainsString('Laporan Absensi Briefing Harian - Sales', $html);
        $this->assertStringContainsString('Divisi', $html);
        $this->assertStringContainsString('Penanggung Jawab', $html);
        $this->assertStringContainsString('Telat / Tidak Briefing', $html);
        $this->assertStringContainsString('Indah Karina', $html);
        $this->assertStringContainsString('row-late', $html);
        $this->assertStringContainsString('Check Jumlah', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'printed_at' => '04 June 2026 13:26',
            'printed_by' => 'Ridho',
            'company' => 'GSU',
            'group' => 'Sales',
            'title' => 'Laporan Absensi Briefing Harian (GSU) - Sales',
            'headers' => ['No', 'Nama', 'Jam Masuk', 'Briefing', 'Telat', 'Sakit', 'Izin', 'Alfa'],
            'rows' => [],
            'total_rows' => 0,
        ];
    }

    private function attendanceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Evi Yunita</Full_x0020_Name>
        <Department_x0020_Name>Penerimaan Bahan Baku</Department_x0020_Name>
        <Job_x0020_Title>Kru Penerimaan BB</Job_x0020_Title>
        <Division_x0020_Name>Bahan Baku</Division_x0020_Name>
        <Workgroup>Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Normal Karyawan</Scheduled_x0020_Shift>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120002</Employee_x0020_Code>
        <Full_x0020_Name>Indah Karina</Full_x0020_Name>
        <Department_x0020_Name>Sales</Department_x0020_Name>
        <Job_x0020_Title>Staff Sales</Job_x0020_Title>
        <Division_x0020_Name>Sales</Division_x0020_Name>
        <Scheduled_x0020_Shift>Normal Staff</Scheduled_x0020_Shift>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>08:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120509</Employee_x0020_Code>
        <Full_x0020_Name>Excluded User</Full_x0020_Name>
        <Department_x0020_Name>Sales</Department_x0020_Name>
        <Job_x0020_Title>Staff Sales</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120003</Employee_x0020_Code>
        <Full_x0020_Name>Operator Broker Pagi</Full_x0020_Name>
        <Department_x0020_Name>Washing &amp; Broker</Department_x0020_Name>
        <Job_x0020_Title>Operator Broker C</Job_x0020_Title>
        <Workgroup>Kary. Prod Ekstrusi Besar III</Workgroup>
        <Scheduled_x0020_Shift>Prod Shift I</Scheduled_x0020_Shift>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>06:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120004</Employee_x0020_Code>
        <Full_x0020_Name>Ridwan</Full_x0020_Name>
        <Department_x0020_Name>Washing &amp; Broker</Department_x0020_Name>
        <Job_x0020_Title>Operator Broker A</Job_x0020_Title>
        <Workgroup>Kary. Prod Ekstrusi Besar I</Workgroup>
        <Scheduled_x0020_Shift>Prod SHIFT III</Scheduled_x0020_Shift>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>22:36</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120005</Employee_x0020_Code>
        <Full_x0020_Name>Ilham</Full_x0020_Name>
        <Department_x0020_Name>Washing &amp; Broker</Department_x0020_Name>
        <Job_x0020_Title>Ka. Regu Cuci &amp; Broker A</Job_x0020_Title>
        <Workgroup>Kary. Prod Ekstrusi Besar I</Workgroup>
        <Scheduled_x0020_Shift>Prod SHIFT III</Scheduled_x0020_Shift>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120006</Employee_x0020_Code>
        <Full_x0020_Name>Kru PIN Hilir Regu</Full_x0020_Name>
        <Department_x0020_Name>Produksi Inject</Department_x0020_Name>
        <Job_x0020_Title>Kru Pasang Kunci Longdoor</Job_x0020_Title>
        <Workgroup>Kary. Produksi Regu I</Workgroup>
        <Scheduled_x0020_Shift>Prod Shift I</Scheduled_x0020_Shift>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>06:35</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120007</Employee_x0020_Code>
        <Full_x0020_Name>Kru PIN Hilir Packing</Full_x0020_Name>
        <Department_x0020_Name>Produksi Inject</Department_x0020_Name>
        <Job_x0020_Title>Kru Packing Lemari</Job_x0020_Title>
        <Workgroup>Kary. Normal Shift KL</Workgroup>
        <Scheduled_x0020_Shift>Kary.(08.00-16.15)</Scheduled_x0020_Shift>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
    </Attendance>
</NewDataSet>
XML;
    }
}
