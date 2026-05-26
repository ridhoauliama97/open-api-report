<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\DaftarKaryawanReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsDaftarKaryawanReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
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
            ->with('ascends.ru.hrm.daftar_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DaftarKaryawanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/daftar-karyawan/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Daftar Karyawan (RU)');
    }

    public function test_ascend_test_upload_form_can_preview_daftar_karyawan_pdf(): void
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
            ->with('ascends.ru.hrm.daftar_karyawan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === 'Laporan Daftar Karyawan (RU)'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DaftarKaryawanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_type' => 'daftar_karyawan',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Daftar Karyawan (RU)');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(DaftarKaryawanReportService::class);
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

        $this->app->instance(DaftarKaryawanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/daftar-karyawan/pdf',
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

        $this->assertPdfDisposition($response, 'inline', 'Laporan Daftar Karyawan (RU)');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(DaftarKaryawanReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(DaftarKaryawanReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/daftar-karyawan/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_daftar_karyawan_parser_groups_active_rows_by_department_and_builds_summaries(): void
    {
        $reportData = app(DaftarKaryawanReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'Nama',
            'Jabatan',
            'Tp',
            'Level',
            'Tgn',
            'Perusahaan Sebelumnya',
            'LastEdu',
            'Tgl Masuk',
        ], $reportData['headers']);
        $this->assertSame(5, $reportData['total_rows']);

        $this->assertSame('Department : Finance & Accounting', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Bela Kontrak', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertSame('06-Mei-2026', $reportData['grouped_rows'][0]['rows'][0]['Tgl Masuk']);
        $this->assertSame('Department : Sawmill', $reportData['grouped_rows'][1]['label']);
        $this->assertSame(4, $reportData['grouped_rows'][1]['summary']['subtotal']);

        $this->assertSame(0, $reportData['grouped_rows'][0]['summary']['gender']['L']['count']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['gender']['P']['count']);

        $sawmillSummary = $reportData['grouped_rows'][1]['summary'];
        $this->assertSame(3, $sawmillSummary['gender']['L']['count']);
        $this->assertSame(1, $sawmillSummary['gender']['P']['count']);
        $this->assertSame(1, $sawmillSummary['status']['ST']['count']);
        $this->assertSame(1, $sawmillSummary['status']['KT']['count']);
        $this->assertSame(1, $sawmillSummary['status']['KK']['count']);
        $this->assertSame(33, $sawmillSummary['status']['ST']['percent']);
        $this->assertSame(0, $sawmillSummary['status']['BR']['count'] ?? 0);
        $this->assertSame(2, $sawmillSummary['education']['SMA/SMK']['count']);
        $this->assertSame(1, $sawmillSummary['education']['S1']['count']);
        $this->assertSame(2, $sawmillSummary['level']['Lvl 2']['count']);

        $this->assertSame(5, $reportData['grand_summary']['subtotal']);
        $this->assertSame(2, $reportData['grand_summary']['status']['KK']['count']);
    }

    public function test_daftar_karyawan_pdf_renders_expected_headers_groups_and_summary(): void
    {
        $reportData = app(DaftarKaryawanReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.ru.hrm.daftar_karyawan.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Daftar Karyawan (RU)', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('Jabatan', $html);
        $this->assertStringContainsString('Tipe', $html);
        $this->assertStringContainsString('Perusahaan<br>Sebelumnya', $html);
        $this->assertStringContainsString('Pendidikan<br>Terakhir', $html);
        $this->assertStringContainsString('Tanggal<br>Masuk', $html);
        $this->assertStringContainsString('Department : Finance &amp; Accounting', $html);
        $this->assertStringContainsString('Department : Sawmill', $html);
        $this->assertStringContainsString('Sub Total', $html);
        $this->assertStringContainsString('Akumulasi L/P', $html);
        $this->assertStringContainsString('Akumulasi Status', $html);
        $this->assertStringContainsString('Akumulasi Strata Pend.', $html);
        $this->assertStringContainsString('Akumulasi Level', $html);
        $this->assertStringContainsString('• ST = 1 (33%)', $html);
        $this->assertStringContainsString('SUMMARY', $html);
        $this->assertStringContainsString('Grand Total', $html);
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
            'sub_report' => 'daftar_karyawan',
            'label' => 'Laporan Daftar Karyawan (RU)',
            'title' => 'Laporan Daftar Karyawan (RU)',
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'Nama',
                'Jabatan',
                'Tp',
                'Level',
                'Tgn',
                'Perusahaan Sebelumnya',
                'LastEdu',
                'Tgl Masuk',
            ],
            'rows' => [[
                'Nama' => 'Bela Kontrak',
                'Jabatan' => 'Kru Cross Cut Awal',
                'Tp' => 'KK',
                'Level' => '2',
                'Tgn' => 'TK',
                'Perusahaan Sebelumnya' => '',
                'LastEdu' => 'SMA',
                'Tgl Masuk' => '06-Mei-2026',
            ]],
            'grouped_rows' => [[
                'label' => 'Department : Finance & Accounting',
                'rows' => [[
                    'Nama' => 'Bela Kontrak',
                    'Jabatan' => 'Kru Cross Cut Awal',
                    'Tp' => 'KK',
                    'Level' => '2',
                    'Tgn' => 'TK',
                    'Perusahaan Sebelumnya' => '',
                    'LastEdu' => 'SMA',
                    'Tgl Masuk' => '06-Mei-2026',
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
        <Full_x0020_Name>Bela Kontrak</Full_x0020_Name>
        <Job_x0020_Title>Kru Cross Cut Awal</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Marital_x0020_Status>TK</Marital_x0020_Status>
        <Employee_x0020_Remarks></Employee_x0020_Remarks>
        <Last_x0020_Academic_x0020_Level>SMA</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2026-05-06T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Sex>Male</Sex>
        <IdentityNo>1275045705760001</IdentityNo>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Full_x0020_Name>Adek Arianda</Full_x0020_Name>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Marital_x0020_Status>KK</Marital_x0020_Status>
        <Employee_x0020_Remarks>PT Lama</Employee_x0020_Remarks>
        <Last_x0020_Academic_x0020_Level>SMK</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2017-08-04T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Sex>Male</Sex>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Full_x0020_Name>Candra Staff</Full_x0020_Name>
        <Job_x0020_Title>Staff Inventory Control</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>4</Level_x0020_Name>
        <Marital_x0020_Status>TK</Marital_x0020_Status>
        <Last_x0020_Academic_x0020_Level>S1</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2023-01-17T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Sex>Female</Sex>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Full_x0020_Name>Doni Tetap</Full_x0020_Name>
        <Job_x0020_Title>Operator Rotary</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Marital_x0020_Status>K</Marital_x0020_Status>
        <Last_x0020_Academic_x0020_Level>SMP</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2020-01-01T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Sex>Male</Sex>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Full_x0020_Name>Eka Kontrak</Full_x0020_Name>
        <Job_x0020_Title>Kru Packing</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Marital_x0020_Status>TK</Marital_x0020_Status>
        <Last_x0020_Academic_x0020_Level>SMA</Last_x0020_Academic_x0020_Level>
        <Join_x0020_Date>2021-02-02T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Sex>Male</Sex>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Full_x0020_Name>Dedi Nonaktif</Full_x0020_Name>
        <Job_x0020_Title>Kru Packing</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Join_x0020_Date>2022-01-01T00:00:00+07:00</Join_x0020_Date>
        <Department_x0020_Name>Stock</Department_x0020_Name>
        <Sex>Male</Sex>
        <Active>Terminated</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
