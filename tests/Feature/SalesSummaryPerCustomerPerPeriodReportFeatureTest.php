<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SalesSummaryPerCustomerPerPeriodReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <InvoiceDate>01-2026</InvoiceDate>
    <CustomerID>1</CustomerID>
    <CustomerName>AP</CustomerName>
    <NetTotal>4458707.0000</NetTotal>
  </Table>
  <Table>
    <InvoiceDate>02-2026</InvoiceDate>
    <CustomerID>1</CustomerID>
    <CustomerName>AP</CustomerName>
    <NetTotal>0.0000</NetTotal>
  </Table>
  <Table>
    <InvoiceDate>06-2026</InvoiceDate>
    <CustomerID>74</CustomerID>
    <CustomerName>BAZAR PABRIK</CustomerName>
    <NetTotal>113541000.0000</NetTotal>
  </Table>
</NewDataSet>
XML;

    public function test_sales_summary_per_customer_per_period_pdf_preview(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('Custom2048.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/sales-summary-per-customer-per-period/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'TestUser',
            'StartDate' => '2026-01-01',
            'EndDate' => '2026-06-30',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
