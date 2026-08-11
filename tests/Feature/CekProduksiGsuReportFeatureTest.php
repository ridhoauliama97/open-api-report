<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ascends\Shared\CustomReport\CekProduksiGsuReportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CekProduksiGsuReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <ItemCode>2.1.1.10.01</ItemCode>
    <ItemName>CST</ItemName>
    <FamilyName>BAHAN BAKU PAKAI</FamilyName>
    <StockCategoryName>BAHAN BAKU</StockCategoryName>
    <Qty>0.0000</Qty>
    <QtyProd>0.0000</QtyProd>
    <QtyMatrl>0.0000</QtyMatrl>
    <PrcIN>0.0000</PrcIN>
    <AdjusIn>0.0000</AdjusIn>
    <AdjusOut>0.0000</AdjusOut>
    <UsageIn>0.0000</UsageIn>
    <Sawal>605.5500</Sawal>
    <Sales>0.0000</Sales>
    <Retur>0.0000</Retur>
    <Good>0.0000</Good>
    <Broken>0.0000</Broken>
    <QtyAdjusIn>0.0000</QtyAdjusIn>
    <QtyAdjusOut>0.0000</QtyAdjusOut>
    <Material>0.0000</Material>
    <QtyPrcIn>0.0000</QtyPrcIn>
    <QtyPrcOut>0.0000</QtyPrcOut>
    <QtyUsg>0.0000</QtyUsg>
  </Table>
  <Table>
    <ItemCode>2.1.1.1.09</ItemCode>
    <ItemName>FILLER UF212 (VTM)</ItemName>
    <FamilyName>BAHAN BAKU PAKAI</FamilyName>
    <StockCategoryName>BAHAN BAKU</StockCategoryName>
    <Sawal>27848.4100</Sawal>
  </Table>
  <Table>
    <ItemCode>2.1.1.1.99</ItemCode>
    <ItemName>FULL FORMULA</ItemName>
    <FamilyName>BAHAN BAKU PAKAI</FamilyName>
    <StockCategoryName>BAHAN BAKU</StockCategoryName>
    <Sawal>10.0000</Sawal>
    <Good>1.0000</Good>
    <Broken>2.0000</Broken>
    <QtyAdjusIn>3.0000</QtyAdjusIn>
    <Retur>4.0000</Retur>
    <Sales>5.0000</Sales>
    <QtyAdjusOut>1.0000</QtyAdjusOut>
    <Material>1.0000</Material>
    <QtyPrcIn>5.0000</QtyPrcIn>
    <QtyUsg>1.0000</QtyUsg>
    <QtyPrcOut>1.0000</QtyPrcOut>
  </Table>
  <Table>
    <ItemCode>2.1.5.1.08.12</ItemCode>
    <ItemName>DICARINTAHKAN</ItemName>
    <FamilyName>ENAMEL</FamilyName>
    <StockCategoryName>BARANG DAGANG</StockCategoryName>
    <Sawal>999.0000</Sawal>
  </Table>
  <Table>
    <ItemCode>2.1.1.1.10</ItemCode>
    <ItemName>HAS NEGATIF</ItemName>
    <FamilyName>BAHAN BAKU PAKAI</FamilyName>
    <StockCategoryName>BAHAN BAKU</StockCategoryName>
    <Sawal>5.0000</Sawal>
    <Sales>50.0000</Sales>
  </Table>
  <Table>
    <ItemCode>2.1.1.1.11</ItemCode>
    <ItemName>QTY TIDAK NOL</ItemName>
    <FamilyName>BAHAN BAKU PAKAI</FamilyName>
    <StockCategoryName>BAHAN BAKU</StockCategoryName>
    <Sawal>100.0000</Sawal>
    <Qty>5.0000</Qty>
  </Table>
  <Table>
    <ItemCode>2.1.5.1.01.02</ItemCode>
    <ItemName>SEKAR BASKOM BIASA 40 CM DECO</ItemName>
    <FamilyName>ENAMEL</FamilyName>
    <StockCategoryName>BARANG DAGANG</StockCategoryName>
    <Sawal>3509.0000</Sawal>
  </Table>
  <Table>
    <ItemCode>2.1.1.3.12.04</ItemCode>
    <ItemName>MORE MEJA SANTAI MS 2801 PREMIUM ORANGE</ItemName>
    <FamilyName>PLASTIK FURNITURE 2</FamilyName>
    <StockCategoryName>BARANG JADI</StockCategoryName>
    <Sawal>25000.0000</Sawal>
    <Sales>24638.0000</Sales>
  </Table>
  <Table>
    <ItemCode>2.1.1.6.01.05</ItemCode>
    <ItemName>PP GIL AQUE A1 (BROKER)</ItemName>
    <FamilyName>BROKER</FamilyName>
    <StockCategoryName>WORK IN PROGRESS</StockCategoryName>
    <Sawal>24508.5800</Sawal>
  </Table>
