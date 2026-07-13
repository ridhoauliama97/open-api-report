<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\DaftarKaryawanBerdasarkanAbjadReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsDaftarKaryawanBerdasarkanAbjadReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(DaftarKaryawanBerdasarkanAbjadReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.daftar_karyawan_berdasarkan_abjad.pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                && !array_key_exists('pdf_orientation', $data)
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DaftarKaryawanBerdasarkanAbjadReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/daftar-karyawan-berdasarkan-abjad/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Daftar Karyawan (RU) - Berdasarkan Abjad');
    }

    public function test_ascend_test_upload_form_can_preview_daftar_karyawan_berdasarkan_abjad_pdf(): void
    {
        $xml = $this->employeeListXml();
        $title = $this->reportTitle();

        $service = Mockery::mock(DaftarKaryawanBerdasarkanAbjadReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.daftar_karyawan_berdasarkan_abjad.pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['title'] ?? null) === $title
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DaftarKaryawanBerdasarkanAbjadReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_type' => 'daftar_karyawan_berdasarkan_abjad',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Daftar Karyawan (RU) - Berdasarkan Abjad');
    }

    public function test_internal_ascend_api_can_render_uc_daftar_karyawan_berdasarkan_abjad_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(DaftarKaryawanBerdasarkanAbjadReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData($this->reportTitle('UC')));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.uc.hrm.daftar_karyawan_berdasarkan_abjad.pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DaftarKaryawanBerdasarkanAbjadReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/uc/hrm/daftar-karyawan-berdasarkan-abjad/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Daftar Karyawan (UC) - Berdasarkan Abjad');
    }

    public function test_ascend_test_upload_form_can_preview_uc_daftar_karyawan_berdasarkan_abjad_pdf(): void
    {
        $xml = $this->employeeListXml();
        $title = $this->reportTitle('UC');

        $service = Mockery::mock(DaftarKaryawanBerdasarkanAbjadReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData($title));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.uc.hrm.daftar_karyawan_berdasarkan_abjad.pdf', Mockery::on(
                static fn(array $data): bool => ($data['reportData']['title'] ?? null) === $title
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DaftarKaryawanBerdasarkanAbjadReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'company' => 'UC',
            'report_module' => 'hrm_analysis_reports',
            'report_type' => 'uc_daftar_karyawan_berdasarkan_abjad',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Daftar Karyawan (UC) - Berdasarkan Abjad');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(DaftarKaryawanBerdasarkanAbjadReportService::class);
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

        $this->app->instance(DaftarKaryawanBerdasarkanAbjadReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/daftar-karyawan-berdasarkan-abjad/pdf',
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

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Daftar Karyawan (RU) - Berdasarkan Abjad');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(DaftarKaryawanBerdasarkanAbjadReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(DaftarKaryawanBerdasarkanAbjadReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/daftar-karyawan-berdasarkan-abjad/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_daftar_karyawan_berdasarkan_abjad_parser_groups_rows_and_filters_staff(): void
    {
        $reportData = app(DaftarKaryawanBerdasarkanAbjadReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'Nama',
            'No ID',
            'Posisi',
            'Paraf',
        ], $reportData['headers']);
        $this->assertSame(4, $reportData['total_rows']);

        $this->assertSame('A - D', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Ade Yulinda Sari', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertSame('131465', $reportData['grouped_rows'][0]['rows'][0]['No ID']);
        $this->assertSame('Adek Arianda', $reportData['grouped_rows'][0]['rows'][1]['Nama']);
        $this->assertSame('M - P', $reportData['grouped_rows'][1]['label']);
        $this->assertSame('Muhammad Taufik', $reportData['grouped_rows'][1]['rows'][0]['Nama']);
        $this->assertSame('U - Z', $reportData['grouped_rows'][2]['label']);
        $this->assertSame('Yasman', $reportData['grouped_rows'][2]['rows'][0]['Nama']);
    }

    public function test_daftar_karyawan_berdasarkan_abjad_pdf_renders_expected_headers_and_groups(): void
    {
        $reportData = app(DaftarKaryawanBerdasarkanAbjadReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.ru.hrm.daftar_karyawan_berdasarkan_abjad.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Daftar Karyawan (RU)<br />', $html);
        $this->assertStringContainsString('Berdasarkan Abjad', $html);
        $this->assertStringContainsString('No ID', $html);
        $this->assertStringContainsString('Posisi', $html);
        $this->assertStringContainsString('Paraf', $html);
        $this->assertStringContainsString('A - D', $html);
        $this->assertStringContainsString('M - P', $html);
        $this->assertStringContainsString('U - Z', $html);
        $this->assertStringNotContainsString('Candra Staff', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(?string $title = null): array
    {
        $title ??= $this->reportTitle();

        return [
            'printed_at' => '20 Mei 2026 10:00',
            'company' => str_contains($title, '(UC)') ? 'UC' : 'RU',
            'module' => 'hrm',
            'sub_report' => 'daftar_karyawan_berdasarkan_abjad',
            'label' => $title,
            'title' => $title,
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'Nama',
                'No ID',
                'Posisi',
                'Paraf',
            ],
            'rows' => [
                [
                    'Nama' => 'Ade Yulinda Sari',
                    'No ID' => '131465',
                    'Posisi' => 'Kru Table Saw',
                    'Paraf' => '',
                ]
            ],
            'grouped_rows' => [
                [
                    'label' => 'A - D',
                    'rows' => [
                        [
                            'Nama' => 'Ade Yulinda Sari',
                            'No ID' => '131465',
                            'Posisi' => 'Kru Table Saw',
                            'Paraf' => '',
                        ]
                    ],
                ]
            ],
            'total_rows' => 1,
        ];
    }

    private function employeeListXml(string $recordTag = 'Employees'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>131465</Employee_x0020_Code>
        <Full_x0020_Name>Ade Yulinda Sari</Full_x0020_Name>
        <Job_x0020_Title>Kru Table Saw</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132101</Employee_x0020_Code>
        <Full_x0020_Name>Adek Arianda</Full_x0020_Name>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131348</Employee_x0020_Code>
        <Full_x0020_Name>Muhammad Taufik</Full_x0020_Name>
        <Job_x0020_Title>Operator Rotary</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131989</Employee_x0020_Code>
        <Full_x0020_Name>Yasman</Full_x0020_Name>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132097</Employee_x0020_Code>
        <Full_x0020_Name>Candra Staff</Full_x0020_Name>
        <Job_x0020_Title>Staff Inventory Control</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132099</Employee_x0020_Code>
        <Full_x0020_Name>Dedi Nonaktif</Full_x0020_Name>
        <Job_x0020_Title>Kru Packing</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Active>Terminated</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }

    private function reportTitle(string $company = 'RU'): string
    {
        return "Laporan Daftar Karyawan ({$company})\nBerdasarkan Abjad";
    }
}
