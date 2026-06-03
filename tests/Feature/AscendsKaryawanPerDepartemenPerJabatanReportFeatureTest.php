<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KaryawanPerDepartemenPerJabatanReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKaryawanPerDepartemenPerJabatanReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerDepartemenPerJabatanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_departemen_per_jabatan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerDepartemenPerJabatanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/karyawan-per-departemen-per-jabatan/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Per Departemen Per Jabatan (RU)');
    }

    public function test_ascend_test_upload_form_can_preview_karyawan_per_departemen_per_jabatan_pdf(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerDepartemenPerJabatanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_departemen_per_jabatan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Per Departemen Per Jabatan (RU)'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerDepartemenPerJabatanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_type' => 'karyawan_per_departemen_per_jabatan',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Per Departemen Per Jabatan (RU)');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerDepartemenPerJabatanReportService::class);
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

        $this->app->instance(KaryawanPerDepartemenPerJabatanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/karyawan-per-departemen-per-jabatan/pdf',
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

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Per Departemen Per Jabatan (RU)');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KaryawanPerDepartemenPerJabatanReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KaryawanPerDepartemenPerJabatanReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/karyawan-per-departemen-per-jabatan/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_karyawan_per_departemen_per_jabatan_parser_groups_non_special_active_rows_and_builds_summaries(): void
    {
        $reportData = app(KaryawanPerDepartemenPerJabatanReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'Nama',
            'L/P',
            'Jabatan',
            'Tipe',
            'Level',
            'Pendidikan Terakhir',
            'Tanggal Masuk',
            'Kelompok Kerja',
        ], $reportData['headers']);
        $this->assertSame(4, $reportData['total_rows']);

        $this->assertSame('Departemen : Finance & Accounting', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Evi Seroja Tampubolon', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertSame('Ka. Div. Accounting RU', $reportData['grouped_rows'][0]['rows'][0]['Jabatan']);
        $this->assertSame('Ferra Novita', $reportData['grouped_rows'][0]['rows'][1]['Nama']);
        $this->assertSame('04-Agt-17', $reportData['grouped_rows'][0]['rows'][1]['Tanggal Masuk']);
        $this->assertSame(2, $reportData['grouped_rows'][0]['summary']['subtotal']);

        $financeSummary = $reportData['grouped_rows'][0]['summary'];
        $this->assertSame(0, $financeSummary['gender']['L']['count']);
        $this->assertSame(2, $financeSummary['gender']['P']['count']);
        $this->assertSame(2, $financeSummary['type']['ST']['count']);
        $this->assertSame(2, $financeSummary['education']['S1']['count']);
        $this->assertSame(1, $financeSummary['level']['Level 2']['count']);
        $this->assertSame(1, $financeSummary['level']['Level 4']['count']);

        $this->assertSame('Departemen : Sawmill', $reportData['grouped_rows'][1]['label']);
        $this->assertSame('Ade Yulinda Sari', $reportData['grouped_rows'][1]['rows'][0]['Nama']);
        $this->assertSame('Adek Arianda', $reportData['grouped_rows'][1]['rows'][1]['Nama']);
        $this->assertSame(2, $reportData['grouped_rows'][1]['summary']['subtotal']);
        $this->assertSame(4, $reportData['grand_summary']['subtotal']);
        $this->assertSame(1, $reportData['grand_summary']['type']['BR']['count']);
        $this->assertSame(1, $reportData['grand_summary']['type']['KK']['count']);
        $this->assertSame(2, $reportData['grand_summary']['type']['ST']['count']);
        $this->assertSame(1, $reportData['grand_summary']['education']['SMK']['count']);
    }

    public function test_karyawan_per_departemen_per_jabatan_pdf_renders_expected_headers_groups_and_summary(): void
    {
        $reportData = app(KaryawanPerDepartemenPerJabatanReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.ru.hrm.karyawan_per_departemen_per_jabatan.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Karyawan Per Departemen Per Jabatan (RU)', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('L/P', $html);
        $this->assertStringContainsString('Jabatan', $html);
        $this->assertStringContainsString('Tipe', $html);
        $this->assertStringContainsString('Level', $html);
        $this->assertStringContainsString('Pendidikan', $html);
        $this->assertStringContainsString('Tanggal', $html);
        $this->assertStringContainsString('Kelompok', $html);
        $this->assertStringContainsString('Departemen : Finance &amp; Accounting', $html);
        $this->assertStringContainsString('Sub Total', $html);
        $this->assertStringContainsString('Akumulasi L/P', $html);
        $this->assertStringContainsString('Akumulasi Tipe', $html);
        $this->assertStringContainsString('Akumulasi Pendidikan Terakhir', $html);
        $this->assertStringContainsString('Akumulasi Level', $html);
        $this->assertStringContainsString('• Perempuan = 2 (100%)', $html);
        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringNotContainsString('Shinta Kartika', $html);
        $this->assertStringNotContainsString('Dedi Nonaktif', $html);
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
            'sub_report' => 'karyawan_per_departemen_per_jabatan',
            'label' => 'Laporan Karyawan Per Departemen Per Jabatan (RU)',
            'title' => 'Laporan Karyawan Per Departemen Per Jabatan (RU)',
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'Nama',
                'L/P',
                'Jabatan',
                'Tipe',
                'Level',
                'Pendidikan Terakhir',
                'Tanggal Masuk',
                'Kelompok Kerja',
            ],
            'rows' => [[
                'Nama' => 'Ferra Novita',
                'L/P' => 'P',
                'Jabatan' => 'Staff Kasir RU',
                'Tipe' => 'ST',
                'Level' => '2',
                'Pendidikan Terakhir' => 'S1',
                'Tanggal Masuk' => '04-Agt-17',
                'Kelompok Kerja' => 'Staff Office II (08.30)',
            ]],
            'grouped_rows' => [[
                'label' => 'Departemen : Finance & Accounting',
                'rows' => [[
                    'Nama' => 'Ferra Novita',
                    'L/P' => 'P',
                    'Jabatan' => 'Staff Kasir RU',
                    'Tipe' => 'ST',
                    'Level' => '2',
                    'Pendidikan Terakhir' => 'S1',
                    'Tanggal Masuk' => '04-Agt-17',
                    'Kelompok Kerja' => 'Staff Office II (08.30)',
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
        <Sex>Female</Sex>
        <IdentityNo>1275045705760001</IdentityNo>
        <Job_x0020_Title>Ka. Div. Accounting RU</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>4</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>S1</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2018-03-13T00:00:00+07:00</Join_x0020_Date>
        <Workgroup>Staff Office II (08.30)</Workgroup>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132201</Employee_x0020_Code>
        <Full_x0020_Name>Ferra Novita</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Staff Kasir RU</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>S1</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2017-08-04T00:00:00+07:00</Join_x0020_Date>
        <Workgroup>Staff Office II (08.30)</Workgroup>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 3</Employee_x0020_Code>
        <Full_x0020_Name>Shinta Kartika</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Ka. Dept. Finance &amp; Accounting RU</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>4</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>S1</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>1997-02-11T00:00:00+07:00</Join_x0020_Date>
        <Workgroup>Staff Office II (08.30)</Workgroup>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132101</Employee_x0020_Code>
        <Full_x0020_Name>Adek Arianda</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>SMA</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2026-05-20T00:00:00+07:00</Join_x0020_Date>
        <Workgroup>Borongan Sawmill</Workgroup>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131465</Employee_x0020_Code>
        <Full_x0020_Name>Ade Yulinda Sari</Full_x0020_Name>
        <Sex>Female</Sex>
        <IdentityNo>1275045705760001</IdentityNo>
        <Job_x0020_Title>Kru Table Saw</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Last_x0020_Academic_x0020_Level>SMK</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2021-08-24T00:00:00+07:00</Join_x0020_Date>
        <Workgroup>Kary. Kontrak Shift Normal</Workgroup>
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
        <Last_x0020_Academic_x0020_Level>SMP</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2022-01-01T00:00:00+07:00</Join_x0020_Date>
        <Workgroup>Kary. Kontrak Shift Normal</Workgroup>
        <Department_x0020_Name>Stock</Department_x0020_Name>
        <Active>Terminated</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
