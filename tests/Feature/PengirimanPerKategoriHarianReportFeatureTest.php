<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ascends\Shared\CustomReport\PengirimanPerKategoriHarianReportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PengirimanPerKategoriHarianReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
  <Table>
    <DateID>2026-07-01T00:00:00+07:00</DateID>
    <ItemCode>2.1.5.1.01.01</ItemCode>
    <ItemName>ENAMEL BIASA 20 CM</ItemName>
    <FamilyID>875</FamilyID>
    <Qty>10</Qty>
  </Table>
  <Table>
    <DateID>2026-07-02T00:00:00+07:00</DateID>
    <ItemCode>2.1.5.1.01.02</ItemCode>
    <ItemName>ENAMEL BIASA 24 CM</ItemName>
    <FamilyID>875</FamilyID>
    <Qty>5</Qty>
  </Table>
  <Table>
    <DateID>2026-06-30T00:00:00+07:00</DateID>
    <ItemCode>2.1.5.1.02.01</ItemCode>
    <ItemName>PLASTIK FURNITURE 1 CHAIR</ItemName>
    <FamilyID>867</FamilyID>
    <Qty>3</Qty>
  </Table>
</NewDataSet>
XML;

    public function test_pengiriman_per_kategori_harian_endpoint_returns_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('pengiriman.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/pengiriman-per-kategori-harian/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_service_groups_by_category_and_collects_day_numbers(): void
    {
        $service = new PengirimanPerKategoriHarianReportService;

        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'fixture', [
            'company' => 'GSU',
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $this->assertSame('Laporan Pengiriman Per Kategori (Harian)', $reportData['title']);
        $this->assertSame('GSU', $reportData['company']);
        $this->assertSame('Ridho', $reportData['printed_by']);
        $this->assertCount(1, $reportData['categories']);
        $this->assertSame([1, 2], $reportData['day_numbers']);
    }

    public function test_endpoint_resolves_alternative_alias_keys(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('pengiriman.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/pengiriman-per-kategori-harian/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'sys_username' => 'AlternativeUser',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $response->assertOk();
    }
}
