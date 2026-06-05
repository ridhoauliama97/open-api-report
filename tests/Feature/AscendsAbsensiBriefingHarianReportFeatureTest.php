<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\AbsensiBriefingHarianReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsAbsensiBriefingHarianReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(AbsensiBriefingHarianReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['group'] ?? null) === 'VKD'
                    && ($filters['report_date'] ?? null) === '2026-06-04'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.absensi_briefing_harian.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Absensi Briefing Harian (RU) - VKD'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(AbsensiBriefingHarianReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/absensi-briefing-harian/pdf', [
            'company' => 'RU',
            'group' => 'VKD',
            'report_date' => '2026-06-04',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Absensi Briefing Harian (RU) VKD');
    }

    public function test_shared_attendance_full_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(AbsensiBriefingHarianReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body', Mockery::type('array'))
            ->andReturn($this->reportData('UC'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.absensi_briefing_harian.pdf', Mockery::type('array'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(AbsensiBriefingHarianReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/shared/hrm/attendance-full/absensi-briefing-harian/pdf',
                ['company' => 'UC', 'group' => 'VKD', 'report_date' => '2026-06-04'],
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

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Absensi Briefing Harian (UC) VKD');
    }

    public function test_shared_attendance_full_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(AbsensiBriefingHarianReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(AbsensiBriefingHarianReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/attendance-full/absensi-briefing-harian/pdf', [
            'company' => 'RU',
            'group' => 'VKD',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_filters_by_date_and_group(): void
    {
        $reportData = app(AbsensiBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'group' => 'VKD',
                'report_date' => '2026-06-04',
            ]);

        $this->assertSame('VKD', $reportData['group']);
        $this->assertSame('04-Jun-26', $reportData['report_date']);
        $this->assertSame(2, $reportData['total_rows']);
        $this->assertSame('Suriono', $reportData['rows'][0]['Nama']);
        $this->assertSame('07:37', $reportData['rows'][0]['Jam Masuk']);
        $this->assertSame('Aferlius Gulo', $reportData['rows'][1]['Nama']);
        $this->assertSame('', $reportData['rows'][1]['Alfa']);
        $this->assertSame('1', $reportData['rows'][1]['is_not_present']);
        $this->assertSame(1, $reportData['summary']['present_no_late']['count']);
        $this->assertSame(1, $reportData['summary']['not_present']['count']);
        $this->assertStringNotContainsString('Other Department User', json_encode($reportData['rows']));
        $this->assertStringNotContainsString('Old Date User', json_encode($reportData['rows']));
    }

    public function test_parser_can_filter_by_date_range_and_exclude_vkd_operator_forklift(): void
    {
        $reportData = app(AbsensiBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'group' => 'VKD',
                'start_date' => '2026-06-03',
                'end_date' => '2026-06-04',
            ]);

        $this->assertSame('VKD', $reportData['group']);
        $this->assertSame('04-Jun-26', $reportData['report_date']);
        $this->assertSame('03-Jun-26', $reportData['start_date']);
        $this->assertSame('03-Jun-26 s/d 04-Jun-26', $reportData['period_text']);
        $this->assertSame(3, $reportData['total_rows']);
        $this->assertSame(2, $reportData['summary']['present_no_late']['count']);
        $this->assertSame(0, $reportData['summary']['late']['count']);
        $this->assertSame(1, $reportData['summary']['not_present']['count']);
        $this->assertSame('Old Date User', $reportData['rows'][0]['Nama']);
        $this->assertSame('Suriono', $reportData['rows'][1]['Nama']);
        $this->assertSame('Aferlius Gulo', $reportData['rows'][2]['Nama']);
        $this->assertStringNotContainsString('Operator Forklift User', json_encode($reportData['rows']));
    }

    public function test_pdf_blade_renders_expected_layout(): void
    {
        $reportData = app(AbsensiBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'group' => 'VKD',
                'report_date' => '2026-06-04',
                'penanggung_jawab' => 'SRO,',
                'tema' => 'Jam Tamu',
            ]);
        $reportData['title'] = 'Laporan Absensi Briefing Harian (RU) - VKD';

        $html = view('ascends.shared.hrm.attendance_full.absensi_briefing_harian.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Absensi Briefing Harian (RU) - VKD', $html);
        $this->assertStringContainsString('Divisi', $html);
        $this->assertStringContainsString('Penanggung Jawab', $html);
        $this->assertStringContainsString('Tanggal', $html);
        $this->assertStringContainsString('Tema', $html);
        $this->assertStringContainsString('Jam Masuk', $html);
        $this->assertStringContainsString('Telat / Tidak Briefing', $html);
        $this->assertStringContainsString('Sakit', $html);
        $this->assertStringContainsString('Izin', $html);
        $this->assertStringContainsString('Alfa', $html);
        $this->assertStringContainsString('check-box', $html);
        $this->assertStringContainsString('Akumulasi Hadir Tidak Telat', $html);
        $this->assertStringContainsString('Check Jumlah', $html);
        $this->assertStringContainsString('ABH', $html);
        $this->assertStringContainsString('Foto', $html);
        $this->assertStringContainsString('Selisih', $html);
        $this->assertStringContainsString('Suriono', $html);
        $this->assertStringContainsString('Kesimpulan Briefing', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company = 'RU'): array
    {
        return [
            'printed_at' => '04 June 2026 13:26',
            'printed_by' => 'Ridho',
            'company' => $company,
            'group' => 'VKD',
            'title' => "Laporan Absensi Briefing Harian ({$company}) - VKD",
            'headers' => ['No', 'Nama', 'Jam Masuk', 'Briefing', 'Telat', 'Sakit', 'Izin', 'Alfa'],
            'rows' => [],
            'total_rows' => 0,
        ];
    }

    private function attendanceXml(string $recordTag = 'Attendance'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>130218</Employee_x0020_Code>
        <Full_x0020_Name>Suriono</Full_x0020_Name>
        <Department_x0020_Name>Vacuum &amp; K/D</Department_x0020_Name>
        <Job_x0020_Title>Ka. Div. Vacuum &amp; KD</Job_x0020_Title>
        <Division_x0020_Name>K/D</Division_x0020_Name>
        <Workgroup>Staff Office I (08.00)</Workgroup>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:37</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <StatusEmp>Active</StatusEmp>
        <Created_x0020_By>Ridho</Created_x0020_By>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131808</Employee_x0020_Code>
        <Full_x0020_Name>Aferlius Gulo</Full_x0020_Name>
        <Department_x0020_Name>Vacuum &amp; K/D</Department_x0020_Name>
        <Job_x0020_Title>Kru Vacuum</Job_x0020_Title>
        <Division_x0020_Name>Vacuum</Division_x0020_Name>
        <Workgroup>Vacuum Group A</Workgroup>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <StatusEmp>Active</StatusEmp>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Other Department User</Full_x0020_Name>
        <Department_x0020_Name>Produksi FJLB</Department_x0020_Name>
        <Job_x0020_Title>Kru Produksi</Job_x0020_Title>
        <Division_x0020_Name>PHU</Division_x0020_Name>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:26</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130002</Employee_x0020_Code>
        <Full_x0020_Name>Old Date User</Full_x0020_Name>
        <Department_x0020_Name>Vacuum &amp; K/D</Department_x0020_Name>
        <Job_x0020_Title>Kru Vacuum</Job_x0020_Title>
        <Division_x0020_Name>K/D</Division_x0020_Name>
        <Date>2026-06-03T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130003</Employee_x0020_Code>
        <Full_x0020_Name>Operator Forklift User</Full_x0020_Name>
        <Department_x0020_Name>Vacuum &amp; K/D</Department_x0020_Name>
        <Job_x0020_Title>Operator Forklift</Job_x0020_Title>
        <Division_x0020_Name>K/D</Division_x0020_Name>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
