<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KaryawanAktifPerDepartemenReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKaryawanAktifPerDepartemenReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanAktifPerDepartemenReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_aktif_per_departemen.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanAktifPerDepartemenReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/karyawan-aktif-per-departemen/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Aktif Per Departemen');
    }

    public function test_ascend_test_upload_form_can_preview_karyawan_aktif_per_departemen_pdf(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanAktifPerDepartemenReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_aktif_per_departemen.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Aktif Per Departemen'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanAktifPerDepartemenReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_type' => 'karyawan_aktif_per_departemen',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Aktif Per Departemen');
    }

    public function test_internal_ascend_api_can_render_uc_karyawan_aktif_per_departemen_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanAktifPerDepartemenReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData('Laporan Karyawan Aktif Per Departemen (UC)'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.uc.hrm.karyawan_aktif_per_departemen.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanAktifPerDepartemenReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/uc/hrm/karyawan-aktif-per-departemen/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Aktif Per Departemen (UC)');
    }

    public function test_ascend_test_upload_form_can_preview_uc_karyawan_aktif_per_departemen_pdf(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanAktifPerDepartemenReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData('Laporan Karyawan Aktif Per Departemen (UC)'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.uc.hrm.karyawan_aktif_per_departemen.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Aktif Per Departemen (UC)'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanAktifPerDepartemenReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'company' => 'UC',
            'report_module' => 'hrm_analysis_reports',
            'report_type' => 'uc_karyawan_aktif_per_departemen',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Aktif Per Departemen (UC)');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanAktifPerDepartemenReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanAktifPerDepartemenReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/karyawan-aktif-per-departemen/pdf',
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

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Aktif Per Departemen');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KaryawanAktifPerDepartemenReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KaryawanAktifPerDepartemenReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/karyawan-aktif-per-departemen/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_karyawan_aktif_per_departemen_parser_groups_non_special_active_rows_and_builds_summaries(): void
    {
        $reportData = app(KaryawanAktifPerDepartemenReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'Nama',
            'Status',
            'L/P',
            'Jabatan',
            'Level',
            'Strata Pend',
            'Tanggal Masuk',
        ], $reportData['headers']);
        $this->assertSame(4, $reportData['total_rows']);

        $this->assertSame('Departemen : Finance & Accounting', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Ferra Novita', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertSame('04-Agt-2017', $reportData['grouped_rows'][0]['rows'][0]['Tanggal Masuk']);
        $this->assertSame('Evi Seroja Tampubolon', $reportData['grouped_rows'][0]['rows'][1]['Nama']);
        $this->assertSame(2, $reportData['grouped_rows'][0]['summary']['subtotal']);

        $financeSummary = $reportData['grouped_rows'][0]['summary'];
        $this->assertSame(0, $financeSummary['gender']['L']['count']);
        $this->assertSame(2, $financeSummary['gender']['P']['count']);
        $this->assertSame(2, $financeSummary['status']['ST']['count']);
        $this->assertSame(2, $financeSummary['education']['S1']['count']);
        $this->assertSame(1, $financeSummary['level']['Level 2']['count']);
        $this->assertSame(1, $financeSummary['level']['Level 4']['count']);

        $this->assertSame('Departemen : Sawmill', $reportData['grouped_rows'][1]['label']);
        $this->assertSame(2, $reportData['grouped_rows'][1]['summary']['subtotal']);
        $this->assertSame(4, $reportData['grand_summary']['subtotal']);
        $this->assertSame(1, $reportData['grand_summary']['status']['BR']['count']);
        $this->assertSame(1, $reportData['grand_summary']['status']['KK']['count']);
        $this->assertSame(2, $reportData['grand_summary']['status']['ST']['count']);
        $this->assertStringNotContainsString(
            'Tanpa Departemen',
            implode('|', array_column($reportData['grouped_rows'], 'label'))
        );
    }

    public function test_karyawan_aktif_per_departemen_prefers_xml_sex_over_identity_number_gender(): void
    {
        $reportData = app(KaryawanAktifPerDepartemenReportService::class)
            ->buildReportDataFromXml($this->employeeListXmlWithConflictingGender(), 'test xml');

        $this->assertSame('Jonatan Pardamean Lase', $reportData['rows'][0]['Nama']);
        $this->assertSame('L', $reportData['rows'][0]['L/P']);
        $this->assertSame(1, $reportData['grand_summary']['gender']['L']['count']);
        $this->assertSame(0, $reportData['grand_summary']['gender']['P']['count']);
    }

    public function test_karyawan_aktif_per_departemen_pdf_renders_expected_headers_groups_and_summary(): void
    {
        $reportData = app(KaryawanAktifPerDepartemenReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.ru.hrm.karyawan_aktif_per_departemen.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Karyawan Aktif Per Departemen', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('Status', $html);
        $this->assertStringContainsString('L/P', $html);
        $this->assertStringContainsString('Jabatan', $html);
        $this->assertStringContainsString('Level', $html);
        $this->assertStringContainsString('Strata', $html);
        $this->assertStringContainsString('Tanggal', $html);
        $this->assertStringContainsString('Departemen : Finance &amp; Accounting', $html);
        $this->assertStringContainsString('Sub Total', $html);
        $this->assertStringContainsString('Akumulasi L/P', $html);
        $this->assertStringContainsString('Akumulasi Status', $html);
        $this->assertStringContainsString('Akumulasi Strata Pend.', $html);
        $this->assertStringContainsString('Akumulasi Level', $html);
        $this->assertStringContainsString('• Perempuan = 2 (100%)', $html);
        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringNotContainsString('Shinta Kartika', $html);
        $this->assertStringNotContainsString('Dedi Nonaktif', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $title = 'Laporan Karyawan Aktif Per Departemen'): array
    {
        return [
            'printed_at' => '20 Mei 2026 10:00',
            'company' => str_contains($title, '(UC)') ? 'UC' : 'RU',
            'module' => 'hrm',
            'sub_report' => 'karyawan_aktif_per_departemen',
            'label' => $title,
            'title' => $title,
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'Nama',
                'Status',
                'L/P',
                'Jabatan',
                'Level',
                'Strata Pend',
                'Tanggal Masuk',
            ],
            'rows' => [[
                'Nama' => 'Ferra Novita',
                'Status' => 'ST',
                'L/P' => 'P',
                'Jabatan' => 'Staff Kasir RU',
                'Level' => '2',
                'Strata Pend' => 'S1',
                'Tanggal Masuk' => '04/08/2017',
            ]],
            'grouped_rows' => [[
                'label' => 'Departemen : Finance & Accounting',
                'rows' => [[
                    'Nama' => 'Ferra Novita',
                    'Status' => 'ST',
                    'L/P' => 'P',
                    'Jabatan' => 'Staff Kasir RU',
                    'Level' => '2',
                    'Strata Pend' => 'S1',
                    'Tanggal Masuk' => '04/08/2017',
                ]],
                'summary' => ['subtotal' => 1],
            ]],
            'grand_summary' => ['subtotal' => 1],
            'total_rows' => 1,
        ];
    }

    private function employeeListXml(string $recordTag = 'Employees'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>132200</Employee_x0020_Code>
        <Full_x0020_Name>Evi Seroja Tampubolon</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Sex>Female</Sex>
        <IdentityNo>1275045705760001</IdentityNo>
        <Job_x0020_Title>Ka. Div. Accounting RU</Job_x0020_Title>
        <Level_x0020_Name>4</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>S1</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2018-03-13T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132201</Employee_x0020_Code>
        <Full_x0020_Name>Ferra Novita</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Sex>Female</Sex>
        <Job_x0020_Title>Staff Kasir RU</Job_x0020_Title>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>S1</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2017-08-04T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 3</Employee_x0020_Code>
        <Full_x0020_Name>Shinta Kartika</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Sex>Female</Sex>
        <Job_x0020_Title>Ka. Dept. Finance &amp; Accounting RU</Job_x0020_Title>
        <Level_x0020_Name>4</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>S1</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>1997-02-11T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132101</Employee_x0020_Code>
        <Full_x0020_Name>Adek Arianda</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Sex>Male</Sex>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>SMA</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2026-05-20T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131465</Employee_x0020_Code>
        <Full_x0020_Name>Ade Yulinda Sari</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Sex>Female</Sex>
        <Job_x0020_Title>Kru Table Saw</Job_x0020_Title>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>SMK</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2021-08-24T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132099</Employee_x0020_Code>
        <Full_x0020_Name>Dedi Nonaktif</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Packing</Job_x0020_Title>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>SMP</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2022-01-01T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Stock</Department_x0020_Name>
        <Active>Terminated</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132300</Employee_x0020_Code>
        <Full_x0020_Name>Departemen Kosong</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Tanpa Departemen</Job_x0020_Title>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>SMA</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2024-01-01T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name></Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }

    private function employeeListXmlWithConflictingGender(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <employees>
        <Employee_x0020_Code>110426</Employee_x0020_Code>
        <Full_x0020_Name>Jonatan Pardamean Lase</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Sex>Male</Sex>
        <IdentityNo>1207234608060003</IdentityNo>
        <Job_x0020_Title>Kru GA</Job_x0020_Title>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>SMA</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2025-09-09T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>HRGA</Department_x0020_Name>
        <Active>Active</Active>
    </employees>
</NewDataSet>
XML;
    }
}
