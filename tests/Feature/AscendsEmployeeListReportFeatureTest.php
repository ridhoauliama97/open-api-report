<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ascends\Ru\Hrm\EmployeeListReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsEmployeeListReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_api_pdf_endpoint_can_render_xml_payload_from_ascend(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
  <Employees>
    <Employee_x0020_Code>RU001</Employee_x0020_Code>
    <Full_x0020_Name>Budi Santoso</Full_x0020_Name>
    <Nick_x0020_Name>Budi</Nick_x0020_Name>
    <Sex>Male</Sex>
    <Department_x0020_Name>HRM</Department_x0020_Name>
    <Job_x0020_Title>Staff</Job_x0020_Title>
    <Join_x0020_Date>2026-05-20T00:00:00+07:00</Join_x0020_Date>
    <Job_x0020_Status>Permanent</Job_x0020_Status>
    <Active>Active</Active>
  </Employees>
</NewDataSet>
XML;

        $service = Mockery::mock(EmployeeListReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request field: xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.list_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['source_file'] ?? null) === 'request field: xml'
                && ($data['reportData']['total_rows'] ?? null) === 1
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(EmployeeListReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->withHeaders($this->authHeaders($user, 'application/pdf'))
            ->postJson('/api/reports/ascends/ru/hrm/employee-list/list-karyawan/pdf', [
                'xml' => $xml,
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'List Karyawan RU');
    }

    public function test_api_pdf_endpoint_rejects_request_without_ascend_xml_payload(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(EmployeeListReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(EmployeeListReportService::class, $service);

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/reports/ascends/ru/hrm/employee-list/list-karyawan/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_ascend_test_upload_form_can_render_uploaded_xml_as_pdf(): void
    {
        $xml = $this->employeeListXml('employees');

        $service = Mockery::mock(EmployeeListReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.list_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(EmployeeListReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'List Karyawan RU');
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml('employees');

        $service = Mockery::mock(EmployeeListReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.list_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(EmployeeListReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/list-karyawan/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'List Karyawan RU');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml('employees');

        $service = Mockery::mock(EmployeeListReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.list_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(EmployeeListReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/list-karyawan/pdf',
                [],
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

        $this->assertPdfDisposition($response, 'attachment', 'List Karyawan RU');
    }

    public function test_internal_ascend_api_can_render_uc_list_karyawan_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml('employees');

        $service = Mockery::mock(EmployeeListReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list-uc.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.uc.hrm.list_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(EmployeeListReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/uc/hrm/list-karyawan/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list-uc.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'List Karyawan UC');
    }

    public function test_internal_ascend_api_can_render_gsu_list_karyawan_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml('employees');

        $service = Mockery::mock(EmployeeListReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list-gsu.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.gsu.hrm.list_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(EmployeeListReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/gsu/hrm/list-karyawan/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list-gsu.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'List Karyawan GSU');
    }

    public function test_ascend_test_upload_form_can_preview_gsu_list_karyawan_pdf(): void
    {
        $xml = $this->employeeListXml('employees');

        $service = Mockery::mock(EmployeeListReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list-gsu.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.gsu.hrm.list_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(EmployeeListReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'company' => 'GSU',
            'report_module' => 'hrm_analysis_reports',
            'report_type' => 'gsu_list_karyawan',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list-gsu.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'List Karyawan GSU');
    }

    public function test_ascend_test_upload_form_can_preview_uc_list_karyawan_pdf(): void
    {
        $xml = $this->employeeListXml('employees');

        $service = Mockery::mock(EmployeeListReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list-uc.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.uc.hrm.list_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(EmployeeListReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'company' => 'UC',
            'report_module' => 'hrm_analysis_reports',
            'report_type' => 'uc_list_karyawan',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list-uc.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'List Karyawan UC');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(EmployeeListReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(EmployeeListReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/list-karyawan/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_employee_list_xml_parser_accepts_lowercase_ascend_record_tag(): void
    {
        $reportData = app(EmployeeListReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame([
            'Nama',
            'Jenis Kelamin',
            'Usia',
            'Jabatan',
            'Lama Bekerja',
            'Keterangan',
            'Nama Tempat Ibadah',
            'Lemari',
        ], $reportData['headers']);
        $this->assertSame('Budi Santoso', $reportData['rows'][0]['Nama'] ?? null);
        $this->assertSame('Wanita', $reportData['rows'][0]['Jenis Kelamin'] ?? null);
        $this->assertSame('30 Thn', $reportData['rows'][0]['Usia'] ?? null);
        $this->assertSame('1 Thn 2 Bln', $reportData['rows'][0]['Lama Bekerja'] ?? null);
        $this->assertSame('HRM', array_key_first($reportData['grouped_rows'] ?? []));
        $this->assertArrayNotHasKey('Tanpa Departemen', $reportData['grouped_rows'] ?? []);
    }

    public function test_ascend_test_upload_form_lists_uc_hrm_list_karyawan_report(): void
    {
        $this->get('/ascend-test')
            ->assertOk()
            ->assertSee('GSU')
            ->assertSee('List Karyawan (GSU)')
            ->assertSee('UC')
            ->assertSee('HRM Analysis Reports')
            ->assertSee('List Karyawan (UC)');
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user, string $accept = 'application/json'): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueJwtForUser($user),
            'Accept' => $accept,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'printed_at' => '20 Mei 2026 10:00',
            'company' => 'RU',
            'module' => 'hrm',
            'sub_report' => 'employee_list',
            'label' => 'List Karyawan RU',
            'title' => 'List Karyawan RU',
            'source_file' => 'request field: xml',
            'headers' => [
                'Nama',
                'Jenis Kelamin',
                'Usia',
                'Jabatan',
                'Lama Bekerja',
                'Keterangan',
                'Nama Tempat Ibadah',
                'Lemari',
            ],
            'rows' => [
                [
                    'Nama' => 'Budi Santoso',
                    'Jenis Kelamin' => 'Wanita',
                    'Usia' => '30 Thn',
                    'Jabatan' => 'Staff',
                    'Lama Bekerja' => '1 Thn 2 Bln',
                    'Keterangan' => '',
                    'Nama Tempat Ibadah' => '',
                    'Lemari' => '',
                    'Departemen' => 'HRM',
                ],
            ],
            'grouped_rows' => [
                'HRM' => [
                    [
                        'Nama' => 'Budi Santoso',
                        'Jenis Kelamin' => 'Wanita',
                        'Usia' => '30 Thn',
                        'Jabatan' => 'Staff',
                        'Lama Bekerja' => '1 Thn 2 Bln',
                        'Keterangan' => '',
                        'Nama Tempat Ibadah' => '',
                        'Lemari' => '',
                        'Departemen' => 'HRM',
                    ],
                ],
            ],
            'total_rows' => 1,
            'summary' => [
                'department_count' => 1,
                'gender_summary' => ['Wanita' => 1],
                'top_departments' => ['HRM' => 1],
            ],
        ];
    }

    private function employeeListXml(string $recordTag = 'Employees'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>RU001</Employee_x0020_Code>
        <Full_x0020_Name>Budi Santoso</Full_x0020_Name>
        <Nick_x0020_Name>Budi</Nick_x0020_Name>
        <Sex>Female</Sex>
        <Department_x0020_Name>HRM</Department_x0020_Name>
        <Age>30</Age>
        <Job_x0020_Title>Staff</Job_x0020_Title>
        <Join_x0020_Date>2026-05-20T00:00:00+07:00</Join_x0020_Date>
        <Working_x0020_Years>1</Working_x0020_Years>
        <Working_x0020_Months>2</Working_x0020_Months>
        <Job_x0020_Status>Permanent</Job_x0020_Status>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>RU002</Employee_x0020_Code>
        <Full_x0020_Name>Departemen Kosong</Full_x0020_Name>
        <Nick_x0020_Name>Kosong</Nick_x0020_Name>
        <Sex>Male</Sex>
        <Department_x0020_Name></Department_x0020_Name>
        <Age>25</Age>
        <Job_x0020_Title>Staff</Job_x0020_Title>
        <Join_x0020_Date>2026-05-20T00:00:00+07:00</Join_x0020_Date>
        <Working_x0020_Years>1</Working_x0020_Years>
        <Working_x0020_Months>2</Working_x0020_Months>
        <Job_x0020_Status>Permanent</Job_x0020_Status>
        <Active>Active</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
