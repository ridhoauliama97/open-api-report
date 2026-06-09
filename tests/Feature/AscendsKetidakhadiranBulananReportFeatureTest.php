<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KetidakhadiranBulananReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKetidakhadiranBulananReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_absence_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->absenceXml();

        $service = Mockery::mock(KetidakhadiranBulananReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: absence.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['start_date'] ?? null) === '2026-05-05'
                    && ($filters['end_date'] ?? null) === '2026-06-04'
                    && ($filters['tipe'] ?? null) === 'KK/KT'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.absence.ketidakhadiran_bulanan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                    && str_contains((string) ($data['reportData']['title'] ?? ''), 'Laporan Ketidakhadiran Bulanan')
                    && str_contains((string) ($data['reportData']['title'] ?? ''), 'KK/KT')
                    && ($data['pdf_orientation'] ?? null) === 'landscape'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KetidakhadiranBulananReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/absence/ketidakhadiran-bulanan/pdf', [
            'company' => 'RU',
            'tipe' => 'KK/KT',
            'start_date' => '2026-05-05',
            'end_date' => '2026-06-04',
            'xml_file' => UploadedFile::fake()->createWithContent('absence.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Absence - Laporan Ketidakhadiran Bulanan (RU) KK KT');
    }

    public function test_shared_absence_api_can_render_raw_xml_body_as_pdf_without_jwt(): void
    {
        $xml = $this->absenceXml();

        $service = Mockery::mock(KetidakhadiranBulananReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request raw xml body', Mockery::type('array'))
            ->andReturn($this->reportData('UC'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.absence.ketidakhadiran_bulanan.pdf', Mockery::type('array'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KetidakhadiranBulananReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this
            ->call(
                'POST',
                '/api/internal/ascends/shared/hrm/absence/ketidakhadiran-bulanan/pdf',
                ['company' => 'UC', 'tipe' => 'KK/KT'],
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

        $this->assertPdfDisposition($response, 'inline', 'Absence - Laporan Ketidakhadiran Bulanan (UC) KK KT');
    }

    public function test_shared_absence_api_rejects_request_without_xml_payload(): void
    {
        $service = Mockery::mock(KetidakhadiranBulananReportService::class);
        $service->shouldNotReceive('buildReportDataFromXml');

        $this->app->instance(KetidakhadiranBulananReportService::class, $service);

        $this->postJson('/api/internal/ascends/shared/hrm/absence/ketidakhadiran-bulanan/pdf', [
            'company' => 'RU',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data XML wajib dikirim dari Ascend saat request print PDF.');
    }

    public function test_parser_builds_monthly_absence_matrix(): void
    {
        $reportData = app(KetidakhadiranBulananReportService::class)
            ->buildReportDataFromXml($this->absenceXml('absence'), 'test xml', [
                'TglAwal' => '2026-05-05',
                'TglAkhir' => '2026-05-07',
                'Tipe' => 'KK/KT',
            ]);

        $this->assertSame('KK/KT', $reportData['tipe']);
        $this->assertSame('Dari 05-Mei-26 s/d 07-Mei-26', $reportData['period']['label']);
        $this->assertSame(['5', '6', '7'], array_column($reportData['date_columns'], 'label'));
        $this->assertSame(2, $reportData['total_rows']);
        $this->assertSame('Aulia', $reportData['rows'][0]['Nama']);
        $this->assertSame('DL', $reportData['rows'][0]['dates']['2026-05-05']);
        $this->assertSame('I', $reportData['rows'][0]['dates']['2026-05-06']);
        $this->assertSame('2', $reportData['rows'][0]['Total']);
        $this->assertSame('Betty', $reportData['rows'][1]['Nama']);
        $this->assertSame('SK', $reportData['rows'][1]['dates']['2026-05-07']);
        $this->assertSame('1', $reportData['rows'][1]['Total']);
    }

    public function test_parser_filters_employee_type_from_ascend_category_parameter(): void
    {
        $reportData = app(KetidakhadiranBulananReportService::class)
            ->buildReportDataFromXml($this->absenceXml('absence'), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-07',
                'Pilih Kategori' => 'ST',
            ]);

        $this->assertSame('ST', $reportData['tipe']);
        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame('Cindy', $reportData['rows'][0]['Nama']);
    }

    public function test_pdf_blade_renders_expected_layout(): void
    {
        $reportData = app(KetidakhadiranBulananReportService::class)
            ->buildReportDataFromXml($this->absenceXml('absence'), 'test xml', [
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-07',
                'tipe' => 'KK/KT',
            ]);
        $reportData['title'] = 'Laporan Ketidakhadiran Bulanan - KK/KT';

        $html = view('ascends.shared.hrm.absence.ketidakhadiran_bulanan.pdf', [
            'company' => 'RU',
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Ketidakhadiran Bulanan - KK/KT', $html);
        $this->assertStringContainsString('Dari 05-Mei-26 s/d 07-Mei-26', $html);
        $this->assertStringContainsString('Tanggal', $html);
        $this->assertStringContainsString('Aulia', $html);
        $this->assertStringContainsString('DL', $html);
        $this->assertStringContainsString('Keterangan Tambahan:', $html);
        $this->assertStringContainsString('Izin', $html);
        $this->assertStringContainsString('Di Liburkan Perusahaan', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company = 'RU'): array
    {
        return [
            'printed_at' => '05 June 2026 14:54',
            'printed_by' => 'Ridho',
            'company' => $company,
            'tipe' => 'KK/KT',
            'title' => "Laporan Ketidakhadiran Bulanan ({$company}) - KK/KT",
            'headers' => ['No', 'Nama', 'Jabatan', '5', '6', 'Total'],
            'date_columns' => [
                ['date' => '2026-05-05', 'label' => '5'],
                ['date' => '2026-05-06', 'label' => '6'],
            ],
            'rows' => [],
            'total_rows' => 0,
        ];
    }

    private function absenceXml(string $recordTag = 'Absence'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Aulia</Full_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Leave_x0020_Days>1.0000</Leave_x0020_Days>
        <Leave_x0020_Type>DL</Leave_x0020_Type>
        <Leave_x0020_Type_x0020_Description>Di Liburkan Perusahaan</Leave_x0020_Type_x0020_Description>
        <Remarks>Di Liburkan Perusahaan</Remarks>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Created_x0020_By>Ridho</Created_x0020_By>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Aulia</Full_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Date>2026-05-06T00:00:00+07:00</Date>
        <Leave_x0020_Days>1.0000</Leave_x0020_Days>
        <Leave_x0020_Type>I</Leave_x0020_Type>
        <Leave_x0020_Type_x0020_Description>Izin</Leave_x0020_Type_x0020_Description>
        <Remarks>Izin</Remarks>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130002</Employee_x0020_Code>
        <Full_x0020_Name>Betty</Full_x0020_Name>
        <Job_x0020_Title>Staff</Job_x0020_Title>
        <Date>2026-05-07T00:00:00+07:00</Date>
        <Leave_x0020_Days>1.0000</Leave_x0020_Days>
        <Leave_x0020_Type>SK</Leave_x0020_Type>
        <Leave_x0020_Type_x0020_Description>Surat Keterangan</Leave_x0020_Type_x0020_Description>
        <Remarks>Surat Keterangan</Remarks>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130003</Employee_x0020_Code>
        <Full_x0020_Name>Cindy</Full_x0020_Name>
        <Job_x0020_Title>Staff</Job_x0020_Title>
        <Date>2026-05-06T00:00:00+07:00</Date>
        <Leave_x0020_Days>1.0000</Leave_x0020_Days>
        <Leave_x0020_Type>S</Leave_x0020_Type>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>130004</Employee_x0020_Code>
        <Full_x0020_Name>Dedi</Full_x0020_Name>
        <Job_x0020_Title>Borongan</Job_x0020_Title>
        <Date>2026-05-06T00:00:00+07:00</Date>
        <Leave_x0020_Days>1.0000</Leave_x0020_Days>
        <Leave_x0020_Type>A</Leave_x0020_Type>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
