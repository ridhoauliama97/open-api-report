<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\DataPesertaMakanSiangIbadahAulaPerDepartemenReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsDataPesertaMakanSiangIbadahAulaPerDepartemenReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(DataPesertaMakanSiangIbadahAulaPerDepartemenReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['company'] ?? null) === 'RU'
                && ($filters['month'] ?? null) === '5'
                && ($filters['year'] ?? null) === '2026'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.data_peserta_makan_siang_ibadah_aula_per_departemen.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                && ($data['reportData']['title'] ?? null) === 'Data Peserta Penerima Makan Siang Ibadah Di Aula Per Departemen'
                && ($data['reportData']['printed_by'] ?? null) === 'Windi'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DataPesertaMakanSiangIbadahAulaPerDepartemenReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/data-peserta-makan-siang-ibadah-aula-per-departemen/pdf', [
            'DB_CompanyName' => 'RU',
            'Sys_Username' => 'Windi',
            'month' => '5',
            'year' => '2026',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Attendance Full - Data Peserta Penerima Makan Siang Ibadah Di Aula Per Departemen (RU)');
    }

    public function test_parser_groups_participants_by_department_page_and_friday_columns(): void
    {
        $reportData = app(DataPesertaMakanSiangIbadahAulaPerDepartemenReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'month' => 'Mei',
                'year' => '2026',
            ]);

        $this->assertSame('Per Mei 2026', $reportData['period']['label']);
        $this->assertSame(['1 (01-Mei-26)', '2 (08-Mei-26)', '3 (15-Mei-26)', '4 (22-Mei-26)', '5 (29-Mei-26)'], array_column($reportData['dates'], 'label'));
        $this->assertSame(3, $reportData['total_rows']);

        $departments = collect($reportData['departments'])->keyBy('department');
        $this->assertTrue($departments->has('PHI'));
        $this->assertTrue($departments->has('PHU 1'));

        $this->assertSame('Difa Alamsah', $departments['PHI']['pj_penerima']);
        $this->assertSame(['Alfian Josua Hamonangan Sitorus', 'Difa Alamsah'], array_column($departments['PHI']['rows'], 'Nama'));

        $this->assertSame('Yazuwar', $departments['PHU 1']['pj_penerima']);
        $this->assertSame(['Yazuwar Mendrofa'], array_column($departments['PHU 1']['rows'], 'Nama'));
    }

    public function test_parser_uses_gsu_department_formula_when_company_is_gsu(): void
    {
        $reportData = app(DataPesertaMakanSiangIbadahAulaPerDepartemenReportService::class)
            ->buildReportDataFromXml($this->gsuAttendanceXml(), 'test xml', [
                'company' => 'GSU',
                'month' => 'Mei',
                'year' => '2026',
            ]);

        $this->assertSame(6, $reportData['total_rows']);

        $departments = collect($reportData['departments'])->keyBy('department');
        $this->assertSame(['Broker Shift A / Shift B', 'Marketing & FA', 'PIN HULU / PIN HILIR', 'PIN HULU REGU A', 'WHS', 'WNB'], array_keys($departments->all()));

        $this->assertSame('EYS, SPY', $departments['WNB']['pj_penerima']);
        $this->assertSame('FLO', $departments['WHS']['pj_penerima']);
        $this->assertSame('Elisabeth', $departments['PIN HULU REGU A']['pj_penerima']);
        $this->assertSame('Elisabeth', $departments['PIN HULU / PIN HILIR']['pj_penerima']);

        $this->assertSame(['Broker User'], array_column($departments['Broker Shift A / Shift B']['rows'], 'Nama'));
        $this->assertSame(['alfisyah rizal'], array_column($departments['WNB']['rows'], 'Nama'));
        $this->assertSame(['Marketing User'], array_column($departments['Marketing & FA']['rows'], 'Nama'));
        $this->assertSame(['Gudang User'], array_column($departments['WHS']['rows'], 'Nama'));
        $this->assertSame(['Fitri Yanti Hu'], array_column($departments['PIN HULU REGU A']['rows'], 'Nama'));
        $this->assertSame(['Assembly User'], array_column($departments['PIN HULU / PIN HILIR']['rows'], 'Nama'));
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'title' => 'Data Peserta Penerima Makan Siang Ibadah Di Aula Per Departemen',
            'printed_by' => 'Windi',
            'period' => ['label' => 'Per Mei 2026'],
            'dates' => [
                ['index' => 1, 'date' => '2026-05-01', 'label' => '1 (01-Mei-26)'],
            ],
            'departments' => [],
            'headers' => ['No', 'Nama'],
            'rows' => [],
            'total_rows' => 0,
        ];
    }

    private function attendanceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>132095</Employee_x0020_Code>
        <Full_x0020_Name>Alfian Josua Hamonangan Sitorus</Full_x0020_Name>
        <Job_x0020_Title>Kru Rotary</Job_x0020_Title>
        <Department_x0020_Name>Produksi FJLB</Department_x0020_Name>
        <Division_x0020_Name>PHI</Division_x0020_Name>
        <Sub-Division_x0020_Name>Rotary</Sub-Division_x0020_Name>
        <Religion>Kristen</Religion>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Created_x0020_By>Windi</Created_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>132095</Employee_x0020_Code>
        <Full_x0020_Name>Alfian Josua Hamonangan Sitorus</Full_x0020_Name>
        <Job_x0020_Title>Kru Rotary</Job_x0020_Title>
        <Department_x0020_Name>Produksi FJLB</Department_x0020_Name>
        <Division_x0020_Name>PHI</Division_x0020_Name>
        <Sub-Division_x0020_Name>Rotary</Sub-Division_x0020_Name>
        <Religion>Kristen</Religion>
        <Date>2026-05-08T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>131339</Employee_x0020_Code>
        <Full_x0020_Name>Difa Alamsah</Full_x0020_Name>
        <Job_x0020_Title>Ka. Regu Produksi Hilir</Job_x0020_Title>
        <Department_x0020_Name>Produksi FJLB</Department_x0020_Name>
        <Division_x0020_Name>PHI</Division_x0020_Name>
        <Sub-Division_x0020_Name>Rotary</Sub-Division_x0020_Name>
        <Religion>Kristen</Religion>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>131154</Employee_x0020_Code>
        <Full_x0020_Name>Yazuwar Mendrofa</Full_x0020_Name>
        <Job_x0020_Title>Ka. Regu Produksi Hulu</Job_x0020_Title>
        <Department_x0020_Name>Produksi FJLB</Department_x0020_Name>
        <Division_x0020_Name>PHU</Division_x0020_Name>
        <Sub-Division_x0020_Name>S4S</Sub-Division_x0020_Name>
        <Religion>Kristen</Religion>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>SPECIAL001</Employee_x0020_Code>
        <Full_x0020_Name>Special User</Full_x0020_Name>
        <Job_x0020_Title>Ka. Div. Produksi Hulu</Job_x0020_Title>
        <Department_x0020_Name>Produksi FJLB</Department_x0020_Name>
        <Division_x0020_Name>PHU</Division_x0020_Name>
        <Sub-Division_x0020_Name>S4S</Sub-Division_x0020_Name>
        <Religion>Kristen</Religion>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
