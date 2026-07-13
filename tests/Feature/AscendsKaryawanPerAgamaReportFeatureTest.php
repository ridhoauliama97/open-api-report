<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KaryawanPerAgamaReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKaryawanPerAgamaReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerAgamaReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_agama.pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerAgamaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/karyawan-per-agama/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Agama');
    }

    public function test_ascend_test_upload_form_can_preview_karyawan_per_agama_pdf(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerAgamaReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_agama.pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Per Agama'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerAgamaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_type' => 'karyawan_per_agama',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Agama');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerAgamaReportService::class);
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

        $this->app->instance(KaryawanPerAgamaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/karyawan-per-agama/pdf',
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

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Agama');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KaryawanPerAgamaReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KaryawanPerAgamaReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/karyawan-per-agama/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_karyawan_per_agama_parser_groups_non_special_active_rows_and_builds_summaries(): void
    {
        $reportData = app(KaryawanPerAgamaReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'Nama',
            'L/P',
            'Jabatan',
            'Umur',
            'THR',
        ], $reportData['headers']);
        $this->assertSame(4, $reportData['total_rows']);

        $this->assertSame('Agama : Buddha', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Ganda P', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertSame('45 Tahun', $reportData['grouped_rows'][0]['rows'][0]['Umur']);
        $this->assertSame('THR IMLEK', $reportData['grouped_rows'][0]['rows'][0]['THR']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['subtotal']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['gender']['L']['count']);

        $this->assertSame('Agama : Islam', $reportData['grouped_rows'][1]['label']);
        $this->assertSame(2, $reportData['grouped_rows'][1]['summary']['subtotal']);
        $this->assertSame(1, $reportData['grouped_rows'][1]['summary']['gender']['L']['count']);
        $this->assertSame(1, $reportData['grouped_rows'][1]['summary']['gender']['P']['count']);

        $this->assertSame('Agama : Kristen', $reportData['grouped_rows'][2]['label']);
        $this->assertSame(1, $reportData['grouped_rows'][2]['summary']['subtotal']);
        $this->assertSame(4, $reportData['grand_summary']['subtotal']);
        $this->assertSame(1, $reportData['grand_summary']['religion']['Buddha']['count']);
        $this->assertSame(2, $reportData['grand_summary']['religion']['Islam']['count']);
        $this->assertSame(1, $reportData['grand_summary']['religion']['Kristen']['count']);
    }

    public function test_karyawan_per_agama_pdf_renders_expected_headers_groups_and_summary(): void
    {
        $reportData = app(KaryawanPerAgamaReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.ru.hrm.karyawan_per_agama.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Karyawan Per Agama', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('L/P', $html);
        $this->assertStringContainsString('Jabatan', $html);
        $this->assertStringContainsString('Umur', $html);
        $this->assertStringContainsString('THR', $html);
        $this->assertStringContainsString('Agama : Buddha', $html);
        $this->assertStringContainsString('Agama : Islam', $html);
        $this->assertStringContainsString('Sub Total', $html);
        $this->assertStringContainsString('Akumulasi L/P', $html);
        $this->assertStringContainsString('• Laki-Laki = 1 (100%)', $html);
        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringContainsString('Rekap', $html);
        $this->assertStringContainsString('• Islam = 2 (50%)', $html);
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
            'sub_report' => 'karyawan_per_agama',
            'label' => 'Laporan Karyawan Per Agama',
            'title' => 'Laporan Karyawan Per Agama',
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'Nama',
                'L/P',
                'Jabatan',
                'Umur',
                'THR',
            ],
            'rows' => [
                [
                    'Nama' => 'Ganda P',
                    'L/P' => 'L',
                    'Jabatan' => 'Asisten Direktur Korporat Sales',
                    'Umur' => '45 Tahun',
                    'THR' => 'THR IMLEK',
                    'Agama' => 'Buddha',
                ]
            ],
            'grouped_rows' => [
                [
                    'label' => 'Agama : Buddha',
                    'rows' => [
                        [
                            'Nama' => 'Ganda P',
                            'L/P' => 'L',
                            'Jabatan' => 'Asisten Direktur Korporat Sales',
                            'Umur' => '45 Tahun',
                            'THR' => 'THR IMLEK',
                            'Agama' => 'Buddha',
                        ]
                    ],
                    'summary' => ['subtotal' => 1],
                ]
            ],
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
        <Full_x0020_Name>Ganda P</Full_x0020_Name>
        <Sex>Male</Sex>
        <IdentityNo>1275041705760001</IdentityNo>
        <Job_x0020_Title>Asisten Direktur Korporat Sales</Job_x0020_Title>
        <Age>45</Age>
        <THR>THR IMLEK</THR>
        <Religion>Budha</Religion>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132201</Employee_x0020_Code>
        <Full_x0020_Name>Ade Yulinda Sari</Full_x0020_Name>
        <Sex>Female</Sex>
        <IdentityNo>1275045705760001</IdentityNo>
        <Job_x0020_Title>Kru Table Saw</Job_x0020_Title>
        <Age>18</Age>
        <THR>THR IDUL FITRI</THR>
        <Religion>Islam</Religion>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132202</Employee_x0020_Code>
        <Full_x0020_Name>Adek Arianda</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Age>24</Age>
        <THR>THR IDUL FITRI</THR>
        <Religion>Islam</Religion>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132203</Employee_x0020_Code>
        <Full_x0020_Name>Adi Putra Simbolon</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Mesin SLP</Job_x0020_Title>
        <Age>30 Tahun</Age>
        <THR>THR NATAL</THR>
        <Religion>Kristen</Religion>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 3</Employee_x0020_Code>
        <Full_x0020_Name>Shinta Kartika</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Ka. Dept. Finance &amp; Accounting RU</Job_x0020_Title>
        <Age>49</Age>
        <THR>THR IDUL FITRI</THR>
        <Religion>Islam</Religion>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132099</Employee_x0020_Code>
        <Full_x0020_Name>Dedi Nonaktif</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Packing</Job_x0020_Title>
        <Age>25</Age>
        <THR>THR IDUL FITRI</THR>
        <Religion>Islam</Religion>
        <Active>Terminated</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
