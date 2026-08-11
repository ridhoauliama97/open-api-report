<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ascends\Shared\CustomReport\BiayaMobilTrukReportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BiayaMobilTrukReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NewDataSet>
  <Table>
    <VoucherDate>2026-01-15T00:00:00+07:00</VoucherDate>
    <LowestDescription>Solar</LowestDescription>
    <AccountName>Bahan Bakar</AccountName>
    <Amt>500000</Amt>
  </Table>
  <Table>
    <VoucherDate>2026-02-20T00:00:00+07:00</VoucherDate>
    <LowestDescription>Solar</LowestDescription>
    <AccountName>Bahan Bakar</AccountName>
    <Amt>750000</Amt>
  </Table>
  <Table>
    <VoucherDate>2026-01-10T00:00:00+07:00</VoucherDate>
    <LowestDescription>Service</LowestDescription>
    <AccountName>Perawatan</AccountName>
    <Amt>200000</Amt>
  </Table>
</NewDataSet>
XML;

    public function test_biaya_mobil_truk_endpoint_returns_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('biaya_mobil_truk.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/biaya-mobil-truk/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-01-01',
            'EndDate' => '2026-06-30',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertPdfDisposition($response, 'attachment', 'Laporan Biaya Mobil Truk');
    }

    public function test_service_parses_xml_and_groups_by_description(): void
    {
        $service = new BiayaMobilTrukReportService;

        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'fixture', [
            'company' => 'GSU',
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-01-01',
            'EndDate' => '2026-06-30',
        ]);

        $this->assertSame('Laporan Biaya Mobil / Truk (Periode 6 Bulanan)', $reportData['title']);
        $this->assertSame('GSU', $reportData['company']);
        $this->assertSame('Ridho', $reportData['printed_by']);
        $this->assertCount(2, $reportData['sections']);

        $descriptions = array_column($reportData['sections'], 'lowest_description');
        $this->assertSame(['Service', 'Solar'], $descriptions);
    }

    public function test_endpoint_resolves_alternative_sys_username_alias(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('biaya_mobil_truk.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/biaya-mobil-truk/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'sys_username' => 'AlternativeUser',
            'StartDate' => '2026-01-01',
            'EndDate' => '2026-06-30',
        ]);

        $response->assertOk();
    }
}
