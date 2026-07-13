<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KehadiranKruRacipReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKehadiranKruRacipReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_kehadiran_kru_racip_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(KehadiranKruRacipReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['start_date'] ?? null) === '2026-05-05'
                && ($filters['end_date'] ?? null) === '2026-05-06'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.kehadiran_kru_racip.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                && ($data['reportData']['title'] ?? null) === 'Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut (RU)'
                && ($data['pdf_format'] ?? null) === 'A4'
                && ($data['pdf_orientation'] ?? null) === 'landscape'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KehadiranKruRacipReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/kehadiran-kru-racip/pdf', [
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
            'Attendance Full - Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut (RU)'
        );
    }

    public function test_shared_attendance_full_kehadiran_kru_racip_api_can_render_raw_xml_body_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(KehadiranKruRacipReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body', Mockery::type('array'))
            ->andReturn($this->reportData('GSU'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.kehadiran_kru_racip.pdf', Mockery::type('array'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KehadiranKruRacipReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/shared/hrm/attendance-full/kehadiran-kru-racip/pdf',
                ['DB_CompanyName' => 'GSU'],
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

        $this->assertPdfDisposition(
            $response,
            'attachment',
            'Attendance Full - Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut (GSU)'
        );
    }

    public function test_shared_attendance_full_kehadiran_kru_racip_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KehadiranKruRacipReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KehadiranKruRacipReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/attendance-full/kehadiran-kru-racip/pdf', [
            'DB_CompanyName' => 'RU',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_builds_kru_racip_pivot_rows(): void
    {
        $reportData = app(KehadiranKruRacipReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
            ]);

        $this->assertSame('Dari 05-Mei-26 Sampai 06-Mei-26', $reportData['period']['label']);
        $this->assertSame(2, $reportData['total_employees']);
        $this->assertCount(2, $reportData['date_columns']);
        $this->assertSame('131356', $reportData['rows'][0]['employee']['employee_code']);
        $this->assertSame('Roma Hutabarat', $reportData['rows'][0]['employee']['name']);
        $this->assertSame('Operator Borongan Sawmill', $reportData['rows'][0]['employee']['job_title']);
        $this->assertStringContainsString('Thn', $reportData['rows'][0]['employee']['year_of_service']);
        $this->assertStringContainsString('Bln', $reportData['rows'][0]['employee']['year_of_service']);
        $this->assertStringContainsString('Hr', $reportData['rows'][0]['employee']['year_of_service']);
        $this->assertSame('06:29', $reportData['rows'][0]['attendance']['2026-05-05']['in']);
        $this->assertSame('11:36', $reportData['rows'][0]['attendance']['2026-05-05']['out']);
        $this->assertSame('1', $reportData['rows'][0]['hk']);
        $this->assertSame(2, $reportData['date_totals']['2026-05-05']);
        $this->assertSame(1, $reportData['date_totals']['2026-05-06']);
    }

    public function test_parser_returns_empty_report_when_xml_has_no_kru_racip_rows(): void
    {
        $reportData = app(KehadiranKruRacipReportService::class)
            ->buildReportDataFromXml($this->attendanceXmlWithoutRacipRows(), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
            ]);

        $this->assertSame('Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut', $reportData['title']);
        $this->assertSame(0, $reportData['total_employees']);
        $this->assertSame([], $reportData['rows']);
        $this->assertCount(2, $reportData['date_columns']);
    }

    public function test_pdf_blade_renders_expected_layout(): void
    {
        $reportData = app(KehadiranKruRacipReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
            ]);
        $reportData['company'] = 'RU';
        $reportData['title'] = 'Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut (RU)';

        $html = view('ascends.shared.hrm.attendance_full.kehadiran_kru_racip.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'headers' => $reportData['headers'],
            'rows' => $reportData['rows'],
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('<h1 class="report-title">RU</h1>', $html);
        $this->assertStringContainsString('Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut', $html);
        $this->assertStringContainsString('Karyawan', $html);
        $this->assertStringContainsString('Masa Kerja', $html);
        $this->assertStringContainsString('05-Mei-26', $html);
        $this->assertStringContainsString('Roma Hutabarat', $html);
        $this->assertStringContainsString('Total Seluruh Karyawan/Kru', $html);
    }

    public function test_pdf_blade_splits_date_columns_into_readable_sections(): void
    {
        $reportData = $this->reportData();
        $reportData['date_columns'] = [];
        $reportData['date_totals'] = [];
        for ($day = 5; $day <= 12; $day++) {
            $date = "2026-05-{$day}";
            $label = str_pad((string) $day, 2, '0', STR_PAD_LEFT).'-Mei-26';
            $reportData['date_columns'][] = ['date' => $date, 'label' => $label];
            $reportData['date_totals'][$date] = 1;
        }
        $reportData['rows'] = [
            [
                'employee' => [
                    'employee_code' => '131356',
                    'name' => 'Roma Hutabarat',
                    'join_date' => '01-Jun-21',
                    'year_of_service' => '5 Thn 0 Bln 3 Hr',
                    'job_title' => 'Operator Borongan Sawmill',
                ],
                'attendance' => [],
                'hk' => '8',
            ],
        ];
        $reportData['total_employees'] = 1;

        $html = view('ascends.shared.hrm.attendance_full.kehadiran_kru_racip.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'headers' => $reportData['headers'],
            'rows' => $reportData['rows'],
            'generatedAt' => now(),
        ])->render();

        $this->assertSame(2, substr_count($html, '<table class="data-table">'));
        $this->assertStringContainsString('12-Mei-26', $html);
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
            'title' => "Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut ({$company})",
            'period' => ['label' => 'Dari 05-Mei-26 Sampai 06-Mei-26'],
            'headers' => ['Karyawan', 'Nama', 'Tanggal Masuk', 'Masa Kerja', 'Jabatan'],
            'date_columns' => [
                ['date' => '2026-05-05', 'label' => '05-Mei-26'],
                ['date' => '2026-05-06', 'label' => '06-Mei-26'],
            ],
            'rows' => [],
            'date_totals' => [],
            'total_employees' => 0,
        ];
    }

    private function attendanceXml(string $recordTag = 'Attendance'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>131356</Employee_x0020_Code>
        <Full_x0020_Name>Roma Hutabarat</Full_x0020_Name>
        <Join_x0020_Date>2021-06-01T00:00:00+07:00</Join_x0020_Date>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Workgroup>Borongan Sawmill</Workgroup>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-05T06:29:00+07:00</Sign_x0020_In>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>06:29</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_Out>2026-05-05T11:36:00+07:00</Sign_x0020_Out>
        <Sign_x0020_Out_x0020__x0028_Time_x0029_>11:36</Sign_x0020_Out_x0020__x0028_Time_x0029_>
        <HK>1</HK>
        <Created_x0020_By>Ridho</Created_x0020_By>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131623</Employee_x0020_Code>
        <Full_x0020_Name>Saridayanti Br Purba</Full_x0020_Name>
        <Join_x0020_Date>2022-04-14T00:00:00+07:00</Join_x0020_Date>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Workgroup>Borongan Sawmill</Workgroup>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-05T06:23:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-05T16:17:00+07:00</Sign_x0020_Out>
        <HK>1</HK>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131623</Employee_x0020_Code>
        <Full_x0020_Name>Saridayanti Br Purba</Full_x0020_Name>
        <Join_x0020_Date>2022-04-14T00:00:00+07:00</Join_x0020_Date>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Workgroup>Borongan Sawmill</Workgroup>
        <Date>2026-05-06T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-06T06:33:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-06T16:44:00+07:00</Sign_x0020_Out>
        <HK>1</HK>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>999999</Employee_x0020_Code>
        <Full_x0020_Name>Bukan Kru Racip</Full_x0020_Name>
        <Job_x0020_Title>Kru Stick Borongan</Job_x0020_Title>
        <Workgroup>Borongan Stick</Workgroup>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-05T08:00:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-05T17:00:00+07:00</Sign_x0020_Out>
        <HK>1</HK>
    </{$recordTag}>
</NewDataSet>
XML;
    }

    private function attendanceXmlWithoutRacipRows(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>999999</Employee_x0020_Code>
        <Full_x0020_Name>Bukan Kru Racip</Full_x0020_Name>
        <Job_x0020_Title>Kru Stick Borongan</Job_x0020_Title>
        <Workgroup>Borongan Stick</Workgroup>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-05T08:00:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-05T17:00:00+07:00</Sign_x0020_Out>
        <HK>1</HK>
        <Created_x0020_By>Ridho</Created_x0020_By>
    </Attendance>
</NewDataSet>
XML;
    }
}
