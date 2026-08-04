<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ascends\Shared\CustomReport\CustomerOverLimitReportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CustomerOverLimitReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <CustomerName>SIMPO FURNITURE</CustomerName>
    <CreditLimit>65000000</CreditLimit>
    <InvoiceNumber>SI/07/26/0001</InvoiceNumber>
    <InvoiceDate>2026-07-01T00:00:00+07:00</InvoiceDate>
    <LamaHari>15</LamaHari>
    <Ket>Belum Lunas</Ket>
    <Total>1173900</Total>
  </Table>
  <Table>
    <CustomerName>AP</CustomerName>
    <CreditLimit>2000000000</CreditLimit>
    <InvoiceNumber>SI/07/26/0002</InvoiceNumber>
    <InvoiceDate>2026-07-01T00:00:00+07:00</InvoiceDate>
    <LamaHari>45</LamaHari>
    <Ket>Belum Lunas</Ket>
    <Total>137106070</Total>
  </Table>
  <Table>
    <CustomerName>AP</CustomerName>
    <CreditLimit>2000000000</CreditLimit>
    <InvoiceNumber>SI/07/26/0003</InvoiceNumber>
    <InvoiceDate>2026-08-04T00:00:00+07:00</InvoiceDate>
    <LamaHari>0</LamaHari>
    <Ket>Lunas</Ket>
    <Total>7321140</Total>
  </Table>
  <Table>
    <CustomerName>AP</CustomerName>
    <CreditLimit>2000000000</CreditLimit>
    <InvoiceNumber>SI/07/26/0004</InvoiceNumber>
    <InvoiceDate>2026-07-02T00:00:00+07:00</InvoiceDate>
    <LamaHari>115</LamaHari>
    <Ket>Belum Lunas</Ket>
    <Total>1893189901.5</Total>
  </Table>
  <Table>
    <CustomerName>SIMPO FURNITURE</CustomerName>
    <CreditLimit>65000000</CreditLimit>
    <InvoiceNumber>SI/07/26/0005</InvoiceNumber>
    <InvoiceDate>2026-07-03T00:00:00+07:00</InvoiceDate>
    <LamaHari>120</LamaHari>
    <Ket>Belum Lunas</Ket>
    <Total>187167000</Total>
  </Table>
</NewDataSet>
XML;

    public function test_customer_over_limit_pdf_download_returns_pdf(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('CustomerOverLimit.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/customer-over-limit/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertPdfDisposition($response, 'attachment', 'Laporan Customer Over Limit');
    }

    public function test_service_groups_by_customer_and_computes_buckets_and_totals(): void
    {
        $service = new CustomerOverLimitReportService;

        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'fixture', [
            'company' => 'GSU',
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
        ]);

        $this->assertSame('Laporan Customer Over Limit', $reportData['title']);
        $this->assertSame('GSU', $reportData['company']);
        $this->assertSame('Ridho', $reportData['printed_by']);
        $this->assertSame('04-Agt-26', $reportData['as_of_date']);
        $this->assertSame('Per Tanggal : 04-Agt-26', $reportData['period_label']);

        // AP then SIMPO FURNITURE (alphabetical)
        $this->assertSame(['AP', 'SIMPO FURNITURE'], array_column($reportData['rows'], 'customer_name'));

        $ap = $reportData['rows'][0];
        $simpo = $reportData['rows'][1];

        // AP: 45 hari -> 31-60; 115 hari -> over90; 0 hari -> tagihan only
        $this->assertSame(2000000000.0, $ap['credit_limit']);
        $this->assertSame(0.0, $ap['b1_30']);
        $this->assertSame(137106070.0, $ap['b31_60']);
        $this->assertSame(0.0, $ap['b61_90']);
        $this->assertSame(1893189901.5, $ap['over90']);
        $this->assertSame(2037617111.5, $ap['tagihan']);

        // SIMPO: 15 hari -> 1-30; 120 hari -> over90
        $this->assertSame(65000000.0, $simpo['credit_limit']);
        $this->assertSame(1173900.0, $simpo['b1_30']);
        $this->assertSame(0.0, $simpo['b31_60']);
        $this->assertSame(0.0, $simpo['b61_90']);
        $this->assertSame(187167000.0, $simpo['over90']);
        $this->assertSame(188340900.0, $simpo['tagihan']);

        $totals = $reportData['totals'];
        $this->assertSame(1173900.0, $totals['b1_30']);
        $this->assertSame(137106070.0, $totals['b31_60']);
        $this->assertSame(1893189901.5 + 187167000.0, $totals['over90']);
        $this->assertSame(2037617111.5 + 188340900.0, $totals['tagihan']);

        $this->assertSame(2, $reportData['total_rows']);
    }
}