</NewDataSet>
XML;

    public function test_cek_produksi_gsu_pdf_download_endpoint_returns_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlPath = 'C:\\Users\\ridho\\AppData\\Local\\Temp\\Custom9.xml';
        if (! file_exists($xmlPath)) {
            $this->markTestSkipped('Custom9.xml not found.');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/cek-produksi-gsu/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-08-01',
            'EndDate' => '2026-08-02',
            'xml_file' => new UploadedFile(
                $xmlPath,
                'Custom9.xml',
                'text/xml',
                null,
                true
            ),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_service_returns_only_selected_row_groups(): void
    {
        $service = new CekProduksiGsuReportService;

        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'fixture', [
            'company' => 'GSU',
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-08-01',
            'EndDate' => '2026-08-02',
        ]);

        $this->assertSame('Laporan Item Tidak ada Penjualan Dan Tidak Ada Produksi', $reportData['title']);
        $this->assertSame(6, $reportData['total_rows']);
        $this->assertSame('01-Agt-26', $reportData['start_date']);
        $this->assertSame('02-Agt-26', $reportData['end_date']);
        $this->assertSame('Ridho', $reportData['printed_by']);

        $sections = $reportData['sections'];
        $this->assertCount(4, $sections);

        $this->assertSame('BAHAN BAKU', $sections[0]['category_name']);
        $this->assertSame('BARANG DAGANG', $sections[1]['category_name']);
        $this->assertSame('BARANG JADI', $sections[2]['category_name']);
        $this->assertSame('WORK IN PROGRESS', $sections[3]['category_name']);

        $itemCodes = array_column($sections[0]['rows'], 'item_code');
        $this->assertSame(['2.1.1.1.09', '2.1.1.1.99', '2.1.1.10.01'], $itemCodes);

        $this->assertSame(27848.41, $sections[0]['rows'][0]['saldo_awal']);

        $fullFormulaRow = $sections[0]['rows'][1];
        $this->assertSame(16.0, $fullFormulaRow['saldo_awal']);
    }

    public function test_service_zeroes_qty_sales_and_qty_prod_columns(): void
    {
        $service = new CekProduksiGsuReportService;

        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'fixture', [
            'DB_CompanyName' => 'GSU',
        ]);

        $barangJadi = $reportData['sections'][2]['rows'][0];
        $this->assertSame(362.0, $barangJadi['saldo_awal']);
        $this->assertSame(0.0, $barangJadi['qty_sales']);
        $this->assertSame(0.0, $barangJadi['qty_prod']);
    }

    public function test_cek_produksi_gsu_endpoint_returns_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('cek_produksi_gsu.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/cek-produksi-gsu/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-08-01',
            'EndDate' => '2026-08-02',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_endpoint_resolves_alternative_alias_keys(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('cek_produksi_gsu.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/cek-produksi-gsu/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'sys_username' => 'AlternativeUser',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
        ]);

        $response->assertOk();
    }
}
