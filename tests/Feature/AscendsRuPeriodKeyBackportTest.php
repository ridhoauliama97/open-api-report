<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\PersentaseKehadiranBulananReportService;
use Tests\TestCase;

class AscendsRuPeriodKeyBackportTest extends TestCase
{
    public function test_ru_persentase_kehadiran_bulanan_resolves_attendance_date_start_date(): void
    {
        $reportData = app(PersentaseKehadiranBulananReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'AttendanceDate.StartDate' => '2026-01-01',
                'AttendanceDate.EndDate' => '2026-01-31',
            ]);

        $this->assertNotEmpty($reportData['period']['label']);
        $this->assertStringContainsString('Jan-26', $reportData['period']['label']);
    }

    public function test_ru_persentase_kehadiran_bulanan_resolves_attendance_date_x0020_encoded_key(): void
    {
        $reportData = app(PersentaseKehadiranBulananReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'AttendanceDate_x0020_StartDate' => '2026-01-01',
                'AttendanceDate_x0020_EndDate' => '2026-01-31',
            ]);

        $this->assertNotEmpty($reportData['period']['label']);
        $this->assertStringContainsString('Jan-26', $reportData['period']['label']);
    }

    public function test_ru_persentase_kehadiran_bulanan_still_resolves_plain_start_date(): void
    {
        $reportData = app(PersentaseKehadiranBulananReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
            ]);

        $this->assertNotEmpty($reportData['period']['label']);
        $this->assertStringContainsString('Jan-26', $reportData['period']['label']);
    }

    public function test_ru_persentase_kehadiran_bulanan_still_resolves_tgl_awal(): void
    {
        $reportData = app(PersentaseKehadiranBulananReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ]);

        $this->assertNotEmpty($reportData['period']['label']);
        $this->assertStringContainsString('Jan-26', $reportData['period']['label']);
    }

    private function attendanceXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Aulia</Full_x0020_Name>
        <Department_x0020_Name>Finance &amp; Accounting</Department_x0020_Name>
        <Job_x0020_Title>Staff Accounting</Job_x0020_Title>
        <Sex>Female</Sex>
        <Level>1</Level>
        <Date>2026-01-15T00:00:00+07:00</Date>
        <Scheduled_x0020_Shift>Normal</Scheduled_x0020_Shift>
        <Present_x002F_Absent>Present</Present_x002F_Absent>
        <Sign_x0020_In>2026-01-15T07:30:00+07:00</Sign_x0020_In>
        <HK>1.0000</HK>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Attendance>
</NewDataSet>
XML;
    }
}
