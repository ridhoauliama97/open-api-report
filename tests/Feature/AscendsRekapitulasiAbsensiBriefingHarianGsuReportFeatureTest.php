<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\RekapitulasiAbsensiBriefingHarianGsuReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsRekapitulasiAbsensiBriefingHarianGsuReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_rekap_gsu_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(RekapitulasiAbsensiBriefingHarianGsuReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance-gsu.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['start_date'] ?? null) === '2026-05-01'
                    && ($filters['end_date'] ?? null) === '2026-05-31'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.rekapitulasi_absensi_briefing_harian_gsu.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'GSU'
                    && str_contains((string) ($data['reportData']['title'] ?? ''), 'Laporan Rekapitulasi Absensi Briefing Harian')
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapitulasiAbsensiBriefingHarianGsuReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-absensi-briefing-harian-gsu/pdf', [
            'DB_CompanyName' => 'GSU',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance-gsu.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Rekapitulasi Absensi Briefing Harian (GSU)');
    }

    public function test_shared_attendance_full_rekap_gsu_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(RekapitulasiAbsensiBriefingHarianGsuReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body', Mockery::type('array'))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.rekapitulasi_absensi_briefing_harian_gsu.pdf', Mockery::type('array'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapitulasiAbsensiBriefingHarianGsuReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-absensi-briefing-harian-gsu/pdf',
                ['DB_CompanyName' => 'GSU', 'start_date' => '2026-05-01', 'end_date' => '2026-05-31'],
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

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Rekapitulasi Absensi Briefing Harian (GSU)');
    }

    public function test_shared_attendance_full_rekap_gsu_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(RekapitulasiAbsensiBriefingHarianGsuReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(RekapitulasiAbsensiBriefingHarianGsuReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-absensi-briefing-harian-gsu/pdf', [
            'DB_CompanyName' => 'GSU',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_builds_gsu_rekap_with_initial_division_and_missing_start_date(): void
    {
        $reportData = app(RekapitulasiAbsensiBriefingHarianGsuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-04',
            ]);

        $this->assertSame('Dari 01-Mei-26 s/d 04-Mei-26', $reportData['period']['label']);
        $this->assertSame('WNB', $reportData['rows'][0]['Divisi']);
        $this->assertSame(1, $reportData['rows'][0]['Jumlah Hadir Tidak Telat']);
        $this->assertSame(1, $reportData['rows'][0]['Jumlah Telat']);
        $this->assertSame(2, $reportData['rows'][0]['Jumlah Tidak Hadir']);
        $this->assertSame(1, $reportData['grand_summary']['Jumlah Hadir Tidak Telat']);
        $this->assertSame(1, $reportData['grand_summary']['Jumlah Telat']);
        $this->assertSame(2, $reportData['grand_summary']['Jumlah Tidak Hadir']);
    }

    public function test_pdf_blade_renders_gsu_expected_layout(): void
    {
        $reportData = app(RekapitulasiAbsensiBriefingHarianGsuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-04',
            ]);
        $reportData['title'] = 'Laporan Rekapitulasi Absensi Briefing Harian';

        $html = view('ascends.shared.hrm.attendance_full.rekapitulasi_absensi_briefing_harian_gsu.pdf', [
            'company' => 'GSU',
            'reportData' => $reportData,
            'headers' => $reportData['headers'],
            'rows' => $reportData['rows'],
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Rekapitulasi Absensi Briefing Harian', $html);
        $this->assertStringContainsString('Dari 01-Mei-26 s/d 04-Mei-26', $html);
        $this->assertStringContainsString('Pukul 15.00', $html);
        $this->assertStringContainsString('WNB', $html);
        $this->assertStringContainsString('Total', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'printed_at' => '31 May 2026 13:26',
            'printed_by' => 'Ridho',
            'company' => 'GSU',
            'title' => 'Laporan Rekapitulasi Absensi Briefing Harian (GSU)',
            'period' => ['label' => 'Dari 01-Mei-26 s/d 31-Mei-26'],
            'headers' => ['No', 'Divisi', 'Jumlah Hadir Tidak Telat', 'Jumlah Telat', 'Jumlah Tidak Hadir', 'Jumlah Saat Pukul 15.00 Wib', 'Selisih', 'Keterangan'],
            'rows' => [],
            'grand_summary' => [],
            'total_rows' => 0,
        ];
    }

    private function attendanceXml(string $recordTag = 'Attendance'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>120942</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Job_x0020_Title>Ka. Div. Washing &amp; Broker</Job_x0020_Title>
        <Department_x0020_Name>Washing &amp; Broker</Department_x0020_Name>
        <Workgroup>Staff Office II (08.00-17.00)</Workgroup>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In_x0020_Diff.>0.1800</Sign_x0020_In_x0020_Diff.>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Created_x0020_By>Ridho</Created_x0020_By>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>120942</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Job_x0020_Title>Ka. Div. Washing &amp; Broker</Job_x0020_Title>
        <Department_x0020_Name>Washing &amp; Broker</Department_x0020_Name>
        <Workgroup>Staff Office II (08.00-17.00)</Workgroup>
        <Date>2026-05-03T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>08:10</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In_x0020_Diff.>-0.1000</Sign_x0020_In_x0020_Diff.>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>120942</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Job_x0020_Title>Ka. Div. Washing &amp; Broker</Job_x0020_Title>
        <Department_x0020_Name>Washing &amp; Broker</Department_x0020_Name>
        <Workgroup>Staff Office II (08.00-17.00)</Workgroup>
        <Date>2026-05-04T00:00:00+07:00</Date>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
