<?php

namespace Tests\Feature;

use App\Services\Ascends\Ru\Hrm\UsiaGenerasiTahunKelahiranMasaKerjaReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsUsiaGenerasiTahunKelahiranMasaKerjaReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_hrm_api_can_render_uploaded_xml_as_pdf_without_jwt(): void
    {
        $xml = $this->employeeListXml();

        $service = Mockery::mock(UsiaGenerasiTahunKelahiranMasaKerjaReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: employee-list.xml')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.employee_list.usia_generasi_tahun_kelahiran_masa_kerja.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'UC'
                    && ($data['reportData']['title'] ?? null) === 'Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja (UC)'
                    && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(UsiaGenerasiTahunKelahiranMasaKerjaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/usia-generasi-tahun-kelahiran-masa-kerja/pdf', [
            'company' => 'UC',
            'xml_file' => UploadedFile::fake()->createWithContent('employee-list.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Employee List - Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja (UC)');
    }

    public function test_parser_groups_rows_by_generation_birth_year_and_builds_summary(): void
    {
        $reportData = app(UsiaGenerasiTahunKelahiranMasaKerjaReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $this->assertSame([
            'No',
            'Nama',
            'Jabatan',
            'Departemen',
            'Usia',
            'Masa Kerja',
        ], $reportData['headers']);
        $this->assertSame(4, $reportData['total_rows']);
        $this->assertSame('Generasi Baby Boomer', $reportData['grouped_rows'][0]['label']);
        $this->assertSame('Generasi X', $reportData['grouped_rows'][1]['label']);
        $this->assertSame('Generasi Milenial', $reportData['grouped_rows'][2]['label']);
        $this->assertSame('Generasi Z', $reportData['grouped_rows'][3]['label']);
        $this->assertSame('Rita Taniwan', $reportData['grouped_rows'][0]['rows'][0]['Nama']);
        $this->assertSame('7 Thn 2 bln', $reportData['grouped_rows'][0]['rows'][0]['Masa Kerja']);
        $this->assertSame(1, $reportData['grouped_rows'][0]['subtotal']);
        $this->assertSame('25.0%', $reportData['grouped_rows'][0]['percent']);
        $this->assertSame(1, $reportData['generation_summary'][0]['count']);
        $this->assertSame('25.0%', $reportData['generation_summary'][0]['percent']);
        $this->assertStringNotContainsString('Special User', json_encode($reportData['grouped_rows']));
        $this->assertStringNotContainsString('Non Aktif', json_encode($reportData['grouped_rows']));
    }

    public function test_pdf_blade_renders_expected_headers_groups_and_summary(): void
    {
        $reportData = app(UsiaGenerasiTahunKelahiranMasaKerjaReportService::class)
            ->buildReportDataFromXml($this->employeeListXml('employees'), 'test xml');

        $html = view('ascends.shared.hrm.employee_list.usia_generasi_tahun_kelahiran_masa_kerja.pdf', [
            'company' => 'UC',
            'reportData' => $reportData,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja', $html);
        $this->assertStringContainsString('Per Tanggal', $html);
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('Jabatan', $html);
        $this->assertStringContainsString('Departemen', $html);
        $this->assertStringContainsString('Usia', $html);
        $this->assertStringContainsString('Masa Kerja', $html);
        $this->assertStringContainsString('Generasi : Baby Boomer', $html);
        $this->assertStringContainsString('7 Thn 2 bln', $html);
        $this->assertStringContainsString('Jumlah Generasi Baby Boomer', $html);
        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringContainsString('100.0%', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'printed_at' => '02 June 2026 15:49',
            'printed_by' => 'Ridho',
            'company' => 'UC',
            'title' => 'Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja (UC)',
            'headers' => [
                'No',
                'Nama',
                'Jabatan',
                'Departemen',
                'Usia',
                'Masa Kerja',
            ],
            'grouped_rows' => [],
            'generation_summary' => [],
            'total_rows' => 4,
        ];
    }

    private function employeeListXml(string $recordTag = 'Employees'): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
    <{$recordTag}>
        <Employee_x0020_Code>1001</Employee_x0020_Code>
        <Full_x0020_Name>Rita Taniwan</Full_x0020_Name>
        <Job_x0020_Title>CEO</Job_x0020_Title>
        <Department_x0020_Name>Management</Department_x0020_Name>
        <Age>72</Age>
        <Birth_x0020_Date>1954-01-01T00:00:00+07:00</Birth_x0020_Date>
        <Working_x0020_Years>7</Working_x0020_Years>
        <Working_x0020_Months>2</Working_x0020_Months>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1002</Employee_x0020_Code>
        <Full_x0020_Name>Sri Setiawati</Full_x0020_Name>
        <Job_x0020_Title>Staff Tax</Job_x0020_Title>
        <Department_x0020_Name>Tax</Department_x0020_Name>
        <Age>61</Age>
        <Birth_x0020_Date>1965-03-01T00:00:00+07:00</Birth_x0020_Date>
        <Working_x0020_Years>41</Working_x0020_Years>
        <Working_x0020_Months>1</Working_x0020_Months>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1003</Employee_x0020_Code>
        <Full_x0020_Name>Anwar Afandi</Full_x0020_Name>
        <Job_x0020_Title>Operator Mould Maker</Job_x0020_Title>
        <Department_x0020_Name>Maintenance Inject</Department_x0020_Name>
        <Age>42</Age>
        <Birth_x0020_Date>1984-06-01T00:00:00+07:00</Birth_x0020_Date>
        <Working_x0020_Years>2</Working_x0020_Years>
        <Working_x0020_Months>1</Working_x0020_Months>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1004</Employee_x0020_Code>
        <Full_x0020_Name>Ridho Aulia Mahqomah Angkat</Full_x0020_Name>
        <Job_x0020_Title>Staff Database Reporting</Job_x0020_Title>
        <Department_x0020_Name>IS Programmer</Department_x0020_Name>
        <Age>29</Age>
        <Birth_x0020_Date>1997-01-01T00:00:00+07:00</Birth_x0020_Date>
        <Working_x0020_Years>0</Working_x0020_Years>
        <Working_x0020_Months>4</Working_x0020_Months>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>SPECIAL 1</Employee_x0020_Code>
        <Full_x0020_Name>Special User</Full_x0020_Name>
        <Job_x0020_Title>Special</Job_x0020_Title>
        <Department_x0020_Name>Management</Department_x0020_Name>
        <Age>28</Age>
        <Birth_x0020_Date>1998-01-01T00:00:00+07:00</Birth_x0020_Date>
        <Working_x0020_Years>1</Working_x0020_Years>
        <Working_x0020_Months>1</Working_x0020_Months>
        <Active>Active</Active>
    </{$recordTag}>
    <{$recordTag}>
        <Employee_x0020_Code>1006</Employee_x0020_Code>
        <Full_x0020_Name>Non Aktif</Full_x0020_Name>
        <Job_x0020_Title>Staff</Job_x0020_Title>
        <Department_x0020_Name>HR</Department_x0020_Name>
        <Age>25</Age>
        <Birth_x0020_Date>2001-01-01T00:00:00+07:00</Birth_x0020_Date>
        <Working_x0020_Years>1</Working_x0020_Years>
        <Working_x0020_Months>1</Working_x0020_Months>
        <Active>Terminated</Active>
    </{$recordTag}>
</NewDataSet>
XML;
    }
}
