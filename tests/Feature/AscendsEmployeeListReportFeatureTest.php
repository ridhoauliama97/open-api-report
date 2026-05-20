<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ascends\Ru\Hrm\EmployeeListReportService;
use App\Services\PdfGenerator;
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
            ->with('ascends.ru.hrm.employee-list.list_karyawan.pdf', Mockery::on(
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
                'Kode Karyawan',
                'Nama Lengkap',
                'Nama Panggilan',
                'JK',
                'Departemen',
                'Jabatan',
                'Tgl Masuk',
                'Status Kerja',
                'Status Aktif',
            ],
            'rows' => [[
                'Kode Karyawan' => 'RU001',
                'Nama Lengkap' => 'Budi Santoso',
                'Nama Panggilan' => 'Budi',
                'JK' => 'Male',
                'Departemen' => 'HRM',
                'Jabatan' => 'Staff',
                'Tgl Masuk' => '2026-05-20T00:00:00+07:00',
                'Status Kerja' => 'Permanent',
                'Status Aktif' => 'Active',
            ]],
            'grouped_rows' => [
                'HRM' => [[
                    'Kode Karyawan' => 'RU001',
                    'Nama Lengkap' => 'Budi Santoso',
                    'Nama Panggilan' => 'Budi',
                    'JK' => 'Male',
                    'Departemen' => 'HRM',
                    'Jabatan' => 'Staff',
                    'Tgl Masuk' => '2026-05-20T00:00:00+07:00',
                    'Status Kerja' => 'Permanent',
                    'Status Aktif' => 'Active',
                ]],
            ],
            'total_rows' => 1,
            'summary' => [
                'department_count' => 1,
                'gender_summary' => ['Male' => 1],
                'top_departments' => ['HRM' => 1],
            ],
        ];
    }
}
