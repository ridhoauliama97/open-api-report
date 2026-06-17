<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\PengabaianKeterlambatanKehadiranManualReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsPengabaianKeterlambatanKehadiranManualReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(PengabaianKeterlambatanKehadiranManualReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['start_date'] ?? null) === '2026-05-05'
                    && ($filters['end_date'] ?? null) === '2026-06-04'
                    && ($filters['Pilih Status'] ?? null) === 'Staff'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.pengabaian_keterlambatan_kehadiran_manual.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Pengabaian Keterlambatan & Kehadiran Manual (Staff) Per Departemen (RU)'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PengabaianKeterlambatanKehadiranManualReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/pengabaian-keterlambatan-kehadiran-manual/pdf', [
            'DB_CompanyName' => 'RU',
            'Pilih Status' => 'Staff',
            'start_date' => '2026-05-05',
            'end_date' => '2026-06-04',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Pengabaian Keterlambatan & Kehadiran Manual Staff Per Departemen (RU)');
    }

    public function test_shared_attendance_full_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(PengabaianKeterlambatanKehadiranManualReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body', Mockery::type('array'))
            ->andReturn($this->reportData('UC'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.pengabaian_keterlambatan_kehadiran_manual.pdf', Mockery::type('array'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PengabaianKeterlambatanKehadiranManualReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/shared/hrm/attendance-full/pengabaian-keterlambatan-kehadiran-manual/pdf',
                ['DB_CompanyName' => 'UC', 'Pilih Status' => 'KK/KT'],
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

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Pengabaian Keterlambatan & Kehadiran Manual Staff Per Departemen (UC)');
    }

    public function test_shared_attendance_full_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(PengabaianKeterlambatanKehadiranManualReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(PengabaianKeterlambatanKehadiranManualReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/attendance-full/pengabaian-keterlambatan-kehadiran-manual/pdf', [
            'DB_CompanyName' => 'RU',
            'Pilih Status' => 'Staff',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_filters_category_groups_department_and_calculates_creator_summary(): void
    {
        $reportData = app(PengabaianKeterlambatanKehadiranManualReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
                'Pilih Status' => 'Staff',
            ]);

        $this->assertSame(['Dibuat Oleh', 'Nama', 'Jabatan', 'Keterangan', 'Tanggal', 'Absen Masuk', 'Absen Keluar'], $reportData['headers']);
        $this->assertSame('Staff', $reportData['status']);
        $this->assertSame('Dari 05-Mei-26 s/d 06-Mei-26', $reportData['period']['label']);
        $this->assertSame(2, $reportData['total_rows']);
        $this->assertSame('Departemen : Finance & Accounting', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Dina', $reportData['grouped_rows'][0]['rows'][0]['Dibuat Oleh']);
        $this->assertSame('Pengabaian terlambat masuk; Datang terlambat disetujui', $reportData['grouped_rows'][0]['rows'][0]['Keterangan']);
        $this->assertSame('Windi', $reportData['grouped_rows'][0]['rows'][1]['Dibuat Oleh']);
        $this->assertStringNotContainsString('Karyawan KK', json_encode($reportData['rows']));
        $this->assertSame('Dina', $reportData['grand_summary'][0]['label']);
        $this->assertSame(1, $reportData['grand_summary'][0]['count']);
        $this->assertSame(50, $reportData['grand_summary'][0]['percent']);
    }

    public function test_parser_filters_kk_kt_status_from_pilih_status_parameter(): void
    {
        $reportData = app(PengabaianKeterlambatanKehadiranManualReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
                'Pilih Status' => 'KK/KT',
            ]);

        $this->assertSame('KK/KT', $reportData['status']);
        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame('Karyawan KK', $reportData['rows'][0]['Nama']);
        $this->assertSame('Sasi', $reportData['rows'][0]['Dibuat Oleh']);
    }

    public function test_parser_ignores_rows_without_last_modified_by(): void
    {
        $reportData = app(PengabaianKeterlambatanKehadiranManualReportService::class)
            ->buildReportDataFromXml($this->attendanceXmlWithoutLastModifiedBy(), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
                'Pilih Status' => 'Staff',
            ]);

        $this->assertSame(0, $reportData['total_rows']);
        $this->assertSame([], $reportData['rows']);
    }

    public function test_pdf_blade_renders_expected_layout(): void
    {
        $reportData = app(PengabaianKeterlambatanKehadiranManualReportService::class)
            ->buildReportDataFromXml($this->attendanceXml('attendance'), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-06',
                'Pilih Status' => 'Staff',
            ]);
        $reportData['title'] = 'Laporan Pengabaian Keterlambatan & Kehadiran Manual (Staff) Per Departemen (RU)';

        $html = view('ascends.shared.hrm.attendance_full.pengabaian_keterlambatan_kehadiran_manual.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('<h1 class="report-title">RU</h1>', $html);
        $this->assertStringContainsString('Laporan Pengabaian Keterlambatan &amp; Kehadiran Manual (Staff) Per Departemen', $html);
        $this->assertStringContainsString('Dari 05-Mei-26 s/d 06-Mei-26', $html);
        $this->assertStringContainsString('Dibuat<br>Oleh', $html);
        $this->assertStringContainsString('Departemen : Finance &amp; Accounting', $html);
        $this->assertStringContainsString('Akumulasi Di Buat Oleh', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company = 'RU'): array
    {
        return [
            'printed_at' => '05 June 2026 17:00',
            'printed_by' => 'Ridho',
            'company' => $company,
            'status' => 'Staff',
            'category' => 'Staff',
            'title' => "Laporan Pengabaian Keterlambatan & Kehadiran Manual (Staff) Per Departemen ({$company})",
            'headers' => ['No', 'Dibuat Oleh', 'Nama', 'Jabatan', 'Keterangan'],
            'rows' => [],
            'grouped_rows' => [],
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
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Aulia</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Staff Accounting</Job_x0020_Title>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Created_x0020_By>System</Created_x0020_By>
        <Last_x0020_Modified_x0020_By>Dina</Last_x0020_Modified_x0020_By>
        <Ignore_x0020_Late_x0020_Sign_x0020_In>true</Ignore_x0020_Late_x0020_Sign_x0020_In>
        <Remarks>Datang terlambat disetujui</Remarks>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130002</Employee_x0020_Code>
        <Full_x0020_Name>Betty</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Kasir</Job_x0020_Title>
        <Date>2026-05-06T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Created_x0020_By>System</Created_x0020_By>
        <Last_x0020_Modified_x0020_By>Windi</Last_x0020_Modified_x0020_By>
        <Remarks>Kehadiran manual</Remarks>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130003</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan KK</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Created_x0020_By>System</Created_x0020_By>
        <Last_x0020_Modified_x0020_By>Sasi</Last_x0020_Modified_x0020_By>
        <Ignore_x0020_Forget_x0020_Sign_x0020_In>true</Ignore_x0020_Forget_x0020_Sign_x0020_In>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL001</Employee_x0020_Code>
        <Full_x0020_Name>Special User</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Created_x0020_By>System</Created_x0020_By>
        <Last_x0020_Modified_x0020_By>Dina</Last_x0020_Modified_x0020_By>
    </{$recordTag}>
</NewDataSet>
XML;
    }

    private function attendanceXmlWithoutLastModifiedBy(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Aulia</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Staff Accounting</Job_x0020_Title>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Created_x0020_By>Dina</Created_x0020_By>
        <Last_x0020_Modified_x0020_By />
        <Ignore_x0020_Late_x0020_Sign_x0020_In>true</Ignore_x0020_Late_x0020_Sign_x0020_In>
    </Attendance>
</NewDataSet>
XML;
    }
}
