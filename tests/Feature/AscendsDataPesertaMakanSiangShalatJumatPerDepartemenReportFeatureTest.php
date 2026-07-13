<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\DataPesertaMakanSiangShalatJumatPerDepartemenReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsDataPesertaMakanSiangShalatJumatPerDepartemenReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_attendance_full_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->attendanceXml();

        $service = Mockery::mock(DataPesertaMakanSiangShalatJumatPerDepartemenReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: attendance.xml', Mockery::on(
                static fn(array $filters): bool => ($filters['company'] ?? null) === 'GSU'
                && ($filters['month'] ?? null) === '5'
                && ($filters['year'] ?? null) === '2026'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.attendance_full.data_peserta_makan_siang_shalat_jumat_per_departemen.pdf', Mockery::on(
                static fn(array $data): bool => ($data['company'] ?? null) === 'GSU'
                && ($data['reportData']['title'] ?? null) === 'Laporan Data Peserta Penerima Makan Siang Shalat Jumat Per Departemen'
                && ($data['reportData']['printed_by'] ?? null) === 'Ridho'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DataPesertaMakanSiangShalatJumatPerDepartemenReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/attendance-full/data-peserta-makan-siang-shalat-jumat-per-departemen/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'month' => '5',
            'year' => '2026',
            'xml_file' => UploadedFile::fake()->createWithContent('attendance.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Attendance Full - Laporan Data Peserta Penerima Makan Siang Shalat Jumat Per Departemen (GSU)');
    }

    public function test_parser_uses_gsu_shalat_jumat_formula(): void
    {
        $reportData = app(DataPesertaMakanSiangShalatJumatPerDepartemenReportService::class)
            ->buildReportDataFromXml($this->attendanceXml(), 'test xml', [
                'company' => 'GSU',
                'month' => 'Mei',
                'year' => '2026',
            ]);

        $this->assertSame('Per Mei 2026', $reportData['period']['label']);
        $this->assertSame(
            ['1 (01-Mei-26)', '2 (08-Mei-26)', '3 (15-Mei-26)', '4 (22-Mei-26)', '5 (29-Mei-26)'],
            array_column($reportData['dates'], 'label')
        );
        $this->assertSame(6, $reportData['total_rows']);

        $departments = collect($reportData['departments'])->keyBy('department');
        $this->assertSame(['WNB', 'WHS', 'Broker Kecil & Besar', 'Regu C', 'PIN HULU', 'PIN HILIR'], array_keys($departments->all()));

        $this->assertSame('SUM', $departments['WNB']['pj_penerima']);
        $this->assertSame('Eko Herianto', $departments['WHS']['pj_penerima']);
        $this->assertSame('Marisa', $departments['PIN HULU']['pj_penerima']);
        $this->assertSame('Marisa', $departments['PIN HILIR']['pj_penerima']);

        $this->assertSame(['alfisyah rizal'], array_column($departments['WNB']['rows'], 'Nama'));
        $this->assertSame(['Supir Gudang User'], array_column($departments['WHS']['rows'], 'Nama'));
        $this->assertSame(['Broker User'], array_column($departments['Broker Kecil & Besar']['rows'], 'Nama'));
        $this->assertSame(['Jansen Mard'], array_column($departments['Regu C']['rows'], 'Nama'));
        $this->assertSame(['Santi Eti'], array_column($departments['PIN HULU']['rows'], 'Nama'));
        $this->assertSame(['Buchari'], array_column($departments['PIN HILIR']['rows'], 'Nama'));
    }

    public function test_parser_uses_ru_shalat_jumat_formula(): void
    {
        $reportData = app(DataPesertaMakanSiangShalatJumatPerDepartemenReportService::class)
            ->buildReportDataFromXml($this->ruAttendanceXml(), 'test xml', [
                'company' => 'RU',
                'month' => 'Mei',
                'year' => '2026',
            ]);

        $this->assertSame(5, $reportData['total_rows']);

        $departments = collect($reportData['departments'])->keyBy('department');
        $this->assertSame(['PKB & SML', 'VKD', 'Borongan', 'PHI', 'PHU & KRUT'], array_keys($departments->all()));

        $this->assertSame('Rafi Prawira & SFD', $departments['PKB & SML']['pj_penerima']);
        $this->assertSame('SRO & Taufik Subiakto', $departments['VKD']['pj_penerima']);
        $this->assertSame('', $departments['Borongan']['pj_penerima']);
        $this->assertSame('Edi Sutoyo', $departments['PHI']['pj_penerima']);
        $this->assertSame('RZA', $departments['PHU & KRUT']['pj_penerima']);

        $this->assertSame(['Rafi Prawira'], array_column($departments['PKB & SML']['rows'], 'Nama'));
        $this->assertSame(['Muhammad Ridho'], array_column($departments['VKD']['rows'], 'Nama'));
        $this->assertSame(['Benjamin'], array_column($departments['Borongan']['rows'], 'Nama'));
        $this->assertSame(['Kru Sanding User'], array_column($departments['PHI']['rows'], 'Nama'));
        $this->assertSame(['Operator Table Saw User'], array_column($departments['PHU & KRUT']['rows'], 'Nama'));
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'title' => 'Laporan Data Peserta Penerima Makan Siang Shalat Jumat Per Departemen',
            'printed_by' => 'Ridho',
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
        <Employee_x0020_Code>120001</Employee_x0020_Code>
        <Full_x0020_Name>alfisyah rizal</Full_x0020_Name>
        <Job_x0020_Title>Staff Gudang</Job_x0020_Title>
        <Department_x0020_Name>Gudang</Department_x0020_Name>
        <Workgroup>Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Normal Shift</Scheduled_x0020_Shift>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
        <Created_x0020_By>Ridho</Created_x0020_By>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120002</Employee_x0020_Code>
        <Full_x0020_Name>Supir Gudang User</Full_x0020_Name>
        <Job_x0020_Title>Supir Gudang</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Normal Shift</Scheduled_x0020_Shift>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120003</Employee_x0020_Code>
        <Full_x0020_Name>Broker User</Full_x0020_Name>
        <Job_x0020_Title>Kru Ekstrusi BB Grup</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Ekstrusi Besar</Workgroup>
        <Scheduled_x0020_Shift>Shift I</Scheduled_x0020_Shift>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120004</Employee_x0020_Code>
        <Full_x0020_Name>Jansen Mard</Full_x0020_Name>
        <Job_x0020_Title>Operator Produksi</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Produksi Regu III</Workgroup>
        <Scheduled_x0020_Shift>Shift III</Scheduled_x0020_Shift>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120005</Employee_x0020_Code>
        <Full_x0020_Name>Santi Eti</Full_x0020_Name>
        <Job_x0020_Title>Kru Hot Stamping</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Produksi Regu I</Workgroup>
        <Scheduled_x0020_Shift>Shift I</Scheduled_x0020_Shift>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120006</Employee_x0020_Code>
        <Full_x0020_Name>Buchari</Full_x0020_Name>
        <Job_x0020_Title>Regu Assembly</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Produksi Regu I</Workgroup>
        <Scheduled_x0020_Shift>Shift I</Scheduled_x0020_Shift>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120007</Employee_x0020_Code>
        <Full_x0020_Name>Female Muslim</Full_x0020_Name>
        <Job_x0020_Title>Operator Bahan Awal</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Normal Shift</Scheduled_x0020_Shift>
        <Religion>Islam</Religion>
        <Sex>Female</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>120008</Employee_x0020_Code>
        <Full_x0020_Name>Christian Male</Full_x0020_Name>
        <Job_x0020_Title>Operator Bahan Awal</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Normal Shift</Workgroup>
        <Scheduled_x0020_Shift>Normal Shift</Scheduled_x0020_Shift>
        <Religion>Kristen</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
</NewDataSet>
XML;
    }

    private function ruAttendanceXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <Attendance>
        <Employee_x0020_Code>130001</Employee_x0020_Code>
        <Full_x0020_Name>Rafi Prawira</Full_x0020_Name>
        <Job_x0020_Title>Staff Sawmill</Job_x0020_Title>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Workgroup>Normal</Workgroup>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130002</Employee_x0020_Code>
        <Full_x0020_Name>Muhammad Ridho</Full_x0020_Name>
        <Job_x0020_Title>Staff IT</Job_x0020_Title>
        <Department_x0020_Name>HRGA</Department_x0020_Name>
        <Workgroup>Normal</Workgroup>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130003</Employee_x0020_Code>
        <Full_x0020_Name>Benjamin</Full_x0020_Name>
        <Job_x0020_Title>Operator</Job_x0020_Title>
        <Department_x0020_Name>Produksi</Department_x0020_Name>
        <Workgroup>Normal</Workgroup>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130004</Employee_x0020_Code>
        <Full_x0020_Name>Kru Sanding User</Full_x0020_Name>
        <Job_x0020_Title>Kru Sanding</Job_x0020_Title>
        <Department_x0020_Name>Produksi Akhir</Department_x0020_Name>
        <Workgroup>Normal</Workgroup>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130005</Employee_x0020_Code>
        <Full_x0020_Name>Operator Table Saw User</Full_x0020_Name>
        <Job_x0020_Title>Operator Table Saw</Job_x0020_Title>
        <Department_x0020_Name>Produksi Hulu</Department_x0020_Name>
        <Workgroup>Normal</Workgroup>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Friday</Day>
        <Date>2026-05-01T00:00:00+07:00</Date>
    </Attendance>
    <Attendance>
        <Employee_x0020_Code>130006</Employee_x0020_Code>
        <Full_x0020_Name>Non Friday User</Full_x0020_Name>
        <Job_x0020_Title>Staff Sawmill</Job_x0020_Title>
        <Department_x0020_Name>Sawmill</Department_x0020_Name>
        <Workgroup>Normal</Workgroup>
        <Religion>Islam</Religion>
        <Sex>Male</Sex>
        <Day>Thursday</Day>
        <Date>2026-05-07T00:00:00+07:00</Date>
    </Attendance>
</NewDataSet>
XML;
    }
}
