<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KehadiranKkKtStReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKehadiranKkKtStReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_hrm_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KehadiranKkKtStReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.employee_list.kehadiran_kk_kt_st.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                && str_contains((string) ($data['reportData']['title'] ?? ''), 'Laporan Kehadiran KK/KT/ST')
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KehadiranKkKtStReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/kehadiran-kk-kt-st/pdf', [
            'company' => 'RU',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Employee List - Laporan Kehadiran KK KT ST (RU)');
    }

    public function test_shared_hrm_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KehadiranKkKtStReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.employee_list.kehadiran_kk_kt_st.pdf', Mockery::type('array'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KehadiranKkKtStReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/shared/hrm/kehadiran-kk-kt-st/pdf',
                ['company' => 'UC'],
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

        $this->assertPdfDisposition($response, 'attachment', 'Employee List - Laporan Kehadiran KK KT ST (UC)');
    }

    public function test_shared_hrm_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KehadiranKkKtStReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KehadiranKkKtStReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/kehadiran-kk-kt-st/pdf', [
            'company' => 'RU',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_filters_active_kk_kt_st_rows_and_groups_by_division(): void
    {
        $reportData = app(KehadiranKkKtStReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame(['No', 'Nama', 'Keterangan'], $reportData['headers']);
        $this->assertSame(4, $reportData['total_rows']);
        $this->assertSame(['KK' => 2, 'KT' => 1, 'ST' => 1], $reportData['status_summary']);
        $this->assertSame('Divisi : PKB', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Divisi : VKD', $reportData['grouped_rows'][1]['label']);
        $this->assertCount(6, $reportData['grouped_rows'][0]['rows']);
        $this->assertCount(6, $reportData['grouped_rows'][1]['rows']);
        $this->assertSame('', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertCount(15, $reportData['follow_up_rows']);
        $this->assertSame('', $reportData['follow_up_rows'][0]['Nama']);
        $this->assertSame('', $reportData['follow_up_rows'][0]['Divisi']);
        $this->assertSame('', $reportData['follow_up_rows'][0]['Keterangan']);
        $this->assertStringNotContainsString('Dedi Nonaktif', json_encode($reportData['grouped_rows']));
        $this->assertStringNotContainsString('Special User', json_encode($reportData['grouped_rows']));
        $this->assertStringNotContainsString('Borongan User', json_encode($reportData['grouped_rows']));
        $this->assertStringNotContainsString('Tanpa Departemen', json_encode($reportData['grouped_rows']));
    }

    public function test_pdf_blade_renders_expected_layout_sections(): void
    {
        $reportData = app(KehadiranKkKtStReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');
        $reportData['title'] = 'Laporan Kehadiran KK/KT/ST';

        $html = view('ascends.shared.hrm.employee_list.kehadiran_kk_kt_st.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Kehadiran KK/KT/ST', $html);
        $this->assertStringContainsString('Divisi : PKB', $html);
        $this->assertStringContainsString('Divisi : VKD', $html);
        $this->assertStringContainsString('Anggota : ______ / ______ Orang', $html);
        $this->assertStringContainsString('Selisih : _____ Orang', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('Keterangan', $html);
        $this->assertStringContainsString('Follow Up KK/KT/ST', $html);
        $this->assertStringContainsString('Divisi', $html);
        $this->assertStringContainsString('Penanganan', $html);
        $this->assertStringContainsString('Follow Up', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'printed_at' => '04 June 2026 09:07',
            'printed_by' => 'Ridho',
            'company' => 'RU',
            'title' => 'Laporan Kehadiran KK/KT/ST',
            'headers' => ['No', 'Nama', 'Keterangan'],
            'rows' => [],
            'grouped_rows' => [],
            'follow_up_rows' => [],
            'total_rows' => 4,
        ];
    }

    private function employeeListXml(string $recordTag = 'Employees'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>1001</Employee_x0020_Code>
        <Full_x0020_Name>Ferra Novita</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Active>Active</Active>
        <Nama_x0020_User>Ridho</Nama_x0020_User>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1002</Employee_x0020_Code>
        <Full_x0020_Name>Andri Ardiwa</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1003</Employee_x0020_Code>
        <Full_x0020_Name>Yazuwar Mendrofa</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1004</Employee_x0020_Code>
        <Full_x0020_Name>Ronauli Sitompul</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1005</Employee_x0020_Code>
        <Full_x0020_Name>Borongan User</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 1</Employee_x0020_Code>
        <Full_x0020_Name>Special User</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1006</Employee_x0020_Code>
        <Full_x0020_Name>Dedi Nonaktif</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Active>Terminated</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1007</Employee_x0020_Code>
        <Full_x0020_Name>Tanpa Departemen</Full_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Department_x0020_Name></Department_x0020_Name>
        <Active>Active</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
