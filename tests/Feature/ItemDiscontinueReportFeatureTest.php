<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ItemDiscontinueReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <ItemCode>2.1.1.1.10</ItemCode>
    <ItemName>FILLER OXIDE</ItemName>
    <StockCategoryName>BAHAN BAKU</StockCategoryName>
    <FamilyName>BAHAN BAKU PAKAI</FamilyName>
    <Sawal>71.2000</Sawal>
    <QtyProd>0</QtyProd>
    <Qty>0</Qty>
  </Table>
  <Table>
    <ItemCode>2.1.1.6.03.16</ItemCode>
    <ItemName>PP GIL HIJAU KURSI (BROKER)</ItemName>
    <StockCategoryName>WORK IN PROGRESS</StockCategoryName>
    <FamilyName>BROKER</FamilyName>
    <Sawal>4881.0000</Sawal>
    <QtyProd>28776.4000</QtyProd>
    <Qty>6891.0000</Qty>
  </Table>
</NewDataSet>
XML;

    public function test_item_discontinue_pdf_download_works(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('CustomReport.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/item-discontinue/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'RU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_item_discontinue_pdf_requires_db_company_name(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('CustomReport.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/item-discontinue/pdf', [
            'xml_file' => $xmlFile,
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Field DB_CompanyName wajib dikirim.']);
    }
}
