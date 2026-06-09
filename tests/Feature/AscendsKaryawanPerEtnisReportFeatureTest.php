<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KaryawanPerEtnisReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKaryawanPerEtnisReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerEtnisReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_etnis.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerEtnisReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/karyawan-per-etnis/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Per Etnis');
    }

    public function test_ascend_test_upload_form_can_preview_karyawan_per_etnis_pdf(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerEtnisReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_etnis.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Per Etnis'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerEtnisReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_type' => 'karyawan_per_etnis',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Per Etnis');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerEtnisReportService::class);
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

        $this->app->instance(KaryawanPerEtnisReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/karyawan-per-etnis/pdf',
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

        $this->assertPdfDisposition($response, 'inline', 'Laporan Karyawan Per Etnis');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KaryawanPerEtnisReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KaryawanPerEtnisReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/karyawan-per-etnis/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_karyawan_per_etnis_parser_groups_non_special_active_rows_and_builds_summaries(): void
    {
        $reportData = app(KaryawanPerEtnisReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'NIK',
            'Nama',
            'Jabatan',
            'Umur',
            'Agama',
        ], $reportData['headers']);
        $this->assertSame(5, $reportData['total_rows']);

        $this->assertSame('Etnis : ', $reportData['grouped_rows'][0]['label']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['subtotal']);

        $this->assertSame('Etnis : Batak', $reportData['grouped_rows'][1]['label']);
        $this->assertSame('Ade Yulinda Sari', $reportData['grouped_rows'][1]['rows'][0]['Nama']);
        $this->assertSame('132201', $reportData['grouped_rows'][1]['rows'][0]['NIK']);
        $this->assertSame('18 Tahun', $reportData['grouped_rows'][1]['rows'][0]['Umur']);
        $this->assertSame(2, $reportData['grouped_rows'][1]['summary']['subtotal']);
        $this->assertSame(1, $reportData['grouped_rows'][1]['summary']['gender']['L']['count']);
        $this->assertSame(1, $reportData['grouped_rows'][1]['summary']['gender']['P']['count']);

        $this->assertSame('Etnis : Jawa', $reportData['grouped_rows'][2]['label']);
        $this->assertSame(1, $reportData['grouped_rows'][2]['summary']['subtotal']);

        $this->assertSame('Etnis : Nias', $reportData['grouped_rows'][3]['label']);
        $this->assertSame(1, $reportData['grouped_rows'][3]['summary']['subtotal']);
        $this->assertSame(5, $reportData['grand_summary']['subtotal']);
        $this->assertSame(1, $reportData['grand_summary']['ethnicity']['Tanpa Etnis']['count']);
        $this->assertSame(2, $reportData['grand_summary']['ethnicity']['Batak']['count']);
        $this->assertSame(1, $reportData['grand_summary']['ethnicity']['Jawa']['count']);
        $this->assertSame(1, $reportData['grand_summary']['ethnicity']['Nias']['count']);
    }

    public function test_karyawan_per_etnis_pdf_renders_expected_headers_groups_and_summary(): void
    {
        $reportData = app(KaryawanPerEtnisReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.ru.hrm.karyawan_per_etnis.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Karyawan Per Etnis', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('NIK', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('Jabatan', $html);
        $this->assertStringContainsString('Umur', $html);
        $this->assertStringContainsString('Agama', $html);
        $this->assertStringContainsString('Etnis : Batak', $html);
        $this->assertStringContainsString('Etnis : Jawa', $html);
        $this->assertStringContainsString('Sub Total', $html);
        $this->assertStringContainsString('Akumulasi L/P', $html);
        $this->assertStringContainsString('• Laki-Laki = 1 (50%)', $html);
        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringContainsString('Akumulasi Etnis', $html);
        $this->assertStringContainsString('• Batak = 2 (40%)', $html);
        $this->assertStringContainsString('• Tanpa Etnis = 1 (20%)', $html);
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
            'sub_report' => 'karyawan_per_etnis',
            'label' => 'Laporan Karyawan Per Etnis',
            'title' => 'Laporan Karyawan Per Etnis',
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'NIK',
                'Nama',
                'Jabatan',
                'Umur',
                'Agama',
            ],
            'rows' => [[
                'NIK' => '132200',
                'Nama' => 'Aferlius Gulo',
                'L/P' => 'L',
                'Jabatan' => 'Kru Grader Sawmill',
                'Umur' => '31 Tahun',
                'Agama' => 'Kristen',
                'Etnis' => 'Nias',
            ]],
            'grouped_rows' => [[
                'label' => 'Etnis : Nias',
                'rows' => [[
                    'NIK' => '132200',
                    'Nama' => 'Aferlius Gulo',
                    'L/P' => 'L',
                    'Jabatan' => 'Kru Grader Sawmill',
                    'Umur' => '31 Tahun',
                    'Agama' => 'Kristen',
                    'Etnis' => 'Nias',
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
        <Full_x0020_Name>Aferlius Gulo</Full_x0020_Name>
        <Sex>Male</Sex>
        <IdentityNo>1275041705760001</IdentityNo>
        <Job_x0020_Title>Kru Grader Sawmill</Job_x0020_Title>
        <Age>31</Age>
        <Religion>Kristen</Religion>
        <Race>Nias</Race>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132201</Employee_x0020_Code>
        <Full_x0020_Name>Ade Yulinda Sari</Full_x0020_Name>
        <Sex>Female</Sex>
        <IdentityNo>1275045705760001</IdentityNo>
        <Job_x0020_Title>Kru Table Saw</Job_x0020_Title>
        <Age>18</Age>
        <Religion>Islam</Religion>
        <Race>Batak</Race>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132202</Employee_x0020_Code>
        <Full_x0020_Name>Adek Arianda</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Age>24</Age>
        <Religion>Islam</Religion>
        <Race>Jawa</Race>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132203</Employee_x0020_Code>
        <Full_x0020_Name>Adi Putra Simbolon</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Mesin SLP</Job_x0020_Title>
        <Age>30 Tahun</Age>
        <Religion>Kristen</Religion>
        <Race>Batak</Race>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132204</Employee_x0020_Code>
        <Full_x0020_Name>Elwin Ramadani</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Finger Joint</Job_x0020_Title>
        <Age>24</Age>
        <Religion>Islam</Religion>
        <Race />
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 3</Employee_x0020_Code>
        <Full_x0020_Name>Shinta Kartika</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Ka. Dept. Finance &amp; Accounting RU</Job_x0020_Title>
        <Age>49</Age>
        <Religion>Islam</Religion>
        <Race>Jawa</Race>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132099</Employee_x0020_Code>
        <Full_x0020_Name>Dedi Nonaktif</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Packing</Job_x0020_Title>
        <Age>25</Age>
        <Religion>Islam</Religion>
        <Race>Jawa</Race>
        <Active>Terminated</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
