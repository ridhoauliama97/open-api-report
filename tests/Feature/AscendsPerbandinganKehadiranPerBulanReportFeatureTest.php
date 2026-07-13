<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\PerbandinganKehadiranPerBulanReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsPerbandinganKehadiranPerBulanReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_comparison_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(PerbandinganKehadiranPerBulanReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn(array $filters): bool => ($filters['company'] ?? null) === 'RU'
                && ($filters['start_date'] ?? null) === '2026-01-01'
                && ($filters['end_date'] ?? null) === '2026-02-28'
            ))
            ->andReturn($this->reportData('RU'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance.perbandingan_kehadiran_per_bulan.pdf', Mockery::on(
                static fn(array $data): bool => ($data['company'] ?? null) === 'RU'
                && ($data['reportData']['title'] ?? null) === 'Laporan Perbandingan Kehadiran Per Bulan'
                && ($data['reportData']['printed_by'] ?? null) === 'Ridho'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PerbandinganKehadiranPerBulanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance/perbandingan-kehadiran-per-bulan/pdf', [
            'DB_CompanyName' => 'RU',
            'Sys_Username' => 'Ridho',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-28',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Attendance - Laporan Perbandingan Kehadiran Per Bulan (RU)');
    }

    public function test_parser_builds_staff_and_kk_kt_monthly_sections(): void
    {
        $reportData = app(PerbandinganKehadiranPerBulanReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'start_date' => '2026-01-01',
                'end_date' => '2026-02-28',
            ]);

        $this->assertSame('Laporan Perbandingan Kehadiran Per Bulan', $reportData['title']);
        $this->assertSame('Dari 01-Jan-26 s/d 28-Feb-26', $reportData['period']['label']);
        $this->assertSame(['Staff', 'KK/KT'], array_column($reportData['sections'], 'title'));

        $staffRows = $reportData['sections'][0]['rows'];
        $kkKtRows = $reportData['sections'][1]['rows'];

        $this->assertSame('Januari', $staffRows[0]['Bulan']);
        $this->assertSame('2', $staffRows[0]['Total Karyawan']);
        $this->assertSame('1', $staffRows[0]['Jumlah Ketidakhadiran']);
        $this->assertSame('50%', $staffRows[0]['% Ketidakhadiran']);
        $this->assertSame('1', $staffRows[0]['Jumlah Terlambat']);
        $this->assertSame('50%', $staffRows[0]['% Terlambat']);

        $this->assertSame('Februari', $kkKtRows[1]['Bulan']);
        $this->assertSame('2', $kkKtRows[1]['Total Karyawan']);
        $this->assertSame('1', $kkKtRows[1]['Jumlah Ketidakhadiran']);
        $this->assertSame('1', $kkKtRows[1]['Jumlah Terlambat']);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company): array
    {
        return [
            'printed_at' => '12 June 2026 09:24',
            'printed_by' => 'Ridho',
            'company' => $company,
            'title' => 'Laporan Perbandingan Kehadiran Per Bulan',
            'headers' => ['Bulan', 'Total Karyawan', 'Jumlah Ketidakhadiran', '% Ketidakhadiran', 'Jumlah Terlambat', '% Terlambat'],
            'sections' => [],
            'total_rows' => 0,
            'period' => ['label' => 'Dari 01-Jan-26 s/d 28-Feb-26'],
        ];
    }

    private function attendanceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <AttendaceSimple>
        <Employee_x0020_Code>100001</Employee_x0020_Code>
        <Full_x0020_Name>Staff Satu</Full_x0020_Name>
        <Date>2026-01-02T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>08:01</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-01-02T08:01:00+07:00</Sign_x0020_In>
        <Shift>08.00-17.00</Shift>
        <Scheduled_x0020_Shift>Karyawan Normal (08.00-16.15)</Scheduled_x0020_Shift>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>100002</Employee_x0020_Code>
        <Full_x0020_Name>Staff Dua</Full_x0020_Name>
        <Date>2026-01-02T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>200001</Employee_x0020_Code>
        <Full_x0020_Name>KK Satu</Full_x0020_Name>
        <Date>2026-02-03T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:50</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-02-03T07:50:00+07:00</Sign_x0020_In>
        <Shift>Kary. Normal Shift</Shift>
        <Scheduled_x0020_Shift>Karyawan Normal (07.45-16.00)</Scheduled_x0020_Shift>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>200002</Employee_x0020_Code>
        <Full_x0020_Name>KT Satu</Full_x0020_Name>
        <Date>2026-02-03T00:00:00+07:00</Date>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:40</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-02-03T07:40:00+07:00</Sign_x0020_In>
        <Shift>Kary. Normal Shift</Shift>
        <Scheduled_x0020_Shift>Karyawan Normal (07.45-16.00)</Scheduled_x0020_Shift>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>200002</Employee_x0020_Code>
        <Full_x0020_Name>KT Satu</Full_x0020_Name>
        <Date>2026-02-04T00:00:00+07:00</Date>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KT</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </AttendaceSimple>
</NewDataSet>
XML;
    }
}
