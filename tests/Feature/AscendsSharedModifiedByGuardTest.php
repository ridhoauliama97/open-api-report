<?php

namespace Tests\Feature;

use App\Services\Ascends\Shared\Hrm\RekapitulasiPengabaianKeterlambatanTahunanReportService;
use Tests\TestCase;

class AscendsSharedModifiedByGuardTest extends TestCase
{
    public function test_shared_excludes_rows_with_empty_last_modified_by(): void
    {
        $reportData = app(RekapitulasiPengabaianKeterlambatanTahunanReportService::class)
            ->buildReportDataFromXml($this->xmlWithEmptyModifiedBy(), 'test xml', [
                'AttendanceDate.StartDate' => '2026-01-01',
                'AttendanceDate.EndDate' => '2026-01-31',
                'Pilih Status' => 'Staff',
            ]);

        $employeeNames = array_column($reportData['rows'] ?? [], 'Nama');

        $this->assertNotContains('Aulia', $employeeNames, 'Row with empty LastModifiedBy should be excluded');
    }

    public function test_shared_includes_rows_with_populated_last_modified_by(): void
    {
        $reportData = app(RekapitulasiPengabaianKeterlambatanTahunanReportService::class)
            ->buildReportDataFromXml($this->xmlWithPopulatedModifiedBy(), 'test xml', [
                'AttendanceDate.StartDate' => '2026-01-01',
                'AttendanceDate.EndDate' => '2026-01-31',
                'Pilih Status' => 'Staff',
            ]);

        $employeeNames = array_column($reportData['rows'] ?? [], 'Nama');

        $this->assertContains('Aulia', $employeeNames, 'Row with populated LastModifiedBy should be included');
    }

    private function xmlWithEmptyModifiedBy(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Aulia</Full_x0020_Name>
        <Department_x0020_Name>Finance</Department_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-01-15T00:00:00+07:00</Date>
        <Last_x0020_Modified_x0020_By></Last_x0020_Modified_x0020_By>
    </Attendance>
</NewDataSet>
XML;
    }

    private function xmlWithPopulatedModifiedBy(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Aulia</Full_x0020_Name>
        <Department_x0020_Name>Finance</Department_x0020_Name>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Date>2026-01-15T00:00:00+07:00</Date>
        <Last_x0020_Modified_x0020_By>Ridho</Last_x0020_Modified_x0020_By>
    </Attendance>
</NewDataSet>
XML;
    }
}
