<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\RekapitulasiPengabaianKeterlambatanTahunanReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsRekapitulasiPengabaianKeterlambatanTahunanReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(RekapitulasiPengabaianKeterlambatanTahunanReportService::class);
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
            ->with('ascends.shared.hrm.attendance_full.rekapitulasi_pengabaian_keterlambatan_tahunan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'GSU'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Rekapitulasi Pengabaian Keterlambatan Tahunan (Staff) (GSU)'
                    && ($data['reportData']['printed_by'] ?? null) === 'Windi'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapitulasiPengabaianKeterlambatanTahunanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/rekapitulasi-pengabaian-keterlambatan-tahunan/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Windi',
            'Pilih Status' => 'Staff',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Attendance Full - Laporan Rekapitulasi Pengabaian Keterlambatan Tahunan Staff (GSU)');
    }

    public function test_parser_applies_staff_last_modified_and_excluded_employee_filters(): void
    {
        $reportData = app(RekapitulasiPengabaianKeterlambatanTahunanReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih Status' => 'Staff',
                'start_date' => '2026-01-01',
                'end_date' => '2026-05-31',
            ]);

        $this->assertSame('Staff', $reportData['status']);
        $this->assertSame(['No', 'Nama', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Total'], $reportData['headers']);
        $this->assertCount(2, $reportData['rows']);

        $this->assertSame('Desi Marziana', $reportData['rows'][0]['Nama']);
        $this->assertSame(1, $reportData['rows'][0]['1']);
        $this->assertSame('-', $reportData['rows'][0]['2']);
        $this->assertSame(2, $reportData['rows'][0]['4']);
        $this->assertSame(3, $reportData['rows'][0]['Total']);

        $this->assertSame('Sumisri', $reportData['rows'][1]['Nama']);
        $this->assertSame(1, $reportData['rows'][1]['3']);
        $this->assertSame(1, $reportData['rows'][1]['Total']);

        $this->assertSame([1 => 1, 2 => 0, 3 => 1, 4 => 2, 5 => 0], $reportData['month_totals']);
        $this->assertSame(4, $reportData['grand_total']);
    }

    public function test_parser_applies_kk_kt_status_without_br(): void
    {
        $reportData = app(RekapitulasiPengabaianKeterlambatanTahunanReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'Pilih Status' => 'KK/KT',
                'start_date' => '2026-01-01',
                'end_date' => '2026-05-31',
            ]);

        $this->assertSame('KK/KT', $reportData['status']);
        $this->assertCount(1, $reportData['rows']);
        $this->assertSame('Karyawan KT', $reportData['rows'][0]['Nama']);
        $this->assertSame(2, $reportData['rows'][0]['5']);
        $this->assertSame(2, $reportData['rows'][0]['Total']);
        $this->assertSame(2, $reportData['grand_total']);
    }

    public function test_parser_accepts_normalized_pilih_status_key(): void
    {
        $reportData = app(RekapitulasiPengabaianKeterlambatanTahunanReportService::class)
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
            'title' => "Laporan Rekapitulasi Pengabaian Keterlambatan Tahunan ({$status}) ({$company})",
            'headers' => ['No', 'Nama', 'Jan', 'Feb', 'Total'],
            'month_labels' => [1 => 'Jan', 2 => 'Feb'],
            'months' => [1, 2],
            'rows' => [],
            'month_totals' => [1 => 0, 2 => 0],
            'grand_total' => 0,
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
        <Full_x0020_Name>Desi Marziana</Full_x0020_Name>
        <Date>2026-01-01T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Last_x0020_Modified_x0020_By>Dina</Last_x0020_Modified_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Desi Marziana</Full_x0020_Name>
        <Date>2026-04-01T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Last_x0020_Modified_x0020_By>Windi</Last_x0020_Modified_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>Desi Marziana</Full_x0020_Name>
        <Date>2026-04-02T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Last_x0020_Modified_x0020_By>Sasi</Last_x0020_Modified_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120002</Employee_x0020_Code>
        <Full_x0020_Name>Sumisri</Full_x0020_Name>
        <Date>2026-03-01T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Last_x0020_Modified_x0020_By>Dina</Last_x0020_Modified_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120003</Employee_x0020_Code>
        <Full_x0020_Name>Blank Modifier</Full_x0020_Name>
        <Date>2026-02-01T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Last_x0020_Modified_x0020_By></Last_x0020_Modified_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>12054399</Employee_x0020_Code>
        <Full_x0020_Name>Excluded Employee</Full_x0020_Name>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Last_x0020_Modified_x0020_By>Dina</Last_x0020_Modified_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120004</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan KT</Full_x0020_Name>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Last_x0020_Modified_x0020_By>Dina</Last_x0020_Modified_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120004</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan KT</Full_x0020_Name>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Last_x0020_Modified_x0020_By>Dina</Last_x0020_Modified_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120005</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan BR</Full_x0020_Name>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Last_x0020_Modified_x0020_By>Dina</Last_x0020_Modified_x0020_By>
    </Attendance>
</NewDataSet>
XML;
    }
}