</NewDataSet>
XML;
    }

    private function gsuAttendanceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>120000</Employee_x0020_Code>
        <Full_x0020_Name>Broker User</Full_x0020_Name>
        <Job_x0020_Title>Operator Broker A</Job_x0020_Title>
        <Department_x0020_Name>Washing &amp; Broker</Department_x0020_Name>
        <Workgroup>Kary. Prod Ekstrusi Besar I</Workgroup>
        <Scheduled_x0020_Shift>Prod SHIFT III</Scheduled_x0020_Shift>
        <Religion>Kristen</Religion>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>alfisyah rizal</Full_x0020_Name>
        <Job_x0020_Title>Staff Gudang</Job_x0020_Title>
        <Department_x0020_Name>Gudang</Department_x0020_Name>
        <Workgroup>Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Normal Shift</Scheduled_x0020_Shift>
        <Religion>Kristen</Religion>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120002</Employee_x0020_Code>
        <Full_x0020_Name>Marketing User</Full_x0020_Name>
        <Job_x0020_Title>Sales Admin</Job_x0020_Title>
        <Department_x0020_Name>Marketing</Department_x0020_Name>
        <Workgroup>Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Normal Shift</Scheduled_x0020_Shift>
        <Religion>Kristen</Religion>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120003</Employee_x0020_Code>
        <Full_x0020_Name>Gudang User</Full_x0020_Name>
        <Job_x0020_Title>Kru Gudang</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Normal Shift</Scheduled_x0020_Shift>
        <Religion>Kristen</Religion>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120004</Employee_x0020_Code>
        <Full_x0020_Name>Fitri Yanti Hu</Full_x0020_Name>
        <Job_x0020_Title>Operator Produksi</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Produksi Regu I</Workgroup>
        <Scheduled_x0020_Shift>Shift I</Scheduled_x0020_Shift>
        <Religion>Kristen</Religion>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120005</Employee_x0020_Code>
        <Full_x0020_Name>Assembly User</Full_x0020_Name>
        <Job_x0020_Title>Operator Assembly</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Produksi Regu I</Workgroup>
        <Scheduled_x0020_Shift>Shift I</Scheduled_x0020_Shift>
        <Religion>Kristen</Religion>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
</NewDataSet>
XML;
    }
}
