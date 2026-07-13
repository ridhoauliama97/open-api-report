<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\DaftarLiburCutiBersamaReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsDaftarLiburCutiBersamaReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_holiday_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->holidayXml();

        $service = Mockery::mock(DaftarLiburCutiBersamaReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: holiday.xml')
            ->andReturn($this->reportData('GSU'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.holiday.daftar_libur_cuti_bersama.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'GSU'
                && ($data['reportData']['title'] ?? null) === 'Daftar Libur Dan Cuti Bersama'
                && ($data['reportData']['printed_by'] ?? null) === 'Windi'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DaftarLiburCutiBersamaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/holiday/daftar-libur-cuti-bersama/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Windi',
            'xml_file' => UploadedFile::fake()->createWithContent('holiday.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Holiday - Daftar Libur Dan Cuti Bersama (GSU)');
    }

    public function test_parser_sorts_holiday_rows_and_formats_dates(): void
    {
        $reportData = app(DaftarLiburCutiBersamaReportService::class)
            ->buildReportDataFromXml($this->holidayXml(), 'test xml');

        $this->assertSame('Daftar Libur Dan Cuti Bersama', $reportData['title']);
        $this->assertSame(2, $reportData['total_rows']);
        $this->assertSame('01-Jan-26', $reportData['rows'][0]['Tanggal']);
        $this->assertSame('New Year 2026', $reportData['rows'][0]['Nama Libur / Cuti Bersama']);
        $this->assertSame('14-Mei-26', $reportData['rows'][1]['Tanggal']);
        $this->assertSame('Tahun 2026', $reportData['period']['label']);
        $this->assertSame(0, $reportData['summary']['total_cuti_bersama']);
        $this->assertSame(2, $reportData['summary']['total_libur']);
        $this->assertSame(2, $reportData['summary']['total']);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company): array
    {
        return [
            'printed_at' => '31 May 2026 13:26',
            'printed_by' => 'Ridho',
            'company' => $company,
            'title' => 'Daftar Libur Dan Cuti Bersama',
            'headers' => ['No', 'Tanggal', 'Nama Libur / Cuti Bersama'],
            'rows' => [],
            'total_rows' => 0,
            'summary' => [
                'total_cuti_bersama' => 0,
                'total_libur' => 0,
                'total' => 0,
            ],
            'period' => ['label' => 'Tahun 2026'],
        ];
    }

    private function holidayXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Holiday>
        <Date>2026-05-14T00:00:00+07:00</Date>
        <Name>Kenaikan Yesus Kristus</Name>
    </Holiday>
    <Holiday>
        <Date>2026-01-01T00:00:00+07:00</Date>
        <Name>New Year 2026</Name>
    </Holiday>
</NewDataSet>
XML;
    }
}
