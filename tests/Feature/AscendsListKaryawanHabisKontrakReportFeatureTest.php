<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\ListKaryawanHabisKontrakReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsListKaryawanHabisKontrakReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_hrm_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(ListKaryawanHabisKontrakReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml', Mockery::on(
                static fn (array $filters): bool => (string) ($filters['month'] ?? '') === '6'
                && (string) ($filters['year'] ?? '') === '2026'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.employee_list.list_karyawan_habis_kontrak.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                && str_contains((string) ($data['reportData']['title'] ?? ''), 'Laporan List Karyawan Habis Kontrak')
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(ListKaryawanHabisKontrakReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/list-karyawan-habis-kontrak/pdf', [
            'company' => 'RU',
            'month' => 6,
            'year' => 2026,
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Employee List - Laporan List Karyawan Habis Kontrak');
    }

    public function test_shared_hrm_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(ListKaryawanHabisKontrakReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body', Mockery::on(
                static fn (array $filters): bool => (string) ($filters['month'] ?? '') === '7'
                && (string) ($filters['year'] ?? '') === '2026'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.employee_list.list_karyawan_habis_kontrak.pdf', Mockery::type('array'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(ListKaryawanHabisKontrakReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/shared/hrm/list-karyawan-habis-kontrak/pdf',
                ['company' => 'UC', 'month' => 7, 'year' => 2026],
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

        $this->assertPdfDisposition($response, 'attachment', 'Employee List - Laporan List Karyawan Habis Kontrak (UC)');
    }

    public function test_shared_hrm_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(ListKaryawanHabisKontrakReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(ListKaryawanHabisKontrakReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/list-karyawan-habis-kontrak/pdf', [
            'company' => 'RU',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_filters_active_contract_rows_with_expiry_date(): void
    {
        $reportData = app(ListKaryawanHabisKontrakReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml', ['month' => 6, 'year' => 2026]);

        $this->assertSame(['Code', 'Full Name', 'Job Title', 'Department', 'Join Date', 'Expiry Date', 'Active'], $reportData['headers']);
        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame('1002', $reportData['rows'][0]['Code']);
        $this->assertSame('Andri Ardiwa', $reportData['rows'][0]['Full Name']);
        $this->assertSame('14-Feb-25', $reportData['rows'][0]['Join Date']);
        $this->assertSame('14-Jun-26', $reportData['rows'][0]['Expiry Date']);
        $this->assertSame('Juni 2026', $reportData['period']['label']);
        $this->assertStringNotContainsString('Permanent User', json_encode($reportData['rows']));
        $this->assertStringNotContainsString('Ronauli Sitompul', json_encode($reportData['rows']));
        $this->assertStringNotContainsString('Fajar Gunawan', json_encode($reportData['rows']));
        $this->assertStringNotContainsString('No Expiry User', json_encode($reportData['rows']));
        $this->assertStringNotContainsString('Special User', json_encode($reportData['rows']));
    }

    public function test_parser_can_filter_by_expiry_date_range(): void
    {
        $reportData = app(ListKaryawanHabisKontrakReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml', [
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
            ]);

        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame('Ronauli Sitompul', $reportData['rows'][0]['Full Name']);
        $this->assertSame('01-Jul-26', $reportData['rows'][0]['Expiry Date']);
    }

    public function test_pdf_blade_renders_expected_headers_rows_and_total(): void
    {
        $reportData = app(ListKaryawanHabisKontrakReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml', ['month' => 6, 'year' => 2026]);
        $reportData['title'] = 'Laporan List Karyawan Habis Kontrak';

        $html = view('ascends.shared.hrm.employee_list.list_karyawan_habis_kontrak.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan List Karyawan Habis Kontrak', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('NIK', $html);
        $this->assertStringContainsString('Nama Lengkap', $html);
        $this->assertStringContainsString('Jabatan', $html);
        $this->assertStringContainsString('Departemen', $html);
        $this->assertStringContainsString('Tanggal Masuk', $html);
        $this->assertStringContainsString('Tanggal Berakhir', $html);
        $this->assertStringContainsString('Status', $html);
        $this->assertStringContainsString('<td class="center">1</td>', $html);
        $this->assertStringContainsString('Andri Ardiwa', $html);
        $this->assertStringContainsString('Grand Total = 1', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'printed_at' => '04 June 2026 09:21',
            'printed_by' => 'Ridho',
            'company' => 'RU',
            'title' => 'Laporan List Karyawan Habis Kontrak',
            'headers' => ['Code', 'Full Name', 'Job Title', 'Department', 'Join Date', 'Expiry Date', 'Active'],
            'rows' => [],
            'total_rows' => 0,
        ];
    }

    private function employeeListXml(string $recordTag = 'Employees'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>1001</Employee_x0020_Code>
        <Full_x0020_Name>Permanent User</Full_x0020_Name>
        <Job_x0020_Title>Staff Accounting</Job_x0020_Title>
        <Department_x0020_Name>Finance</Department_x0020_Name>
        <Join_x0020_Date>2025-01-01T00:00:00+07:00</Join_x0020_Date>
        <Expiry_x0020_Date>2026-07-01T00:00:00+07:00</Expiry_x0020_Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
        <Nama_x0020_User>Ridho</Nama_x0020_User>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1002</Employee_x0020_Code>
        <Full_x0020_Name>Andri Ardiwa</Full_x0020_Name>
        <Job_x0020_Title>Kru Mesin SLP</Job_x0020_Title>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Join_x0020_Date>2025-02-14T00:00:00+07:00</Join_x0020_Date>
        <Expiry_x0020_Date>2026-06-14T00:00:00+07:00</Expiry_x0020_Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1003</Employee_x0020_Code>
        <Full_x0020_Name>Ronauli Sitompul</Full_x0020_Name>
        <Job_x0020_Title>Kru Grader Sawmill</Job_x0020_Title>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Join_x0020_Date>2025-03-01T00:00:00+07:00</Join_x0020_Date>
        <Expiry_x0020_Date>2026-07-01T00:00:00+07:00</Expiry_x0020_Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1004</Employee_x0020_Code>
        <Full_x0020_Name>No Expiry User</Full_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Join_x0020_Date>2025-04-01T00:00:00+07:00</Join_x0020_Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1005</Employee_x0020_Code>
        <Full_x0020_Name>Fajar Gunawan</Full_x0020_Name>
        <Job_x0020_Title>Operator KD</Job_x0020_Title>
        <Department_x0020_Name>Vacuum &amp; K/D</Department_x0020_Name>
        <Join_x0020_Date>2019-12-12T00:00:00+07:00</Join_x0020_Date>
        <Expiry_x0020_Date>2026-12-12T00:00:00+07:00</Expiry_x0020_Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 1</Employee_x0020_Code>
        <Full_x0020_Name>Special User</Full_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Join_x0020_Date>2025-04-01T00:00:00+07:00</Join_x0020_Date>
        <Expiry_x0020_Date>2026-09-01T00:00:00+07:00</Expiry_x0020_Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
