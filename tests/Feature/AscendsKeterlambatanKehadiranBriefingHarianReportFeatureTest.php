<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\KeterlambatanKehadiranBriefingHarianReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsKeterlambatanKehadiranBriefingHarianReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_late_briefing_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(KeterlambatanKehadiranBriefingHarianReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['company'] ?? null) === 'GSU'
                && ($filters['start_date'] ?? null) === '2026-04-01'
                && ($filters['end_date'] ?? null) === '2026-05-31'
            ))
            ->andReturn($this->reportData('GSU'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance.keterlambatan_kehadiran_briefing_harian.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'GSU'
                && ($data['reportData']['title'] ?? null) === 'Laporan Keterlambatan Kehadiran Briefing Harian'
                && ($data['reportData']['printed_by'] ?? null) === 'Ridho'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KeterlambatanKehadiranBriefingHarianReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance/keterlambatan-kehadiran-briefing-harian/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'start_date' => '2026-04-01',
            'end_date' => '2026-05-31',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Attendance - Laporan Keterlambatan Kehadiran Briefing Harian (GSU)');
    }

    public function test_parser_builds_late_briefing_counts_by_employee_and_month(): void
    {
        $reportData = app(KeterlambatanKehadiranBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'company' => 'GSU',
                'start_date' => '2026-04-01',
                'end_date' => '2026-05-31',
            ]);

        $this->assertSame('Laporan Keterlambatan Kehadiran Briefing Harian', $reportData['title']);
        $this->assertSame('Dari 01/04/2026 Sampai 31/05/2026', $reportData['period']['label']);
        $this->assertSame(['2026-04' => 'Apr', '2026-05' => 'May'], $reportData['month_labels']);
        $this->assertCount(2, $reportData['rows']);

        $this->assertSame('Staff A', $reportData['rows'][0]['Nama']);
        $this->assertSame(1, $reportData['rows'][0]['months']['2026-04']['value']);
        $this->assertSame(1, $reportData['rows'][0]['months']['2026-05']['value']);

        $this->assertSame('Worker B', $reportData['rows'][1]['Nama']);
        $this->assertSame(1, $reportData['rows'][1]['months']['2026-04']['value']);
        $this->assertSame(0, $reportData['rows'][1]['months']['2026-05']['value']);

        $this->assertSame(['2026-04' => 2, '2026-05' => 1], $reportData['totals']);
    }

    public function test_ru_jam_masuk_rules_work_correctly(): void
    {
        $reportData = app(KeterlambatanKehadiranBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->ruAttendanceXml(), 'test xml', [
                'company' => 'RU',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30',
            ]);

        $this->assertCount(2, $reportData['rows']);

        $names = array_map(static fn (array $row): string => (string) ($row['Nama'] ?? ''), $reportData['rows']);
        $this->assertContains('Staff RU A', $names);
        $this->assertContains('Worker RU B', $names);

        foreach ($reportData['rows'] as $row) {
            // Both matching rows should have 1 late occurrence
            $this->assertSame(1, $row['months']['2026-06']['value'] ?? 0);
        }

        // Worker RU C: Shift "Karyawan Normal (07" → jamMasuk 645, sign-in 06:30 → 630 < 645 → not late (excluded)
    }

    public function test_ru_is_workday_excludes_off_shift(): void
    {
        $reportData = app(KeterlambatanKehadiranBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->ruOffShiftXml(), 'test xml', [
                'company' => 'RU',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30',
            ]);

        $this->assertCount(0, $reportData['rows']);
        $this->assertSame(0, $reportData['total_rows']);
    }

    public function test_ru_holiday_records_are_excluded(): void
    {
        $reportData = app(KeterlambatanKehadiranBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->ruHolidayXml(), 'test xml', [
                'company' => 'RU',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30',
            ]);

        $this->assertCount(0, $reportData['rows']);
    }

    public function test_ru_unmatched_shifts_are_late_with_zero_jam_masuk(): void
    {
        $reportData = app(KeterlambatanKehadiranBriefingHarianReportService::class)
            ->buildReportDataFromXml($this->ruFallbackParseXml(), 'test xml', [
                'company' => 'RU',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30',
            ]);

        $this->assertCount(2, $reportData['rows']);

        $names = array_map(static fn (array $row): string => (string) ($row['Nama'] ?? ''), $reportData['rows']);

        $this->assertContains('Custom Shift Worker', $names);
        $this->assertContains('Hour Only Worker', $names);

        foreach ($reportData['rows'] as $row) {
            $this->assertSame(1, $row['months']['2026-06']['value'] ?? 0);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company): array
    {
        return [
            'printed_at' => '12 June 2026 14:15',
            'printed_by' => 'Ridho',
            'company' => $company,
            'title' => 'Laporan Keterlambatan Kehadiran Briefing Harian',
            'headers' => ['Kode', 'Nama', 'Jabatan', 'Apr', 'May'],
            'month_keys' => ['2026-04', '2026-05'],
            'month_labels' => ['2026-04' => 'Apr', '2026-05' => 'May'],
            'rows' => [],
            'totals' => ['2026-04' => 0, '2026-05' => 0],
            'total_rows' => 0,
            'period' => ['label' => 'Dari 01/04/2026 Sampai 31/05/2026'],
        ];
    }

    private function ruAttendanceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <AttendaceSimple>
        <Employee_x0020_Code>200001</Employee_x0020_Code>
        <Full_x0020_Name>Staff RU A</Full_x0020_Name>
        <Job_x0020_Title>Staff Accounting</Job_x0020_Title>
        <Department_x0020_Name>Finance</Department_x0020_Name>
        <Workgroup>Staff Office I (08</Workgroup>
        <Scheduled_x0020_Shift>Regular Shift</Scheduled_x0020_Shift>
        <Date>2026-06-02T00:00:00+07:00</Date>
        <Day>Tuesday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>08:10</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-06-02T08:10:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>200002</Employee_x0020_Code>
        <Full_x0020_Name>Worker RU B</Full_x0020_Name>
        <Job_x0020_Title>Operator Vacuum</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Shift</Workgroup>
        <Scheduled_x0020_Shift>Vacuum Malam (Sabtu</Scheduled_x0020_Shift>
        <Date>2026-06-03T00:00:00+07:00</Date>
        <Day>Wednesday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>19:00</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-06-03T19:00:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>200003</Employee_x0020_Code>
        <Full_x0020_Name>Worker RU C</Full_x0020_Name>
        <Job_x0020_Title>Operator Produksi</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Shift</Workgroup>
        <Scheduled_x0020_Shift>Karyawan Normal (07</Scheduled_x0020_Shift>
        <Date>2026-06-04T00:00:00+07:00</Date>
        <Day>Thursday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>06:30</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-06-04T06:30:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
</NewDataSet>
XML;
    }

    private function ruOffShiftXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <AttendaceSimple>
        <Employee_x0020_Code>300001</Employee_x0020_Code>
        <Full_x0020_Name>Off Shift Worker</Full_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Shift</Workgroup>
        <Scheduled_x0020_Shift>Off - Libur</Scheduled_x0020_Shift>
        <Date>2026-06-02T00:00:00+07:00</Date>
        <Day>Tuesday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>08:10</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-06-02T08:10:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>300002</Employee_x0020_Code>
        <Full_x0020_Name>Another Off Worker</Full_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Shift</Workgroup>
        <Scheduled_x0020_Shift>Off Duty</Scheduled_x0020_Shift>
        <Date>2026-06-03T00:00:00+07:00</Date>
        <Day>Wednesday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>09:00</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-06-03T09:00:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
</NewDataSet>
XML;
    }

    private function ruHolidayXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <AttendaceSimple>
        <Employee_x0020_Code>200004</Employee_x0020_Code>
        <Full_x0020_Name>Holiday Worker</Full_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Shift</Workgroup>
        <Scheduled_x0020_Shift>Karyawan Normal (07</Scheduled_x0020_Shift>
        <Holiday_x0020_Name>Hari Libur Nasional</Holiday_x0020_Name>
        <Date>2026-06-08T00:00:00+07:00</Date>
        <Day>Monday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:00</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-06-08T07:00:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
</NewDataSet>
XML;
    }

    private function ruFallbackParseXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <AttendaceSimple>
        <Employee_x0020_Code>200005</Employee_x0020_Code>
        <Full_x0020_Name>Custom Shift Worker</Full_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Shift</Workgroup>
        <Scheduled_x0020_Shift>Custom Shift (09.30)</Scheduled_x0020_Shift>
        <Date>2026-06-09T00:00:00+07:00</Date>
        <Day>Tuesday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>09:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-06-09T09:20:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>200006</Employee_x0020_Code>
        <Full_x0020_Name>Hour Only Worker</Full_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Shift</Workgroup>
        <Scheduled_x0020_Shift>Night Shift (22</Scheduled_x0020_Shift>
        <Date>2026-06-10T00:00:00+07:00</Date>
        <Day>Wednesday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>21:50</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-06-10T21:50:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
</NewDataSet>
XML;
    }

    private function attendanceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <AttendaceSimple>
        <Employee_x0020_Code>100001</Employee_x0020_Code>
        <Full_x0020_Name>Staff A</Full_x0020_Name>
        <Job_x0020_Title>Staff Accounting</Job_x0020_Title>
        <Department_x0020_Name>Finance</Department_x0020_Name>
        <Workgroup>Staff Office PT. UC I</Workgroup>
        <Scheduled_x0020_Shift>Regular Shift</Scheduled_x0020_Shift>
        <Date>2026-04-02T00:00:00+07:00</Date>
        <Day>Thursday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:50</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-04-02T07:50:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>100001</Employee_x0020_Code>
        <Full_x0020_Name>Staff A</Full_x0020_Name>
        <Job_x0020_Title>Staff Accounting</Job_x0020_Title>
        <Department_x0020_Name>Finance</Department_x0020_Name>
        <Workgroup>Staff Office PT. UC I</Workgroup>
        <Scheduled_x0020_Shift>Regular Shift</Scheduled_x0020_Shift>
        <Date>2026-05-04T00:00:00+07:00</Date>
        <Day>Monday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>08:10</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-05-04T08:10:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>100002</Employee_x0020_Code>
        <Full_x0020_Name>Worker B</Full_x0020_Name>
        <Job_x0020_Title>Operator Inject</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Kary. Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Karyawan Normal</Scheduled_x0020_Shift>
        <Date>2026-04-03T00:00:00+07:00</Date>
        <Day>Friday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>08:01</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-04-03T08:01:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>100002</Employee_x0020_Code>
        <Full_x0020_Name>Worker B</Full_x0020_Name>
        <Job_x0020_Title>Operator Inject</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Kary. Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Karyawan Normal</Scheduled_x0020_Shift>
        <Date>2026-05-05T00:00:00+07:00</Date>
        <Day>Tuesday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>07:30</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-05-05T07:30:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>100003</Employee_x0020_Code>
        <Full_x0020_Name>Holiday Row</Full_x0020_Name>
        <Job_x0020_Title>Operator Inject</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Kary. Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Karyawan Normal</Scheduled_x0020_Shift>
        <Holiday_x0020_Name>Hari Buruh</Holiday_x0020_Name>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Day>Friday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>08:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-05-01T08:20:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>100004</Employee_x0020_Code>
        <Full_x0020_Name>Sunday Row</Full_x0020_Name>
        <Job_x0020_Title>Operator Inject</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Kary. Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Karyawan Normal</Scheduled_x0020_Shift>
        <Date>2026-05-03T00:00:00+07:00</Date>
        <Day>Sunday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>08:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-05-03T08:20:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
    <AttendaceSimple>
        <Employee_x0020_Code>100005</Employee_x0020_Code>
        <Full_x0020_Name>Management Row</Full_x0020_Name>
        <Job_x0020_Title>Manager</Job_x0020_Title>
        <Department_x0020_Name>Management</Department_x0020_Name>
        <Workgroup>Staff Office PT. UC I</Workgroup>
        <Scheduled_x0020_Shift>Staff Office</Scheduled_x0020_Shift>
        <Date>2026-04-07T00:00:00+07:00</Date>
        <Day>Tuesday</Day>
        <Sign_x0020_In_x0020__x0028_Time_x0029_>08:20</Sign_x0020_In_x0020__x0028_Time_x0029_>
        <Sign_x0020_In>2026-04-07T08:20:00+07:00</Sign_x0020_In>
    </AttendaceSimple>
</NewDataSet>
XML;
    }
}
