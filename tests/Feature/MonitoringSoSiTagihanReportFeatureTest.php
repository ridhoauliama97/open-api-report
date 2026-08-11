<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ascends\Shared\CustomReport\MonitoringSoSiTagihanReportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MonitoringSoSiTagihanReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <CustomerName>SHOPEE</CustomerName>
    <SOID>288675</SOID>
    <SONumber>SO/07/26/0001</SONumber>
    <SODate>2026-07-01T00:00:00+07:00</SODate>
    <InvoiceID>299930</InvoiceID>
    <InvoiceNumber>SI/07/26/0001</InvoiceNumber>
    <InvoiceDate>2026-07-01T00:00:00+07:00</InvoiceDate>
    <DateV>2026-07-22T00:00:00+07:00</DateV>
    <Ket>Lunas</Ket>
    <SO-SI>0</SO-SI>
    <SI-TGH>21</SI-TGH>
  </Table>
  <Table>
    <CustomerName>SHOPEE</CustomerName>
    <SOID>288676</SOID>
    <SONumber>SO/07/26/0002</SONumber>
    <SODate>2026-07-01T00:00:00+07:00</SODate>
    <InvoiceID>299931</InvoiceID>
    <InvoiceNumber>SI/07/26/0002</InvoiceNumber>
    <InvoiceDate>2026-07-01T00:00:00+07:00</InvoiceDate>
    <DateV>2026-07-15T00:00:00+07:00</DateV>
    <Ket>Lunas</Ket>
    <SO-SI>0</SO-SI>
    <SI-TGH>14</SI-TGH>
  </Table>
  <Table>
    <CustomerName>TIKTOK</CustomerName>
    <SOID>288694</SOID>
    <SONumber>SO/07/26/0019</SONumber>
    <SODate>2026-07-01T00:00:00+07:00</SODate>
    <InvoiceID>299948</InvoiceID>
    <InvoiceNumber>SI/07/26/0019</InvoiceNumber>
    <InvoiceDate>2026-07-01T00:00:00+07:00</InvoiceDate>
    <Ket>Belum Lunas</Ket>
    <SO-SI>0</SO-SI>
  </Table>
  <Table>
    <CustomerName>TIKTOK</CustomerName>
    <SOID>288695</SOID>
    <SONumber>SO/07/26/0020</SONumber>
    <SODate>2026-06-30T00:00:00+07:00</SODate>
    <InvoiceID>299949</InvoiceID>
    <InvoiceNumber>SI/07/26/0020</InvoiceNumber>
    <InvoiceDate>2026-06-30T00:00:00+07:00</InvoiceDate>
    <Ket>Belum Lunas</Ket>
    <SO-SI>0</SO-SI>
  </Table>
</NewDataSet>
XML;

    public function test_monitoring_so_si_tagihan_pdf_download_returns_pdf(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('MonitoringSoSiTagihan.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/monitoring-so-si-tagihan/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_service_filters_by_so_date_range_and_computes_formulas(): void
    {
        $service = new MonitoringSoSiTagihanReportService;

        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'fixture', [
            'company' => 'GSU',
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
            'PrintDate' => '2026-08-04',
        ]);

        $this->assertSame('Laporan Monitoring SO - SI - Tagihan', $reportData['title']);
        $this->assertSame('GSU', $reportData['company']);
        $this->assertSame('Ridho', $reportData['printed_by']);
        $this->assertSame('01-Jul-26', $reportData['start_date']);
        $this->assertSame('31-Jul-26', $reportData['end_date']);
        $this->assertSame('Dari 01-Jul-26 s/d 31-Jul-26', $reportData['period_label']);

        // SO/07/26/0020 has SODate 2026-06-30 -> filtered out
        $this->assertCount(3, $reportData['rows']);
        $this->assertSame(3, $reportData['total_rows']);

        $rows = $reportData['rows'];

        $this->assertSame('SHOPEE', $rows[0]['customer_name']);
        $this->assertSame('SO/07/26/0001', $rows[0]['so_number']);
        $this->assertSame('01-Jul-26', $rows[0]['so_date']);
        $this->assertSame('SI/07/26/0001', $rows[0]['invoice_number']);
        $this->assertSame('01-Jul-26', $rows[0]['inv_date']);
        $this->assertSame('0', $rows[0]['so_ke_si']);
        $this->assertSame('22-Jul-26', $rows[0]['date_pelunas']);
        $this->assertSame('21', $rows[0]['si_ke_tgh']);
        $this->assertSame('Yes', $rows[0]['lunas']);

        $this->assertSame('SO/07/26/0002', $rows[1]['so_number']);
        $this->assertSame('15-Jul-26', $rows[1]['date_pelunas']);
        $this->assertSame('14', $rows[1]['si_ke_tgh']);
        $this->assertSame('Yes', $rows[1]['lunas']);

        // SI-TGH null -> printdate - InvoiceDate = 04/08 - 01/07 = 34 days
        $this->assertSame('TIKTOK', $rows[2]['customer_name']);
        $this->assertSame('SO/07/26/0019', $rows[2]['so_number']);
        $this->assertSame('', $rows[2]['date_pelunas']);
        $this->assertSame('34', $rows[2]['si_ke_tgh']);
        $this->assertSame('No', $rows[2]['lunas']);
    }

    public function test_endpoint_resolves_alternative_alias_keys(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('MonitoringSoSiTagihan.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/monitoring-so-si-tagihan/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'sys_username' => 'AlternativeUser',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $response->assertOk();
    }
}
