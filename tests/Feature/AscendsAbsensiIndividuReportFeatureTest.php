<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\AbsensiIndividuReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsAbsensiIndividuReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_absensi_individu_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(AbsensiIndividuReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn(array $filters): bool => ($filters['employee_code'] ?? null) === '130016'
                && ($filters['start_date'] ?? null) === '2026-05-05'
                && ($filters['end_date'] ?? null) === '2026-06-04'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.absensi_individu.pdf', Mockery::on(
                static fn(array $data): bool => ($data['company'] ?? null) === 'RU'
                && str_contains((string) ($data['reportData']['title'] ?? ''), 'Laporan Absensi Individu')
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(AbsensiIndividuReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/absensi-individu/pdf', [
            'company' => 'RU',
            'employee_code' => '130016',
            'start_date' => '2026-05-05',
            'end_date' => '2026-06-04',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Attendance Full - Laporan Absensi Individu');
    }

    public function test_shared_attendance_full_absensi_individu_api_can_render_raw_xml_body_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(AbsensiIndividuReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body', Mockery::type('array'))
            ->andReturn($this->reportData('GSU'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.absensi_individu.pdf', Mockery::type('array'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(AbsensiIndividuReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/shared/hrm/attendance-full/absensi-individu/pdf',
                ['company' => 'GSU', 'employee_name' => 'Riza'],
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

        $this->assertPdfDisposition($response, 'attachment', 'Attendance Full - Laporan Absensi Individu (GSU)');
    }

    public function test_shared_attendance_full_absensi_individu_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(AbsensiIndividuReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(AbsensiIndividuReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/attendance-full/absensi-individu/pdf', [
            'company' => 'RU',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_builds_individual_attendance_rows_and_summary(): void
    {
        $reportData = app(AbsensiIndividuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'employee_code' => '130016',
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
            ]);

        $this->assertSame('Dari 05-Mei-26 s/d 06-Mei-26', $reportData['period']['label']);
        $this->assertSame('Riza Apriadi', $reportData['employees'][0]['employee']['name']);
        $this->assertSame('Ka. Div. Produksi Hulu', $reportData['employees'][0]['employee']['job_title']);
        $this->assertSame(['Hari', 'Absen Masuk', 'Absen Keluar', 'Waktu Bekerja'], $reportData['headers']);
        $this->assertSame(2, $reportData['total_rows']);
        $this->assertSame('Selasa', $reportData['employees'][0]['rows'][0]['Hari']);
        $this->assertSame('05-Mei-26 07:25:49', $reportData['employees'][0]['rows'][0]['Absen Masuk']);
        $this->assertSame('05-Mei-26 18:32:58', $reportData['employees'][0]['rows'][0]['Absen Keluar']);
        $this->assertSame('10:07:09', $reportData['employees'][0]['rows'][0]['Waktu Bekerja']);
        $this->assertSame('19 Jam 8 Menit', $reportData['employees'][0]['summary']['total']);
    }

    public function test_parser_defaults_to_all_employees(): void
    {
        $reportData = app(AbsensiIndividuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
            ]);

        $this->assertSame(2, $reportData['total_employees']);
        $this->assertSame(3, $reportData['total_rows']);
        $this->assertSame('Riza Apriadi', $reportData['employees'][0]['employee']['name']);
        $this->assertGreaterThan(
            $reportData['employees'][1]['summary']['total_seconds'],
            $reportData['employees'][0]['summary']['total_seconds']
        );
    }

    public function test_pdf_blade_renders_expected_layout(): void
    {
        $reportData = app(AbsensiIndividuReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'employee_name' => 'Riza',
            ]);
        $reportData['company'] = 'RU';
        $reportData['title'] = 'Laporan Absensi Individu';

        $html = view('ascends.shared.hrm.attendance_full.absensi_individu.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'headers' => $reportData['headers'],
            'rows' => $reportData['rows'],
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Absensi Individu', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('Riza Apriadi', $html);
        $this->assertStringContainsString('Absen Masuk', $html);
        $this->assertStringContainsString('Waktu Bekerja', $html);
        $this->assertStringContainsString('Akumulasi Min', $html);
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
            'title' => "Laporan Absensi Individu ({$company})",
            'period' => ['label' => 'Dari 05/05/2026 Sampai 04/06/2026'],
            'employee' => ['name' => 'Riza Apriadi', 'job_title' => 'Ka. Div. Produksi Hulu'],
            'headers' => ['Hari', 'Absen Masuk', 'Absen Keluar', 'Waktu Bekerja'],
            'rows' => [],
            'summary' => ['total' => '0 Jam 0 Menit'],
            'total_rows' => 0,
        ];
    }

    private function attendanceXml(string $recordTag = 'Attendance'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>130016</Employee_x0020_Code>
        <Full_x0020_Name>Riza Apriadi</Full_x0020_Name>
        <Job_x0020_Title>Ka. Div. Produksi Hulu</Job_x0020_Title>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Day>Tuesday</Day>
        <Sign_x0020_In>2026-05-05T07:25:49+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-05T18:32:58+07:00</Sign_x0020_Out>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Created_x0020_By>Ridho</Created_x0020_By>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130016</Employee_x0020_Code>
        <Full_x0020_Name>Riza Apriadi</Full_x0020_Name>
        <Job_x0020_Title>Ka. Div. Produksi Hulu</Job_x0020_Title>
        <Date>2026-05-06T00:00:00+07:00</Date>
        <Day>Wednesday</Day>
        <Sign_x0020_In>2026-05-06T07:25:44+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-06T17:27:29+07:00</Sign_x0020_Out>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>999999</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Lain</Full_x0020_Name>
        <Job_x0020_Title>Staff</Job_x0020_Title>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Day>Tuesday</Day>
        <Sign_x0020_In>2026-05-05T08:00:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-05T17:00:00+07:00</Sign_x0020_Out>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
