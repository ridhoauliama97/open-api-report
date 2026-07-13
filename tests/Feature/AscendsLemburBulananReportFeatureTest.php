<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\LemburBulananReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsLemburBulananReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_overtime_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->overtimeXml();

        $service = Mockery::mock(LemburBulananReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: overtime.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['company'] ?? null) === 'RU'
                && ($filters['Pilih Tipe'] ?? null) === 'Staff'
                && ($filters['start_date'] ?? null) === '2026-05-01'
                && ($filters['end_date'] ?? null) === '2026-05-31'
            ))
            ->andReturn($this->reportData('RU', 'ST'));

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.overtime.lembur_bulanan.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                && ($data['reportData']['title'] ?? null) === 'Laporan Lembur Bulanan (ST) Per Departemen'
                && ($data['reportData']['printed_by'] ?? null) === 'Windi'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(LemburBulananReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/overtime/lembur-bulanan/pdf', [
            'DB_CompanyName' => 'RU',
            'Sys_Username' => 'Windi',
            'Pilih Tipe' => 'Staff',
            'StartDate' => '2026-05-01',
            'EndDate' => '2026-05-31',
            'xml_file' => UploadedFile::fake()->createWithContent('overtime.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Overtime - Laporan Lembur Bulanan ST Per Departemen (RU)');
    }

    public function test_parser_filters_staff_overtime_and_summarizes_employee_rows(): void
    {
        $reportData = app(LemburBulananReportService::class)
            ->buildReportDataFromXml($this->overtimeXml(), 'test xml', [
                'Pilih Tipe' => 'Staff',
            ]);

        $this->assertSame('ST', $reportData['type']);
        $this->assertSame(1, $reportData['total_rows']);
        $this->assertSame('Karyawan Staff', $reportData['rows'][0]['Nama']);
        $this->assertSame('P', $reportData['rows'][0]['L/P']);
        $this->assertSame('18', $reportData['rows'][0]['Jam']);
        $this->assertSame(2, $reportData['rows'][0]['Total Hari']);
        $this->assertSame('0', $reportData['rows'][0]['Total Lemburan']);
        $this->assertSame('0.0%', $reportData['rows'][0]['%']);
    }

    public function test_parser_filters_kk_kt_and_br_overtime(): void
    {
        $reportData = app(LemburBulananReportService::class)
            ->buildReportDataFromXml($this->overtimeXml(), 'test xml', [
                'Pilih Tipe' => 'KK/KT',
            ]);

        $names = array_column($reportData['rows'], 'Nama');

        $this->assertSame('KK/KT', $reportData['type']);
        $this->assertSame(2, $reportData['total_rows']);
        $this->assertContains('Karyawan KK', $names);
        $this->assertContains('Karyawan BR', $names);
        $this->assertNotContains('Karyawan Staff', $names);
        $this->assertSame(2, $reportData['grand_summary']['subtotal']);
    }

    public function test_parser_uses_full_month_period_from_table1_when_dates_are_not_sent(): void
    {
        $reportData = app(LemburBulananReportService::class)
            ->buildReportDataFromXml($this->overtimeXml(), 'test xml', [
                'Pilih Tipe' => 'Staff',
            ]);

        $this->assertSame('2026-05-01', $reportData['period']['start_date']);
        $this->assertSame('2026-05-31', $reportData['period']['end_date']);
        $this->assertSame('Dari 01/05/2026 Sampai 31/05/2026', $reportData['period']['label']);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $company, string $type): array
    {
        return [
            'printed_at' => '31 May 2026 13:26',
            'printed_by' => 'Ridho',
            'company' => $company,
            'type' => $type,
            'title' => "Laporan Lembur Bulanan ({$type}) Per Departemen",
            'headers' => ['Nama', 'L/P', 'Jabatan', 'Jam', 'Total Hari', 'Total Lemburan', '%'],
            'rows' => [],
            'grouped_rows' => [],
            'grand_summary' => ['subtotal' => 0, 'department_totals' => []],
            'total_rows' => 0,
        ];
    }

    private function overtimeXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Overtime>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Staff</Full_x0020_Name>
        <Department_x0020_Name>HRGA</Department_x0020_Name>
        <Department_x0020_Code>800</Department_x0020_Code>
        <Job_x0020_Title>Staff HR</Job_x0020_Title>
        <Level>2</Level>
        <Sex>Female</Sex>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-02T08:00:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-02T18:00:00+07:00</Sign_x0020_Out>
        <Original_x0020_Hours>10</Original_x0020_Hours>
        <Overtime_x002F_Hours>0</Overtime_x002F_Hours>
        <Overtime_x0020_Hours_x0020__x0028_Early_x0029_>1</Overtime_x0020_Hours_x0020__x0028_Early_x0029_>
        <Overtime_x0020_Hours_x0020__x0028_Break_x0029_>0.5</Overtime_x0020_Hours_x0020__x0028_Break_x0029_>
        <Overtime_x0020_Hours_x0020__x0028_Out_x0029_>1</Overtime_x0020_Hours_x0020__x0028_Out_x0029_>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
        <Created_x0020_By>Admin</Created_x0020_By>
    </Overtime>
    <Overtime>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Staff</Full_x0020_Name>
        <Department_x0020_Name>HRGA</Department_x0020_Name>
        <Department_x0020_Code>800</Department_x0020_Code>
        <Job_x0020_Title>Staff HR</Job_x0020_Title>
        <Level>2</Level>
        <Sex>Female</Sex>
        <Date>2026-05-03T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-03T08:00:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-03T19:00:00+07:00</Sign_x0020_Out>
        <Original_x0020_Hours>8</Original_x0020_Hours>
        <Overtime_x002F_Hours>0</Overtime_x002F_Hours>
        <Overtime_x0020_Hours_x0020__x0028_Early_x0029_>0</Overtime_x0020_Hours_x0020__x0028_Early_x0029_>
        <Overtime_x0020_Hours_x0020__x0028_Break_x0029_>0</Overtime_x0020_Hours_x0020__x0028_Break_x0029_>
        <Overtime_x0020_Hours_x0020__x0028_Out_x0029_>1</Overtime_x0020_Hours_x0020__x0028_Out_x0029_>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>ST</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Overtime>
    <Overtime>
        <Employee_x0020_Code>130002</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan KK</Full_x0020_Name>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Department_x0020_Code>1000</Department_x0020_Code>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Level>1</Level>
        <Sex>Male</Sex>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Sign_x0020_In>2026-05-02T08:00:00+07:00</Sign_x0020_In>
        <Sign_x0020_Out>2026-05-02T20:00:00+07:00</Sign_x0020_Out>
        <Original_x0020_Hours>8</Original_x0020_Hours>
        <Overtime_x002F_Hours>0</Overtime_x002F_Hours>
        <Overtime_x0020_Hours_x0020__x0028_Early_x0029_>0</Overtime_x0020_Hours_x0020__x0028_Early_x0029_>
        <Overtime_x0020_Hours_x0020__x0028_Break_x0029_>1</Overtime_x0020_Hours_x0020__x0028_Break_x0029_>
        <Overtime_x0020_Hours_x0020__x0028_Out_x0029_>3</Overtime_x0020_Hours_x0020__x0028_Out_x0029_>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Overtime>
    <Overtime>
        <Employee_x0020_Code>130003</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan BR</Full_x0020_Name>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Department_x0020_Code>1000</Department_x0020_Code>
        <Job_x0020_Title>Operator Borongan</Job_x0020_Title>
        <Level>1</Level>
        <Sex>Male</Sex>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Original_x0020_Hours>7</Original_x0020_Hours>
        <Overtime_x002F_Hours>0</Overtime_x002F_Hours>
        <Overtime_x0020_Hours_x0020__x0028_Early_x0029_>0</Overtime_x0020_Hours_x0020__x0028_Early_x0029_>
        <Overtime_x0020_Hours_x0020__x0028_Break_x0029_>0</Overtime_x0020_Hours_x0020__x0028_Break_x0029_>
        <Overtime_x0020_Hours_x0020__x0028_Out_x0029_>2</Overtime_x0020_Hours_x0020__x0028_Out_x0029_>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>BR</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Overtime>
    <Overtime>
        <Employee_x0020_Code>130004</Employee_x0020_Code>
        <Full_x0020_Name>Karyawan Nol</Full_x0020_Name>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Date>2026-05-02T00:00:00+07:00</Date>
        <Original_x0020_Hours>0</Original_x0020_Hours>
        <Overtime_x002F_Hours>0</Overtime_x002F_Hours>
        <Overtime_x0020_Hours_x0020__x0028_Early_x0029_>0</Overtime_x0020_Hours_x0020__x0028_Early_x0029_>
        <Overtime_x0020_Hours_x0020__x0028_Break_x0029_>0</Overtime_x0020_Hours_x0020__x0028_Break_x0029_>
        <Overtime_x0020_Hours_x0020__x0028_Out_x0029_>0</Overtime_x0020_Hours_x0020__x0028_Out_x0029_>
        <Daily_x0020_Worker_x0020_Type_x0020_Code>KK</Daily_x0020_Worker_x0020_Type_x0020_Code>
    </Overtime>
    <Table1>
        <Tag>0</Tag>
        <FirstPeriod>202605</FirstPeriod>
        <LastPeriod>202605</LastPeriod>
    </Table1>
</NewDataSet>
XML;
    }
}
