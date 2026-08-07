<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PembelianKayuPerPeriodeReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <PurchaseType>PI</PurchaseType>
    <PurchaseNumber>RU/PI/26/07/0019</PurchaseNumber>
    <PurchaseDate>2026-07-02T00:00:00+07:00</PurchaseDate>
    <SupplierName>PUTRA.T - (MUSLIMIN)</SupplierName>
    <Name>GUDANG KAYU BULAT</Name>
    <ItemName>KB RAMBUNG - STD</ItemName>
    <Quantity>27681.0000</Quantity>
    <UOMCode>KG</UOMCode>
    <LineTotal>17439030.0000</LineTotal>
    <ExtraInvoiceDiscount>1309.1086</ExtraInvoiceDiscount>
    <OtherLineTotal>178000.0000</OtherLineTotal>
    <Hasil>17615720.8914</Hasil>
  </Table>
  <Table>
    <PurchaseType>PI</PurchaseType>
    <PurchaseNumber>RU/PI/26/07/0019</PurchaseNumber>
    <PurchaseDate>2026-07-02T00:00:00+07:00</PurchaseDate>
    <SupplierName>PUTRA.T - (MUSLIMIN)</SupplierName>
    <Name>GUDANG KAYU BULAT</Name>
    <ItemName>KB RAMBUNG - MC</ItemName>
    <Quantity>414.0000</Quantity>
    <UOMCode>KG</UOMCode>
    <LineTotal>82800.0000</LineTotal>
    <ExtraInvoiceDiscount>6.2156</ExtraInvoiceDiscount>
    <OtherLineTotal>0.0000</OtherLineTotal>
    <Hasil>82793.7844</Hasil>
  </Table>
  <Table>
    <PurchaseType>PI</PurchaseType>
    <PurchaseNumber>RU/PI/26/07/0021</PurchaseNumber>
    <PurchaseDate>2026-07-06T00:00:00+07:00</PurchaseDate>
    <SupplierName>PUTRA.T - (ADEK)</SupplierName>
    <Name>GUDANG KAYU BULAT</Name>
    <ItemName>KB RAMBUNG - STD</ItemName>
    <Quantity>29943.0000</Quantity>
    <UOMCode>KG</UOMCode>
    <LineTotal>18864090.0000</LineTotal>
    <ExtraInvoiceDiscount>874.0245</ExtraInvoiceDiscount>
    <OtherLineTotal>172000.0000</OtherLineTotal>
    <Hasil>19035215.9755</Hasil>
  </Table>
  <Table>
    <PurchaseType>PI</PurchaseType>
    <PurchaseNumber>RU/PI/26/07/0074</PurchaseNumber>
    <PurchaseDate>2026-07-22T00:00:00+07:00</PurchaseDate>
    <SupplierName>YENNI</SupplierName>
    <Name>GUDANG KAYU BULAT</Name>
    <ItemName>KB JABON - STD</ItemName>
    <Quantity>6.3839</Quantity>
    <UOMCode>TON</UOMCode>
    <LineTotal>12767800.0000</LineTotal>
    <ExtraInvoiceDiscount>1758.5168</ExtraInvoiceDiscount>
    <OtherLineTotal>0.0000</OtherLineTotal>
    <Hasil>12766041.4832</Hasil>
  </Table>
</NewDataSet>
XML;

    public function test_pembelian_kayu_per_periode_pdf_download_works(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('CustomReport.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/pembelian-kayu-per-periode/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'RU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pembelian_kayu_per_periode_pdf_requires_db_company_name(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('CustomReport.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/pembelian-kayu-per-periode/pdf', [
            'xml_file' => $xmlFile,
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Field DB_CompanyName wajib dikirim.']);
    }
}
