<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KaryawanPerMasaKerjaReportService;
use App\Services\PdfGenerator;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKaryawanPerMasaKerjaReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_ascend_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerMasaKerjaReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_masa_kerja.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['total_rows'] ?? null) === 1
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerMasaKerjaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/ru/hrm/karyawan-per-masa-kerja/pdf', [
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Masa Kerja (RU)');
    }

    public function test_ascend_test_upload_form_can_preview_karyawan_per_masa_kerja_pdf(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerMasaKerjaReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_masa_kerja.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Per Masa Kerja (RU)'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerMasaKerjaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/ascend-test/pdf', [
            'report_type' => 'karyawan_per_masa_kerja',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Masa Kerja (RU)');
    }

    public function test_internal_ascend_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(KaryawanPerMasaKerjaReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.ru.hrm.karyawan_per_masa_kerja.pdf', Mockery::on(
                static fn (array $data): bool => ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Per Masa Kerja (RU)'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KaryawanPerMasaKerjaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/ru/hrm/karyawan-per-masa-kerja/pdf',
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

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Karyawan Per Masa Kerja (RU)');
    }

    public function test_internal_ascend_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KaryawanPerMasaKerjaReportService::class);
        $service->shouldNotReceive('buildReportData');
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KaryawanPerMasaKerjaReportService::class, $service);

        $this->postJson('/api/internal/ascends/ru/hrm/karyawan-per-masa-kerja/pdf', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_karyawan_per_masa_kerja_parser_groups_rows_and_builds_summaries(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-25 11:25:00'));

        $reportData = app(KaryawanPerMasaKerjaReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'Nama',
            'L/P',
            'Jabatan',
            'Status',
            'Level',
            'Tanggal Masuk',
            'Masa Kerja',
        ], $reportData['headers']);
        $this->assertSame(5, $reportData['total_rows']);

        $groups = $reportData['grouped_rows'];
        $this->assertSame('Masa Kerja : 0 - 6 Bulan', $groups['0_6_bulan']['label']);
        $this->assertSame('Andi Enam Kurang', $groups['0_6_bulan']['rows'][0]['Nama']);
        $this->assertSame('5 Bln 2 Hari', $groups['0_6_bulan']['rows'][0]['Masa Kerja']);
        $this->assertSame(1, $groups['0_6_bulan']['summary']['subtotal']);
        $this->assertSame(['count' => 1, 'percent' => 100], $groups['0_6_bulan']['summary']['gender']['L']);

        $this->assertSame('Bela Enam Bulan', $groups['6_12_bulan']['rows'][0]['Nama']);
        $this->assertSame('6 Bln', $groups['6_12_bulan']['rows'][0]['Masa Kerja']);
        $this->assertSame('25-Nov-25', $groups['6_12_bulan']['rows'][0]['Tanggal Masuk']);
        $this->assertSame('KK', $groups['6_12_bulan']['rows'][0]['Status']);

        $this->assertSame(5, $reportData['grand_summary']['subtotal']);
        $this->assertSame(['count' => 3, 'percent' => 60], $reportData['grand_summary']['gender']['L']);
        $this->assertSame(['count' => 2, 'percent' => 40], $reportData['grand_summary']['gender']['P']);
        $this->assertSame(['count' => 2, 'percent' => 40], $reportData['grand_summary']['status']['BR']);
        $this->assertSame(['count' => 2, 'percent' => 40], $reportData['grand_summary']['status']['KK']);
        $this->assertSame(['count' => 0, 'percent' => 0], $reportData['grand_summary']['status']['KT']);
        $this->assertSame(['count' => 1, 'percent' => 20], $reportData['grand_summary']['status']['ST']);
        $this->assertSame('ST', $groups['2_3_tahun']['rows'][0]['Status']);
        $this->assertSame(['count' => 2, 'percent' => 40], $reportData['grand_summary']['level']['Level 1']);
        $this->assertSame(['count' => 0, 'percent' => 0], $reportData['grand_summary']['level']['Level 5']);
        $this->assertSame(['count' => 0, 'percent' => 0], $reportData['grand_summary']['level']['Level 7']);
        $this->assertSame(1, $reportData['grand_summary']['work_period']['2_3_tahun']['count']);
        $this->assertSame('Masa Kerja : 3 Tahun Lebih', $reportData['grand_summary']['work_period']['3_tahun_lebih']['label']);
        $this->assertSame(1, $reportData['grand_summary']['work_period']['3_tahun_lebih']['count']);
        $this->assertSame(20, $reportData['grand_summary']['work_period']['3_tahun_lebih']['percent']);
    }

    public function test_karyawan_per_masa_kerja_pdf_renders_bulleted_summary_layout(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-25 11:25:00'));

        $reportData = app(KaryawanPerMasaKerjaReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.ru.hrm.karyawan_per_masa_kerja.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('SubTotal', $html);
        $this->assertStringContainsString('Akumulasi L/P', $html);
        $this->assertStringContainsString('• Laki-Laki = 1 (100%)', $html);
        $this->assertStringContainsString('• Perempuan = 0 (0%)', $html);
        $this->assertStringContainsString('• Level 1 = 2 (40%)', $html);
        $this->assertStringContainsString('• Level 7 = 0 (0%)', $html);
        $this->assertStringContainsString('• BR = 2 (40%)', $html);
        $this->assertStringContainsString('• KK = 2 (40%)', $html);
        $this->assertStringContainsString('• KT = 0 (0%)', $html);
        $this->assertStringContainsString('• ST = 1 (20%)', $html);
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
            'sub_report' => 'karyawan_per_masa_kerja',
            'label' => 'Laporan Karyawan Per Masa Kerja (RU)',
            'title' => 'Laporan Karyawan Per Masa Kerja (RU)',
            'source_file' => 'request field: xml',
            'headers' => [
                'No',
                'Nama',
                'L/P',
                'Jabatan',
                'Status',
                'Level',
                'Tanggal Masuk',
                'Masa Kerja',
            ],
            'rows' => [
                [
                    'Nama' => 'Sari Senior',
                    'L/P' => 'P',
                    'Jabatan' => 'Supervisor',
                    'Status' => 'Karyawan Kontrak',
                    'Level' => '2',
                    'Tanggal Masuk' => '20/02/2016',
                    'Masa Kerja' => '10 Thn 3 Bln',
                ],
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
        <Full_x0020_Name>Andi Enam Kurang</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Staff</Job_x0020_Title>
        <Job_x0020_Status>BR</Job_x0020_Status>
        <Salary_x0020_Security_x0020_Code>BORONGAN</Salary_x0020_Security_x0020_Code>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Join_x0020_Date>2025-12-23T00:00:00+07:00</Join_x0020_Date>
        <Working_x0020_Years>0</Working_x0020_Years>
        <Working_x0020_Months>5</Working_x0020_Months>
        <Working_x0020_Days>2</Working_x0020_Days>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Full_x0020_Name>Bela Enam Bulan</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Job_x0020_Status>Contract</Job_x0020_Status>
        <Salary_x0020_Security_x0020_Code>KL - KT</Salary_x0020_Security_x0020_Code>
        <Level_x0020_Name>2</Level_x0020_Name>
        <Join_x0020_Date>2025-11-25T00:00:00+07:00</Join_x0020_Date>
        <Working_x0020_Years>0</Working_x0020_Years>
        <Working_x0020_Months>6</Working_x0020_Months>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Full_x0020_Name>Candra Satu Tahun</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Mandor</Job_x0020_Title>
        <Job_x0020_Status>BR</Job_x0020_Status>
        <Salary_x0020_Security_x0020_Code>BORONGAN</Salary_x0020_Security_x0020_Code>
        <Level_x0020_Name>1</Level_x0020_Name>
        <Join_x0020_Date>2024-11-22T00:00:00+07:00</Join_x0020_Date>
        <Working_x0020_Years>1</Working_x0020_Years>
        <Working_x0020_Months>6</Working_x0020_Months>
        <Working_x0020_Days>3</Working_x0020_Days>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Full_x0020_Name>Dina Dua Tahun</Full_x0020_Name>
        <Sex>Female</Sex>
        <Job_x0020_Title>Supervisor</Job_x0020_Title>
        <Job_x0020_Status>Permanent</Job_x0020_Status>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Salary_x0020_Security_x0020_Code>STAFF</Salary_x0020_Security_x0020_Code>
        <Level_x0020_Name>3</Level_x0020_Name>
        <Join_x0020_Date>2024-03-21T00:00:00+07:00</Join_x0020_Date>
        <Working_x0020_Years>2</Working_x0020_Years>
        <Working_x0020_Months>2</Working_x0020_Months>
        <Working_x0020_Days>4</Working_x0020_Days>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Full_x0020_Name>Eko Tiga Tahun</Full_x0020_Name>
        <Sex>Male</Sex>
        <Job_x0020_Title>Kepala Bagian</Job_x0020_Title>
        <Job_x0020_Status>KK</Job_x0020_Status>
        <Salary_x0020_Security_x0020_Code>KL - KT</Salary_x0020_Security_x0020_Code>
        <Level_x0020_Name>4</Level_x0020_Name>
        <Join_x0020_Date>2023-04-20T00:00:00+07:00</Join_x0020_Date>
        <Working_x0020_Years>3</Working_x0020_Years>
        <Working_x0020_Months>1</Working_x0020_Months>
        <Working_x0020_Days>5</Working_x0020_Days>
        <Active>Active</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
