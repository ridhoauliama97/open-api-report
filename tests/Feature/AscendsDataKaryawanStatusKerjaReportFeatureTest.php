<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\DataKaryawanStatusKerjaReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsDataKaryawanStatusKerjaReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(DataKaryawanStatusKerjaReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.data_karyawan_status_kerja.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                    && ! array_key_exists('pdf_orientation', $data)
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DataKaryawanStatusKerjaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/data-karyawan-status-kerja/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Data Karyawan (RU) - Status Kerja');
    }

    public function test_ascend_test_upload_form_can_preview_data_karyawan_status_kerja_pdf(): void
    {
        $xml = $this->employeeListXml();
        $title = $this->reportTitle();

        $service = Mockery::mock(DataKaryawanStatusKerjaReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.data_karyawan_status_kerja.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === $title
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DataKaryawanStatusKerjaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_type' => 'data_karyawan_status_kerja',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Data Karyawan (RU) - Status Kerja');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(DataKaryawanStatusKerjaReportService::class);
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

        $this->app->instance(DataKaryawanStatusKerjaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/data-karyawan-status-kerja/pdf',
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

        $this->assertPdfDisposition($response, 'inline', 'Laporan Data Karyawan (RU) - Status Kerja');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(DataKaryawanStatusKerjaReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(DataKaryawanStatusKerjaReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/data-karyawan-status-kerja/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_data_karyawan_status_kerja_parser_shapes_rows_and_filters_invalid_hk(): void
    {
        $reportData = app(DataKaryawanStatusKerjaReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'NIK',
            'Nama',
            'Tempat',
            'Tgl Lahir',
            'Umur',
            'Pendidikan',
            'Jabatan',
            'HK',
        ], $reportData['headers']);
        $this->assertSame(4, $reportData['total_rows']);

        $this->assertSame('Adek Arianda', $reportData['rows'][0]['Nama']);
        $this->assertSame('BORONGAN', $reportData['rows'][0]['HK']);
        $this->assertSame('04-Mar-89', $reportData['rows'][0]['Tgl Lahir']);
        $this->assertSame('SMA Swasta', $reportData['rows'][0]['Pendidikan']);
        $this->assertSame('Bela Kontrak', $reportData['rows'][1]['Nama']);
        $this->assertSame('KARYAWAN KONTRAK', $reportData['rows'][1]['HK']);
        $this->assertSame('Doni Tetap', $reportData['rows'][2]['Nama']);
        $this->assertSame('KARYAWAN TETAP', $reportData['rows'][2]['HK']);
        $this->assertSame('Candra Staff', $reportData['rows'][3]['Nama']);
        $this->assertSame('STAFF', $reportData['rows'][3]['HK']);
    }

    public function test_data_karyawan_status_kerja_pdf_renders_expected_headers(): void
    {
        $reportData = app(DataKaryawanStatusKerjaReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.ru.hrm.data_karyawan_status_kerja.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Data Karyawan (RU)<br />', $html);
        $this->assertStringContainsString('Staff, Karyawan Tetap &amp; Karyawan Kontrak<br />', $html);
        $this->assertStringContainsString('Berdasarkan Status Kerja', $html);
        $this->assertStringContainsString('NIK', $html);
        $this->assertStringContainsString('Tanggal Lahir', $html);
        $this->assertStringContainsString('Pendidikan', $html);
        $this->assertStringContainsString('Dicetak oleh:  pada', $html);
        $this->assertStringNotContainsString('Dicetak oleh: sistem pada', $html);
        $this->assertStringContainsString('KARYAWAN KONTRAK', $html);
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
            'sub_report' => 'data_karyawan_status_kerja',
            'label' => $this->reportTitle(),
            'title' => $this->reportTitle(),
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'NIK',
                'Nama',
                'Tempat',
                'Tgl Lahir',
                'Umur',
                'Pendidikan',
                'Jabatan',
                'HK',
            ],
            'rows' => [[
                'NIK' => '132101',
                'Nama' => 'Bela Kontrak',
                'Tempat' => 'Medan',
                'Tgl Lahir' => '06-May-96',
                'Umur' => '33',
                'Pendidikan' => 'SMA',
                'Jabatan' => 'Kru Cross Cut Awal',
                'HK' => 'KARYAWAN KONTRAK',
            ]],
            'total_rows' => 1,
        ];
    }

    private function employeeListXml(string $recordTag = 'Employees'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>132101</Employee_x0020_Code>
        <Full_x0020_Name>Adek Arianda</Full_x0020_Name>
        <Birth_x0020_Place>Sei Renggas</Birth_x0020_Place>
        <Birth_x0020_Date>1989-03-04T00:00:00+07:00</Birth_x0020_Date>
        <Age>37</Age>
        <Last_x0020_Education_x0020_School_x0020_Name>SMA Swasta</Last_x0020_Education_x0020_School_x0020_Name>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Name>BORONGAN</Daily_x0020_Worker_x0020_Type_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Salary_x0020_Security_x0020_Code>BORONGAN</Salary_x0020_Security_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132096</Employee_x0020_Code>
        <Full_x0020_Name>Bela Kontrak</Full_x0020_Name>
        <Birth_x0020_Place>Medan</Birth_x0020_Place>
        <Birth_x0020_Date>1996-05-06T00:00:00+07:00</Birth_x0020_Date>
        <Age>33</Age>
        <Last_x0020_Academic_x0020_Level>SMA</Last_x0020_Academic_x0020_Level>
        <Job_x0020_Title>Kru Cross Cut Awal</Job_x0020_Title>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Salary_x0020_Security_x0020_Code>KL - KT</Salary_x0020_Security_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132097</Employee_x0020_Code>
        <Full_x0020_Name>Doni Tetap</Full_x0020_Name>
        <Birth_x0020_Place>Medan</Birth_x0020_Place>
        <Birth_x0020_Date>2001-01-01T00:00:00+07:00</Birth_x0020_Date>
        <Age>25</Age>
        <Job_x0020_Title>Supervisor</Job_x0020_Title>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132098</Employee_x0020_Code>
        <Full_x0020_Name>Candra Staff</Full_x0020_Name>
        <Birth_x0020_Place>Binjai</Birth_x0020_Place>
        <Birth_x0020_Date>2002-01-17T00:00:00+07:00</Birth_x0020_Date>
        <Age>24</Age>
        <Last_x0020_Education_x0020_School_x0020_Name>Universitas Sumatera Utara</Last_x0020_Education_x0020_School_x0020_Name>
        <Job_x0020_Title>Staff Inventory Control</Job_x0020_Title>
        <Level_x0020_Name>3</Level_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Name>STAFF</Daily_x0020_Worker_x0020_Type_x0020_Name>
        <Salary_x0020_Security_x0020_Code>STAFF</Salary_x0020_Security_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132099</Employee_x0020_Code>
        <Full_x0020_Name>Eka Level Satu</Full_x0020_Name>
        <Birth_x0020_Place>Medan</Birth_x0020_Place>
        <Birth_x0020_Date>2001-01-01T00:00:00+07:00</Birth_x0020_Date>
        <Age>25</Age>
        <Job_x0020_Title>Supervisor</Job_x0020_Title>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }

    private function reportTitle(): string
    {
        return "Laporan Data Karyawan (RU)\nStaff, Karyawan Tetap & Karyawan Kontrak\nBerdasarkan Status Kerja";
    }
}
