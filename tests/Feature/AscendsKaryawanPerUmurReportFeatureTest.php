<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KaryawanPerUmurReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKaryawanPerUmurReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerUmurReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_umur.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerUmurReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/karyawan-per-umur/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Umur');
    }

    public function test_ascend_test_upload_form_can_preview_karyawan_per_umur_pdf(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerUmurReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_umur.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Per Umur'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerUmurReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_type' => 'karyawan_per_umur',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Umur');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerUmurReportService::class);
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

        $this->app->instance(KaryawanPerUmurReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/karyawan-per-umur/pdf',
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

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Umur');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KaryawanPerUmurReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KaryawanPerUmurReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/karyawan-per-umur/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_karyawan_per_umur_parser_groups_non_special_active_rows_and_builds_summaries(): void
    {
        $reportData = app(KaryawanPerUmurReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'Nama',
            'Jabatan',
            'L/P',
            'Status',
            'Umur',
            'Masa Kerja',
            'Level',
        ], $reportData['headers']);
        $this->assertSame(6, $reportData['total_rows']);

        $this->assertSame('Umur : 17 - 20 Tahun', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Alfian', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertSame('17', $reportData['grouped_rows'][0]['rows'][0]['Umur']);
        $this->assertSame('0 Thn 5 Bln 10 Hari', $reportData['grouped_rows'][0]['rows'][0]['Masa Kerja']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['subtotal']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['gender']['L']['count']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['status']['KK']['count']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['summary']['level']['Level 1']['count']);

        $this->assertSame('Umur : 21 - 30 Tahun', $reportData['grouped_rows'][1]['label']);
        $this->assertSame(1, $reportData['grouped_rows'][1]['summary']['subtotal']);
        $this->assertSame('Umur : 31 - 40 Tahun', $reportData['grouped_rows'][2]['label']);
        $this->assertSame('Umur : 41 - 50 Tahun', $reportData['grouped_rows'][3]['label']);
        $this->assertSame('Umur : 51 - 60 Tahun', $reportData['grouped_rows'][4]['label']);
        $this->assertSame('Umur : 60 Tahun ++', $reportData['grouped_rows'][5]['label']);

        $this->assertSame(6, $reportData['grand_summary']['subtotal']);
        $this->assertSame(1, $reportData['grand_summary']['age']['17_20']['count']);
        $this->assertSame(1, $reportData['grand_summary']['age']['21_30']['count']);
        $this->assertSame(1, $reportData['grand_summary']['age']['31_40']['count']);
        $this->assertSame(1, $reportData['grand_summary']['age']['41_50']['count']);
        $this->assertSame(1, $reportData['grand_summary']['age']['51_60']['count']);
        $this->assertSame(1, $reportData['grand_summary']['age']['60_plus']['count']);
        $this->assertSame(1, $reportData['grand_summary']['status']['BR']['count']);
        $this->assertSame(2, $reportData['grand_summary']['status']['KK']['count']);
        $this->assertSame(2, $reportData['grand_summary']['status']['KT']['count']);
        $this->assertSame(1, $reportData['grand_summary']['status']['ST']['count']);
        $this->assertSame(2, $reportData['grand_summary']['level']['Level 1']['count']);
        $this->assertSame(1, $reportData['grand_summary']['level']['Level 2']['count']);
        $this->assertSame(0, $reportData['grand_summary']['level']['Level 5']['count']);
        $this->assertSame(1, $reportData['grand_summary']['level']['Level 6']['count']);
    }

    public function test_karyawan_per_umur_pdf_renders_expected_headers_groups_and_summary(): void
    {
        $reportData = app(KaryawanPerUmurReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.ru.hrm.karyawan_per_umur.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Karyawan Per Umur', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('Jabatan', $html);
        $this->assertStringContainsString('L/P', $html);
        $this->assertStringContainsString('Status', $html);
        $this->assertStringContainsString('Umur', $html);
        $this->assertStringContainsString('Masa Kerja', $html);
        $this->assertStringContainsString('Level', $html);
        $this->assertStringContainsString('Umur : 17 - 20 Tahun', $html);
        $this->assertStringContainsString('Umur : 60 Tahun ++', $html);
        $this->assertStringContainsString('Sub Total', $html);
        $this->assertStringContainsString('Akumulasi L/P', $html);
        $this->assertStringContainsString('Akumulasi Status', $html);
        $this->assertStringContainsString('Akumulasi Level', $html);
        $this->assertStringContainsString('Akumulasi Umur', $html);
        $this->assertStringContainsString('• 17 - 20 Tahun = 1 (17%)', $html);
        $this->assertStringContainsString('• Level 5 = 0 (0%)', $html);
        $this->assertStringNotContainsString('Special Person', $html);
        $this->assertStringNotContainsString('Non Aktif', $html);
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
            'sub_report' => 'karyawan_per_umur',
            'label' => 'Laporan Karyawan Per Umur',
            'title' => 'Laporan Karyawan Per Umur',
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'Nama',
                'Jabatan',
                'L/P',
                'Status',
                'Umur',
                'Masa Kerja',
                'Level',
            ],
            'rows' => [
                [
                    'Nama' => 'Alfian',
                    'Jabatan' => 'Kru Rotary',
                    'L/P' => 'L',
                    'Status' => 'KK',
                    'Umur' => '17',
                    'Masa Kerja' => '0 Thn 5 Bln 10 Hari',
                    'Level' => '1',
                ],
            ],
            'grouped_rows' => [
                [
                    'label' => 'Umur : 17 - 20 Tahun',
                    'rows' => [
                        [
                            'Nama' => 'Alfian',
                            'Jabatan' => 'Kru Rotary',
                            'L/P' => 'L',
                            'Status' => 'KK',
                            'Umur' => '17',
                            'Masa Kerja' => '0 Thn 5 Bln 10 Hari',
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
        <Employee_x0020_Code>131001</Employee_x0020_Code>
        <Full_x0020_Name>Alfian</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Rotary</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Age>17</Age>
        <Working_x0020_Years>0</Working_x0020_Years>
        <Working_x0020_Months>5</Working_x0020_Months>
        <Working_x0020_Days>10</Working_x0020_Days>
        <Join_x0020_Date>2025-12-16T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131002</Employee_x0020_Code>
        <Full_x0020_Name>Fariel</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Operator Cross Cut Akhir</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Age>21</Age>
        <Working_x0020_Years>1</Working_x0020_Years>
        <Working_x0020_Months>9</Working_x0020_Months>
        <Working_x0020_Days>28</Working_x0020_Days>
        <Join_x0020_Date>2024-08-01T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131003</Employee_x0020_Code>
        <Full_x0020_Name>Ferra Novita</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Staff Kasir RU</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Age>31</Age>
        <Working_x0020_Years>8</Working_x0020_Years>
        <Working_x0020_Months>9</Working_x0020_Months>
        <Working_x0020_Days>22</Working_x0020_Days>
        <Join_x0020_Date>2017-08-04T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>4</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131004</Employee_x0020_Code>
        <Full_x0020_Name>Junaidi</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Operator Borongan Sawmill</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Age>43</Age>
        <Working_x0020_Years>0</Working_x0020_Years>
        <Working_x0020_Months>8</Working_x0020_Months>
        <Working_x0020_Days>7</Working_x0020_Days>
        <Join_x0020_Date>2025-09-19T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131005</Employee_x0020_Code>
        <Full_x0020_Name>Maniati Hia</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Kru Grader Sawmill</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Age>51</Age>
        <Working_x0020_Years>9</Working_x0020_Years>
        <Working_x0020_Months>2</Working_x0020_Months>
        <Working_x0020_Days>13</Working_x0020_Days>
        <Join_x0020_Date>2017-03-13T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>6</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131006</Employee_x0020_Code>
        <Full_x0020_Name>Senior Person</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Ka. Div. Produksi</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Age>61</Age>
        <Working_x0020_Years>20</Working_x0020_Years>
        <Working_x0020_Months>0</Working_x0020_Months>
        <Working_x0020_Days>0</Working_x0020_Days>
        <Join_x0020_Date>2006-05-26T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>3</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 3</Employee_x0020_Code>
        <Full_x0020_Name>Special Person</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Ka. Dept. Finance &amp; Accounting RU</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Age>49</Age>
        <Working_x0020_Years>11</Working_x0020_Years>
        <Working_x0020_Months>8</Working_x0020_Months>
        <Working_x0020_Days>10</Working_x0020_Days>
        <Join_x0020_Date>2014-01-01T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>6</Level_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>131008</Employee_x0020_Code>
        <Full_x0020_Name>Non Aktif</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kru Packing</Job_x0020_Title>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Age>19</Age>
        <Working_x0020_Years>0</Working_x0020_Years>
        <Working_x0020_Months>1</Working_x0020_Months>
        <Working_x0020_Days>5</Working_x0020_Days>
        <Join_x0020_Date>2026-04-20T00:00:00+07:00</Join_x0020_Date>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Active>Terminated</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
