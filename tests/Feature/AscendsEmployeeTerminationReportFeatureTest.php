<?php

namespace Tests\Feature;

use App\Services\Ascends\Shared\Hrm\EmployeeTerminationReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class AscendsEmployeeTerminationReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_shared_hrm_api_can_render_employee_termination_pdf(): void
    {
        $xml = $this->retirementXml();

        $service = Mockery::mock(EmployeeTerminationReportService::class);
        $service
            ->shouldReceive('buildReportDataFromXml')
            ->once()
            ->with($xml, 'request upload: retirement.xml', Mockery::on(
                static fn (array $filters): bool => ($filters['start_date'] ?? null) === '2026-05-01'
                && ($filters['end_date'] ?? null) === '2026-05-31'
            ))
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('ascends.shared.hrm.employee_termination.pdf', Mockery::on(
                static fn (array $data): bool => ($data['company'] ?? null) === 'RU'
                && ($data['reportData']['total_rows'] ?? null) === 3
                && ($data['reportData']['title'] ?? null) === 'Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar (RU)'
                && ($data['subtitle'] ?? null) === 'Periode : 01-Mei-26 s/d 31-Mei-26'
                && ($data['pdf_orientation'] ?? null) === 'portrait'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(EmployeeTerminationReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->post('/api/internal/ascends/shared/hrm/employee-termination/pdf', [
            'company' => 'RU',
            'Sys_Username' => 'Ridho',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'xml_file' => UploadedFile::fake()->createWithContent('retirement.xml', $xml),
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Employee Termination - Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar (RU)');
    }

    public function test_parser_builds_correct_employee_termination_data(): void
    {
        $reportData = app(EmployeeTerminationReportService::class)
            ->buildReportDataFromXml($this->retirementXml(), 'test xml', [
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-31',
            ]);

        $this->assertSame('Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar', $reportData['title']);
        $this->assertSame(9, $reportData['total_rows']);

        $headers = ['No', 'Nama', 'L/P', 'Jabatan', 'Status', 'Level', 'Tanggal Masuk', 'Tanggal Keluar', 'Masa Kerja', 'Alasan Keluar'];
        $this->assertSame($headers, $reportData['headers']);

        $this->assertCount(3, $reportData['grouped_rows']);

        $groupLabels = array_map(static fn (array $g): string => $g['label'], $reportData['grouped_rows']);
        sort($groupLabels);
        $this->assertSame(['Departemen : Produksi FJLB', 'Departemen : Sawmill', 'Departemen : Vacuum & K/D'], $groupLabels);

        foreach ($reportData['grouped_rows'] as $group) {
            $this->assertArrayHasKey('summary', $group);
            $this->assertArrayHasKey('rows', $group);
            $this->assertSame(count($group['rows']), $group['subtotal']);
        }

        $allRows = $reportData['rows'];
        $this->assertCount(9, $allRows);

        $firstRow = $allRows[0];
        $this->assertArrayHasKey('Nama', $firstRow);
        $this->assertArrayHasKey('L/P', $firstRow);
        $this->assertArrayHasKey('Jabatan', $firstRow);
        $this->assertArrayHasKey('Status', $firstRow);
        $this->assertArrayHasKey('Level', $firstRow);
        $this->assertArrayHasKey('Tanggal Masuk', $firstRow);
        $this->assertArrayHasKey('Tanggal Keluar', $firstRow);
        $this->assertArrayHasKey('Masa Kerja', $firstRow);
        $this->assertArrayHasKey('Alasan Keluar', $firstRow);

        $grandSummary = $reportData['grand_summary'];
        $this->assertSame(9, $grandSummary['subtotal']);

        $this->assertArrayHasKey('gender', $grandSummary);
        $this->assertSame(6, $grandSummary['gender']['L']['count']);
        $this->assertSame(3, $grandSummary['gender']['P']['count']);

        $this->assertArrayHasKey('status', $grandSummary);
        $this->assertCount(4, $grandSummary['status']);
        $this->assertSame('KK', $grandSummary['status'][1]['label']);
        $this->assertSame(9, $grandSummary['status'][1]['count']);
        $this->assertSame(100, $grandSummary['status'][1]['percent']);

        $this->assertArrayHasKey('level', $grandSummary);
        $this->assertCount(7, $grandSummary['level']);
        $this->assertSame('Level 1', $grandSummary['level'][0]['label']);
        $this->assertSame(9, $grandSummary['level'][0]['count']);
        $this->assertSame(100, $grandSummary['level'][0]['percent']);

        $this->assertArrayHasKey('period_label', $reportData);
        $this->assertSame('Periode : 01-Mei-26 s/d 31-Mei-26', $reportData['period_label']);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'title' => 'Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar (RU)',
            'source_file' => 'request upload: retirement.xml',
            'period_label' => 'Periode : 01-Mei-26 s/d 31-Mei-26',
            'printed_at' => '12 June 2026 14:15',
            'printed_by' => 'Ridho',
            'headers' => ['No', 'Nama', 'L/P', 'Jabatan', 'Status', 'Level', 'Tanggal Masuk', 'Tanggal Keluar', 'Masa Kerja', 'Alasan Keluar'],
            'rows' => [],
            'grouped_rows' => [],
            'grand_summary' => [
                'subtotal' => 3,
                'gender' => [],
                'status' => [
                    ['label' => 'BR', 'count' => 0, 'percent' => 0],
                    ['label' => 'KK', 'count' => 3, 'percent' => 100],
                    ['label' => 'KT', 'count' => 0, 'percent' => 0],
                    ['label' => 'ST', 'count' => 0, 'percent' => 0],
                ],
                'level' => array_map(static fn (int $i): array => [
                    'label' => 'Level '.$i,
                    'count' => $i === 1 ? 3 : 0,
                    'percent' => $i === 1 ? 100 : 0,
                ], range(1, 7)),
            ],
            'total_rows' => 3,
        ];
    }

    private function retirementXml(): string
    {
        return <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Employees>
    <Employee_x0020_Code>131606</Employee_x0020_Code>
    <Full_x0020_Name>Ari Azi</Full_x0020_Name>
    <Date_x0020_of_x0020_Join>2022-03-16T00:00:00+07:00</Date_x0020_of_x0020_Join>
    <Job_x0020_Title>Operator Rotary</Job_x0020_Title>
    <Department_x0020_Name>Produksi FJLB</Department_x0020_Name>
    <Level>1</Level>
    <Date>2026-05-05T00:00:00+07:00</Date>
    <Retirement_x0020_Reason>Dikeluarkan Dari Perusahaan</Retirement_x0020_Reason>
    <Status_x0020_Type>Contract</Status_x0020_Type>
    <Sex>Male</Sex>
  </Employees>
  <Employees>
    <Employee_x0020_Code>132043</Employee_x0020_Code>
    <Full_x0020_Name>Muhammad Ibnu Hadi</Full_x0020_Name>
    <Date_x0020_of_x0020_Join>2026-01-19T00:00:00+07:00</Date_x0020_of_x0020_Join>
    <Job_x0020_Title>Kru Cross Cut Awal</Job_x0020_Title>
    <Department_x0020_Name>Produksi FJLB</Department_x0020_Name>
    <Level>1</Level>
    <Date>2026-05-06T00:00:00+07:00</Date>
    <Retirement_x0020_Reason>Dikeluarkan Dari Perusahaan</Retirement_x0020_Reason>
    <Status_x0020_Type>Contract</Status_x0020_Type>
    <Sex>Male</Sex>
  </Employees>
  <Employees>
    <Employee_x0020_Code>132090</Employee_x0020_Code>
    <Full_x0020_Name>Frasisca Dewi</Full_x0020_Name>
    <Date_x0020_of_x0020_Join>2026-05-04T00:00:00+07:00</Date_x0020_of_x0020_Join>
    <Job_x0020_Title>Kru Grader Sawmill</Job_x0020_Title>
    <Department_x0020_Name>Sawmill</Department_x0020_Name>
    <Level>1</Level>
    <Date>2026-05-09T00:00:00+07:00</Date>
    <Retirement_x0020_Reason>Dikeluarkan Dari Perusahaan</Retirement_x0020_Reason>
    <Status_x0020_Type>Contract</Status_x0020_Type>
    <Sex>Female</Sex>
  </Employees>
  <Employees>
    <Employee_x0020_Code>132091</Employee_x0020_Code>
    <Full_x0020_Name>Pebriyanti Veronika Br Simatupang Siburian</Full_x0020_Name>
    <Date_x0020_of_x0020_Join>2026-05-07T00:00:00+07:00</Date_x0020_of_x0020_Join>
    <Job_x0020_Title>Kru Cross Cut Manual</Job_x0020_Title>
    <Department_x0020_Name>Produksi FJLB</Department_x0020_Name>
    <Level>1</Level>
    <Date>2026-05-11T00:00:00+07:00</Date>
    <Retirement_x0020_Reason>Dikeluarkan Dari Perusahaan</Retirement_x0020_Reason>
    <Status_x0020_Type>Contract</Status_x0020_Type>
    <Sex>Male</Sex>
  </Employees>
  <Employees>
    <Employee_x0020_Code>132066</Employee_x0020_Code>
    <Full_x0020_Name>Adi Putra Simbolon</Full_x0020_Name>
    <Date_x0020_of_x0020_Join>2026-04-01T00:00:00+07:00</Date_x0020_of_x0020_Join>
    <Job_x0020_Title>Kru Mesin SLP</Job_x0020_Title>
    <Department_x0020_Name>Sawmill</Department_x0020_Name>
    <Level>1</Level>
    <Date>2026-05-19T00:00:00+07:00</Date>
    <Retirement_x0020_Reason>Dikeluarkan Dari Perusahaan</Retirement_x0020_Reason>
    <Status_x0020_Type>Contract</Status_x0020_Type>
    <Sex>Male</Sex>
  </Employees>
  <Employees>
    <Employee_x0020_Code>131886</Employee_x0020_Code>
    <Full_x0020_Name>Linda Ridawani</Full_x0020_Name>
    <Date_x0020_of_x0020_Join>2024-10-14T00:00:00+07:00</Date_x0020_of_x0020_Join>
    <Job_x0020_Title>Kru Double Rip</Job_x0020_Title>
    <Department_x0020_Name>Produksi FJLB</Department_x0020_Name>
    <Level>1</Level>
    <Date>2026-05-22T00:00:00+07:00</Date>
    <Retirement_x0020_Reason>Mengundurkan diri</Retirement_x0020_Reason>
    <Status_x0020_Type>Contract</Status_x0020_Type>
    <Sex>Female</Sex>
  </Employees>
  <Employees>
    <Employee_x0020_Code>132098</Employee_x0020_Code>
    <Full_x0020_Name>Okky Bagaskara</Full_x0020_Name>
    <Date_x0020_of_x0020_Join>2026-05-19T00:00:00+07:00</Date_x0020_of_x0020_Join>
    <Job_x0020_Title>Kru Mesin SLP</Job_x0020_Title>
    <Department_x0020_Name>Sawmill</Department_x0020_Name>
    <Level>1</Level>
    <Date>2026-05-22T00:00:00+07:00</Date>
    <Retirement_x0020_Reason>Mengundurkan diri</Retirement_x0020_Reason>
    <Status_x0020_Type>Contract</Status_x0020_Type>
    <Sex>Male</Sex>
  </Employees>
  <Employees>
    <Employee_x0020_Code>132062</Employee_x0020_Code>
    <Full_x0020_Name>Daniel Siagian</Full_x0020_Name>
    <Date_x0020_of_x0020_Join>2026-02-24T00:00:00+07:00</Date_x0020_of_x0020_Join>
    <Job_x0020_Title>Kru KD</Job_x0020_Title>
    <Department_x0020_Name>Vacuum &amp; K/D</Department_x0020_Name>
    <Level>1</Level>
    <Date>2026-05-23T00:00:00+07:00</Date>
    <Retirement_x0020_Reason>Dikeluarkan Dari Perusahaan</Retirement_x0020_Reason>
    <Status_x0020_Type>Contract</Status_x0020_Type>
    <Sex>Male</Sex>
  </Employees>
  <Employees>
    <Employee_x0020_Code>132099</Employee_x0020_Code>
    <Full_x0020_Name>Anna Tasya Br Sitepu</Full_x0020_Name>
    <Date_x0020_of_x0020_Join>2026-05-19T00:00:00+07:00</Date_x0020_of_x0020_Join>
    <Job_x0020_Title>Kru Grader Sawmill</Job_x0020_Title>
    <Department_x0020_Name>Sawmill</Department_x0020_Name>
    <Level>1</Level>
    <Date>2026-05-29T00:00:00+07:00</Date>
    <Retirement_x0020_Reason>Mengundurkan diri</Retirement_x0020_Reason>
    <Status_x0020_Type>Contract</Status_x0020_Type>
    <Sex>Female</Sex>
  </Employees>
</NewDataSet>
XML;
    }
}
