<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KaryawanPerLevelReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKaryawanPerLevelReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerLevelReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_level.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerLevelReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/karyawan-per-level/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Level');
    }

    public function test_ascend_test_upload_form_can_preview_karyawan_per_level_pdf(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerLevelReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_level.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Per Level'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerLevelReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_type' => 'karyawan_per_level',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Level');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerLevelReportService::class);
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

        $this->app->instance(KaryawanPerLevelReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/karyawan-per-level/pdf',
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

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Level');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KaryawanPerLevelReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KaryawanPerLevelReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/karyawan-per-level/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_karyawan_per_level_parser_groups_non_special_active_rows_and_builds_summaries(): void
    {
        $reportData = app(KaryawanPerLevelReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'Nama',
            'L/P',
            'Jabatan',
            'Status',
            'Tanggal Masuk',
        ], $reportData['headers']);
        $this->assertSame(5, $reportData['total_rows']);

        $this->assertSame('Level : 1', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Mulyadi', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertSame('16-Jun-07', $reportData['grouped_rows'][0]['rows'][0]['Tanggal Masuk']);
        $this->assertSame('Sulasmi', $reportData['grouped_rows'][0]['rows'][1]['Nama']);
        $this->assertSame(2, $reportData['grouped_rows'][0]['summary']['subtotal']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['gender']['L']['count']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['gender']['P']['count']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['status']['KT']['count']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['status']['KK']['count']);

        $this->assertSame('Level : 2', $reportData['grouped_rows'][1]['label']);
        $this->assertSame(2, $reportData['grouped_rows'][1]['summary']['subtotal']);

        $this->assertSame('Level : 6', $reportData['grouped_rows'][2]['label']);
        $this->assertSame(1, $reportData['grouped_rows'][2]['summary']['subtotal']);
        $this->assertSame(5, $reportData['grand_summary']['subtotal']);
        $this->assertSame(2, $reportData['grand_summary']['level']['Level 1']['count']);
        $this->assertSame(2, $reportData['grand_summary']['level']['Level 2']['count']);
        $this->assertSame(0, $reportData['grand_summary']['level']['Level 5']['count']);
        $this->assertSame(1, $reportData['grand_summary']['level']['Level 6']['count']);
        $this->assertSame(1, $reportData['grand_summary']['status']['BR']['count']);
        $this->assertSame(1, $reportData['grand_summary']['status']['KK']['count']);
        $this->assertSame(2, $reportData['grand_summary']['status']['KT']['count']);
        $this->assertSame(1, $reportData['grand_summary']['status']['ST']['count']);
    }

    public function test_karyawan_per_level_pdf_renders_expected_headers_groups_and_summary(): void
    {
        $reportData = app(KaryawanPerLevelReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.ru.hrm.karyawan_per_level.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Karyawan Per Level', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('L/P', $html);
        $this->assertStringContainsString('Jabatan', $html);
        $this->assertStringContainsString('Status', $html);
        $this->assertStringContainsString('Tanggal<br>Masuk', $html);
        $this->assertStringContainsString('Level : 1', $html);
        $this->assertStringContainsString('Level : 2', $html);
        $this->assertStringContainsString('Sub Total', $html);
        $this->assertStringContainsString('Akumulasi L/P', $html);
        $this->assertStringContainsString('Akumulasi Status', $html);
        $this->assertStringContainsString('• Laki - Laki = 1 (50%)', $html);
        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringContainsString('Akumulasi Level', $html);
        $this->assertStringContainsString('• Level 1 = 2 (40%)', $html);
        $this->assertStringContainsString('• Level 5 = 0 (0%)', $html);
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
            'sub_report' => 'karyawan_per_level',
            'label' => 'Laporan Karyawan Per Level',
            'title' => 'Laporan Karyawan Per Level',
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'Nama',
                'L/P',
                'Jabatan',
                'Status',
                'Tanggal Masuk',
            ],
            'rows' => [
                [
                    'Nama' => 'Mulyadi',
                    'L/P' => 'L',
                    'Jabatan' => 'Kru Sanding',
                    'Status' => 'KT',
                    'Tanggal Masuk' => '16-Jun-07',
                    'Level' => '1',
                ],
            ],
            'grouped_rows' => [
                [
                    'label' => 'Level : 1',
                    'rows' => [
                        [
                            'Nama' => 'Mulyadi',
                            'L/P' => 'L',
                            'Jabatan' => 'Kru Sanding',
                            'Status' => 'KT',
                            'Tanggal Masuk' => '16-Jun-07',
                            'Level' => '1',
                        ],
                    ],
                    'summary' => ['subtotal' => 1],
                ],
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
        <Employee_x0020_Code>131896</Employee_x0020_Code>
        <Full_x0020_Name>Mulyadi</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Sanding</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Join_x0020_Date>2007-06-16T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130211</Employee_x0020_Code>
        <Full_x0020_Name>Sulasmi</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Kru Moulding 2</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Join_x0020_Date>2010-09-23T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130433</Employee_x0020_Code>
        <Full_x0020_Name>Juwanita</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Operator Double Planner</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Join_x0020_Date>2014-05-16T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130598</Employee_x0020_Code>
        <Full_x0020_Name>Luki Syahputra</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Operator Finger Joint</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Join_x0020_Date>2014-09-17T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130385</Employee_x0020_Code>
        <Full_x0020_Name>Ganda P</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Asisten Direktur Korporat Sales</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Join_x0020_Date>2005-01-01T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>6</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 3</Employee_x0020_Code>
        <Full_x0020_Name>Shinta Kartika</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Ka. Dept. Finance &amp; Accounting RU</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Join_x0020_Date>2014-01-01T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>6</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>132099</Employee_x0020_Code>
        <Full_x0020_Name>Dedi Nonaktif</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Packing</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Join_x0020_Date>2025-01-01T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Active>Terminated</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
