<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\RekapitulasiAbsensiBriefingHarianReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsRekapitulasiAbsensiBriefingHarianReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_rekap_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(RekapitulasiAbsensiBriefingHarianReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['start_date'] ?? null) === '2026-06-01'
                    && ($filters['end_date'] ?? null) === '2026-06-05'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.rekapitulasi_absensi_briefing_harian.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                    && str_contains((string) ($data['reportData']['title'] ?? ''), 'Laporan Rekapitulasi Absensi Briefing Harian')
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapitulasiAbsensiBriefingHarianReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-absensi-briefing-harian/pdf', [
            'company' => 'RU',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Rekapitulasi Absensi Briefing Harian');
    }

    public function test_shared_attendance_full_rekap_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(RekapitulasiAbsensiBriefingHarianReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body', Mockery::type('array'))
            ->andReturn($this->reportData('GSU'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.rekapitulasi_absensi_briefing_harian.pdf', Mockery::type('array'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapitulasiAbsensiBriefingHarianReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-absensi-briefing-harian/pdf',
                ['company' => 'GSU', 'start_date' => '2026-06-01', 'end_date' => '2026-06-05'],
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

    public function test_shared_attendance_full_rekap_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(RekapitulasiAbsensiBriefingHarianReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(RekapitulasiAbsensiBriefingHarianReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-absensi-briefing-harian/pdf', [
            'company' => 'RU',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_builds_rekap_by_date_and_division(): void
    {
        $reportData = app(RekapitulasiAbsensiBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-02',
            ]);

        $this->assertSame('01-Jun-26 s/d 02-Jun-26', $reportData['period']['label']);
        $this->assertSame(2, $reportData['total_rows']);

        $this->assertSame('SML', $reportData['rows'][0]['Divisi']);
        $this->assertSame(1, $reportData['rows'][0]['Jumlah Hadir Tidak Telat']);
        $this->assertSame(1, $reportData['rows'][0]['Jumlah Telat']);
        $this->assertSame(1, $reportData['rows'][0]['Jumlah Tidak Hadir']);

        $this->assertSame('VKD', $reportData['rows'][1]['Divisi']);
        $this->assertSame(1, $reportData['rows'][1]['Jumlah Hadir Tidak Telat']);
        $this->assertSame(0, $reportData['rows'][1]['Jumlah Telat']);

        $this->assertSame(2, $reportData['grand_summary']['Jumlah Hadir Tidak Telat']);
        $this->assertSame(1, $reportData['grand_summary']['Jumlah Telat']);
        $this->assertSame(1, $reportData['grand_summary']['Jumlah Tidak Hadir']);
    }

    public function test_parser_can_filter_rekap_by_group(): void
    {
        $reportData = app(RekapitulasiAbsensiBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'group' => 'VKD',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-02',
            ]);

        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame('VKD', $reportData['rows'][0]['Divisi']);
    }

    public function test_pdf_blade_renders_expected_layout(): void
    {
        $reportData = app(RekapitulasiAbsensiBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-02',
            ]);
        $reportData['title'] = 'Laporan Rekapitulasi Absensi Briefing Harian';

        $html = view('ascends.shared.hrm.attendance_full.rekapitulasi_absensi_briefing_harian.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'headers' => $reportData['headers'],
            'rows' => $reportData['rows'],
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Rekapitulasi Absensi Briefing Harian', $html);
        $this->assertStringContainsString('01-Jun-26 s/d 02-Jun-26', $html);
        $this->assertStringContainsString('Jumlah Hadir', $html);
        $this->assertStringContainsString('Jumlah Saat', $html);
        $this->assertStringContainsString('SML', $html);
        $this->assertStringContainsString('VKD', $html);
        $this->assertStringContainsString('Total', $html);
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
            'title' => "Laporan Rekapitulasi Absensi Briefing Harian ({$company})",
            'period' => ['label' => '01-Jun-26 s/d 05-Jun-26'],
            'headers' => ['Divisi', 'Jumlah Hadir Tidak Telat', 'Jumlah Telat', 'Jumlah Tidak Hadir', 'Jumlah Saat Pukul 12.55 Wib', 'Selisih', 'Keterangan'],
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
        <Employee_x0020_Code>100001</Employee_x0020_Code>
        <Full_x0020_Name>Hadir Normal</Full_x0020_Name>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Division_x0020_Name>Band Saw</Division_x0020_Name>
        <Date>2026-06-01T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:00</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In_x0020_Diff.>0</Sign_x0020_In_x0020_Diff.>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Created_x0020_By>Ridho</Created_x0020_By>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>100002</Employee_x0020_Code>
        <Full_x0020_Name>Hadir Telat</Full_x0020_Name>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Division_x0020_Name>Band Saw</Division_x0020_Name>
        <Date>2026-06-01T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:45</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In_x0020_Diff.>15</Sign_x0020_In_x0020_Diff.>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>100003</Employee_x0020_Code>
        <Full_x0020_Name>Tidak Hadir</Full_x0020_Name>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Division_x0020_Name>Band Saw</Division_x0020_Name>
        <Date>2026-06-01T00:00:00+07:00</Date>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>100004</Employee_x0020_Code>
        <Full_x0020_Name>VKD User</Full_x0020_Name>
        <Department_x0020_Name>Vacuum &amp; K/D</Department_x0020_Name>
        <Division_x0020_Name>VKD</Division_x0020_Name>
        <Date>2026-06-02T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:10</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In_x0020_Diff.>0</Sign_x0020_In_x0020_Diff.>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL001</Employee_x0020_Code>
        <Full_x0020_Name>Special User</Full_x0020_Name>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Division_x0020_Name>Band Saw</Division_x0020_Name>
        <Date>2026-06-01T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:10</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
