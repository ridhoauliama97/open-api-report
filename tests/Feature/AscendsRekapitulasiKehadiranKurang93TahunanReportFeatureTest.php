<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\RekapitulasiKehadiranKurang93TahunanReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsRekapitulasiKehadiranKurang93TahunanReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(RekapitulasiKehadiranKurang93TahunanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['company'] ?? null) === 'GSU'
                    && ($filters['Pilih Status'] ?? null) === 'Staff'
            ))
            ->andReturn($this->reportData('GSU', 'Staff'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.rekapitulasi_kehadiran_kurang_93_tahunan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'GSU'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Rekapitulasi Kehadiran < 93 % Tahunan (Staff) (GSU)'
                    && ($data['reportData']['printed_by'] ?? null) === 'Windi'
                    && ($data['pdf_orientation'] ?? null) === 'landscape'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapitulasiKehadiranKurang93TahunanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-kehadiran-kurang-93-tahunan/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Windi',
            'Pilih Status' => 'Staff',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Rekapitulasi Kehadiran Kurang 93 Persen Tahunan Staff (GSU)');
    }

    public function test_parser_applies_staff_status_and_yearly_less_than_93_formula(): void
    {
        $reportData = app(RekapitulasiKehadiranKurang93TahunanReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih Status' => 'Staff',
                'start_date' => '2026-01-01',
                'end_date' => '2026-05-31',
            ]);

        $this->assertSame('Staff', $reportData['status']);
        $this->assertSame(['Nama', '01', '02', '03', '04', '05', 'Total'], $reportData['headers']);
        $this->assertCount(1, $reportData['rows']);

        $this->assertSame('Frans Bossy Panjaitan', $reportData['rows'][0]['Nama']);
        $this->assertSame('92%', $reportData['rows'][0]['1']);
        $this->assertSame('71%', $reportData['rows'][0]['2']);
        $this->assertSame('-%', $reportData['rows'][0]['3']);
        $this->assertSame('-%', $reportData['rows'][0]['4']);
        $this->assertSame('89%', $reportData['rows'][0]['5']);
        $this->assertSame(3, $reportData['rows'][0]['Total']);
    }

    public function test_parser_applies_kk_kt_br_status(): void
    {
        $reportData = app(RekapitulasiKehadiranKurang93TahunanReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih Status' => 'KK/KT',
                'start_date' => '2026-01-01',
                'end_date' => '2026-05-31',
            ]);

        $this->assertSame('KK/KT', $reportData['status']);
        $this->assertCount(1, $reportData['rows']);
        $this->assertSame('Karyawan KT', $reportData['rows'][0]['Nama']);
        $this->assertSame('89%', $reportData['rows'][0]['5']);
        $this->assertSame(1, $reportData['rows'][0]['Total']);
    }

    public function test_parser_accepts_normalized_pilih_status_key(): void
    {
        $reportData = app(RekapitulasiKehadiranKurang93TahunanReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih_x0020_Status' => 'Staff',
                'start_date' => '2026-01-01',
                'end_date' => '2026-05-31',
            ]);

        $this->assertSame('Staff', $reportData['status']);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company, string $status): array
    {
        return [
            'printed_at' => '09 Jun 2026 13:26',
            'printed_by' => 'Ridho',
            'company' => $company,
            'status' => $status,
            'title' => "Laporan Rekapitulasi Kehadiran < 93 % Tahunan ({$status}) ({$company})",
            'headers' => ['Nama', '01', '02', 'Total'],
            'months' => [1, 2],
            'rows' => [],
            'total_rows' => 0,
            'period' => ['label' => 'Dari 01-Jan-26 s/d 28-Feb-26'],
        ];
    }

    private function attendanceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-01-01T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>I</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-01-02T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>I</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-02-01T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>I</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-02-02T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>I</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-02-03T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>I</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-02-04T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>I</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-02-05T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>CM</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-02-06T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>CM</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-03-01T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>C</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-04-01T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>SKD</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>S</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Frans Bossy Panjaitan</Full_x0020_Name>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>A</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120002</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan KT</Full_x0020_Name>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>A</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120002</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan KT</Full_x0020_Name>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>I</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>SPECIAL001</Employee_x0020_Code>
        <Full_x0020_Name>Special Employee</Full_x0020_Name>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Leave_x0020_Type_x0020_Code>A</Leave_x0020_Type_x0020_Code>
        <Present_x002F_Absent>Absent</Present_x002F_Absent>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
</NewDataSet>
XML;
    }
}
