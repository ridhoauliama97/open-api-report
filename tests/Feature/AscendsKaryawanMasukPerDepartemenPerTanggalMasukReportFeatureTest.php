<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\DaftarKaryawanReportService;
use App\Services\Ascends\Ru\Hrm\KaryawanMasukPerDepartemenPerTanggalMasukReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKaryawanMasukPerDepartemenPerTanggalMasukReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uc_karyawan_masuk_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.uc.hrm.karyawan_masuk_per_departemen_per_tanggal_masuk.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 2
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/uc/hrm/karyawan-masuk-per-departemen-per-tanggal-masuk/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk');
    }

    public function test_ascend_test_upload_form_can_preview_uc_karyawan_masuk_pdf(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.uc.hrm.karyawan_masuk_per_departemen_per_tanggal_masuk.pdf', Mockery::on(
                static fn (array $data): bool => str_contains((string) ($data['reportData']['title'] ?? ''), 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk')
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'company' => 'UC',
            'report_module' => 'hrm_analysis_reports',
            'report_type' => 'uc_karyawan_masuk_per_departemen_per_tanggal_masuk',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (UC)');
    }

    public function test_shared_hrm_test_route_can_render_selected_company_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.employee_list.karyawan_masuk_per_departemen_per_tanggal_masuk.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'GSU'
                    && ($data['reportData']['company'] ?? null) === 'GSU'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (GSU)'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/karyawan-masuk-per-departemen-per-tanggal-masuk/pdf', [
            'company' => 'GSU',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Employee List - Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (GSU)');
    }

    public function test_shared_hrm_generic_route_can_render_daftar_karyawan_for_selected_company(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(DaftarKaryawanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.employee_list.daftar_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'UC'
                    && ($data['reportData']['company'] ?? null) === 'UC'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Daftar Karyawan (UC)'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DaftarKaryawanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/daftar-karyawan/pdf', [
            'company' => 'UC',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Employee List - Laporan Daftar Karyawan (UC)');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class, $service);

        $this->postJson('/api/internal/ascends/uc/hrm/karyawan-masuk-per-departemen-per-tanggal-masuk/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.uc.hrm.karyawan_masuk_per_departemen_per_tanggal_masuk.pdf', Mockery::on(
                static fn (array $data): bool => str_contains((string) ($data['reportData']['title'] ?? ''), 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk')
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/uc/hrm/karyawan-masuk-per-departemen-per-tanggal-masuk/pdf',
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

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (UC)');
    }

    public function test_karyawan_masuk_parser_groups_by_department_and_join_date(): void
    {
        $reportData = app(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'Nama',
            'L/P',
            'Jabatan',
            'Status',
            'Level',
            'Pendidikan Terakhir',
            'Tanggal Masuk',
        ], $reportData['headers']);
        $this->assertSame(5, $reportData['total_rows']);
        $this->assertSame('Departemen : ', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Departemen : Finance & Accounting', $reportData['grouped_rows'][1]['label']);
        $this->assertArrayNotHasKey('date_groups', $reportData['grouped_rows'][1]);
        $this->assertSame('Ferra Novita', $reportData['grouped_rows'][1]['rows'][0]['Nama']);
        $this->assertSame('P', $reportData['grouped_rows'][1]['rows'][0]['L/P']);
        $this->assertSame('04-Agt-2017', $reportData['grouped_rows'][1]['rows'][0]['Tanggal Masuk']);
        $this->assertSame('Departemen : Sawmill', $reportData['grouped_rows'][2]['label']);
        $this->assertStringContainsString('Dedi Nonaktif', json_encode($reportData['grouped_rows']));
        $this->assertStringContainsString('Departemen Kosong', json_encode($reportData['grouped_rows']));
        $this->assertStringNotContainsString('Special User', json_encode($reportData['grouped_rows']));
    }

    public function test_karyawan_masuk_pdf_renders_expected_headers_groups_and_totals(): void
    {
        $reportData = app(KaryawanMasukPerDepartemenPerTanggalMasukReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.uc.hrm.karyawan_masuk_per_departemen_per_tanggal_masuk.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('L/P', $html);
        $this->assertStringContainsString('Tanggal', $html);
        $this->assertStringContainsString('Departemen : Finance &amp; Accounting', $html);
        $this->assertStringContainsString('04-Agt-2017', $html);
        $this->assertStringNotContainsString('Tanggal Masuk : 04-Agt-2017', $html);
        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringContainsString('<td colspan="2">5</td>', $html);
        $this->assertStringContainsString('Akumulasi L/P', $html);
        $this->assertStringContainsString('Akumulasi Status', $html);
        $this->assertStringContainsString('Akumulasi Pendidikan', $html);
        $this->assertStringContainsString('Akumulasi Level', $html);
        $this->assertStringContainsString('• Laki - Laki = 3 (60%)', $html);
        $this->assertStringContainsString('• Perempuan = 2 (40%)', $html);
        $this->assertStringContainsString('• KK = 2 (40%)', $html);
        $this->assertStringContainsString('• ST = 2 (40%)', $html);
        $this->assertStringContainsString('Dedi Nonaktif', $html);
        $this->assertStringContainsString('Departemen Kosong', $html);
        $this->assertStringNotContainsString('Special User', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'printed_at' => '20 Mei 2026 10:00',
            'company' => 'UC',
            'module' => 'hrm',
            'sub_report' => 'karyawan_masuk_per_departemen_per_tanggal_masuk',
            'label' => 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk',
            'title' => 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk',
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'Nama',
                'L/P',
                'Jabatan',
                'Status',
                'Level',
                'Pendidikan Terakhir',
                'Tanggal Masuk',
            ],
            'rows' => [[
                'Nama' => 'Ferra Novita',
                'L/P' => 'P',
                'Jabatan' => 'Staff Kasir',
                'Status' => 'ST',
                'Level' => '2',
                'Pendidikan Terakhir' => 'S1',
                'Tanggal Masuk' => '04-Agt-2017',
            ]],
            'grouped_rows' => [[
                'label' => 'Departemen : Finance & Accounting',
                'subtotal' => 2,
                'rows' => [[
                    'Nama' => 'Ferra Novita',
                    'L/P' => 'P',
                    'Jabatan' => 'Staff Kasir',
                    'Status' => 'ST',
                    'Level' => '2',
                    'Pendidikan Terakhir' => 'S1',
                    'Tanggal Masuk' => '04-Agt-2017',
                ]],
            ]],
            'total_rows' => 2,
        ];
    }

    private function employeeListXml(string $recordTag = 'Employees'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>132201</Employee_x0020_Code>
        <Full_x0020_Name>Ferra Novita</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Staff Kasir</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>S1</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2017-08-04T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132200</Employee_x0020_Code>
        <Full_x0020_Name>Evi Seroja Tampubolon</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Ka. Div. Accounting</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>4</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>S1</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2018-03-13T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132101</Employee_x0020_Code>
        <Full_x0020_Name>Adek Arianda</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Operator Sawmill</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>SMA</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2026-05-20T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 1</Employee_x0020_Code>
        <Full_x0020_Name>Special User</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Special</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Join_x0020_Date>2026-05-20T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132099</Employee_x0020_Code>
        <Full_x0020_Name>Dedi Nonaktif</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Packing</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Join_x0020_Date>2022-01-01T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Stock</Department_x0020_Name>
        <Active>Terminated</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132300</Employee_x0020_Code>
        <Full_x0020_Name>Departemen Kosong</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Kosong</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Join_x0020_Date>2024-01-01T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name></Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
