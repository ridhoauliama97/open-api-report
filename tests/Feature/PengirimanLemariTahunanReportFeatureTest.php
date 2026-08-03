<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PengirimanLemariTahunanReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <No>0</No>
    <DateID>2025-01-15T00:00:00+07:00</DateID>
    <CondItemID>6012</CondItemID>
    <ItemCode>2.1.3.4.01.29</ItemCode>
    <ItemName>GRANDE PLASTIK KABINET PK 3004 4Tx8P SNOW (LP 005)</ItemName>
    <FamilyID>2879</FamilyID>
    <Specification>BIRU</Specification>
    <Qty>5.0000</Qty>
  </Table>
  <Table>
    <No>0</No>
    <DateID>2025-02-10T00:00:00+07:00</DateID>
    <CondItemID>6062</CondItemID>
    <ItemCode>2.1.3.4.01.35</ItemCode>
    <ItemName>GRANDE PLASTIK KABINET PK 3004 4Tx8P MOSQUE (LP 012)</ItemName>
    <FamilyID>2879</FamilyID>
    <Specification>PUTIH</Specification>
    <Qty>9.0000</Qty>
  </Table>
  <Table>
    <No>0</No>
    <DateID>2025-01-05T00:00:00+07:00</DateID>
    <CondItemID>22501</CondItemID>
    <ItemCode>2.1.3.5.01.01</ItemCode>
    <ItemName>GRANDE PLASTIK KABINET PK 3014 4Tx4P PANORAMA (LP 106)</ItemName>
    <FamilyID>2892</FamilyID>
    <Specification>HIJAU APEL</Specification>
    <Qty>3.0000</Qty>
  </Table>
  <Table>
    <No>0</No>
    <DateID>2025-03-01T00:00:00+07:00</DateID>
    <CondItemID>6012</CondItemID>
    <ItemCode>2.1.3.4.01.29</ItemCode>
    <ItemName>PROMO LEM GRANDE PLASTIK KABINET PK 3004 4Tx8P SNOW</ItemName>
    <FamilyID>2879</FamilyID>
    <Specification>BIRU</Specification>
    <Qty>10.0000</Qty>
  </Table>
  <Table>
    <No>0</No>
    <DateID>2025-01-01T00:00:00+07:00</DateID>
    <CondItemID>100</CondItemID>
    <ItemCode>1.1.1.1.01.01</ItemCode>
    <ItemName>ENAMAL SPESIAL ITEM</ItemName>
    <FamilyID>875</FamilyID>
    <Specification>MERAH</Specification>
    <Qty>2.0000</Qty>
  </Table>
</NewDataSet>
XML;

    public function test_pengiriman_lemari_tahunan_pdf_generation(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('Custom16.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/pengiriman-lemari-tahunan/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'TestUser',
            'StartDate' => '2025-01-01',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename="Laporan Pengiriman Lemari (Tahunan) GSU.pdf"; filename*=UTF-8\'\'Laporan%20Pengiriman%20Lemari%20%28Tahunan%29%20GSU.pdf'
        );
    }

    public function test_promo_lem_items_are_excluded(): void
    {
        $service = new \App\Services\Ascends\Shared\CustomReport\PengirimanLemariTahunanReportService();
        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'Custom16.xml', [
            'StartDate' => '2025-01-01',
        ]);

        $itemNames = [];
        foreach ($reportData['categories'] as $cat) {
            foreach ($cat['grp_groups'] as $grp) {
                foreach ($grp['items'] as $item) {
                    $itemNames[] = $item['item_name'];
                }
            }
        }

        $allItemNames = implode(' ', $itemNames);
        $this->assertStringNotContainsString('PROMO LEM', $allItemNames);
        $this->assertStringContainsString('GRANDE PLASTIK KABINET PK 3004 4Tx8P SNOW', $allItemNames);
        $this->assertStringContainsString('GRANDE PLASTIK KABINET PK 3014 4Tx4P PANORAMA', $allItemNames);
    }

    public function test_non_cabinet_items_are_excluded(): void
    {
        $service = new \App\Services\Ascends\Shared\CustomReport\PengirimanLemariTahunanReportService();
        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'Custom16.xml', [
            'StartDate' => '2025-01-01',
        ]);

        $itemNames = [];
        foreach ($reportData['categories'] as $cat) {
            foreach ($cat['grp_groups'] as $grp) {
                foreach ($grp['items'] as $item) {
                    $itemNames[] = $item['item_name'];
                }
            }
        }

        $allItemNames = implode(' ', $itemNames);
        $this->assertStringNotContainsString('ENAMAL SPESIAL ITEM', $allItemNames);
    }

    public function test_grp_calculation_and_category_grouping(): void
    {
        $service = new \App\Services\Ascends\Shared\CustomReport\PengirimanLemariTahunanReportService();
        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'Custom16.xml', [
            'StartDate' => '2025-01-01',
        ]);

        $categoryLabels = array_column($reportData['categories'], 'label');
        $this->assertContains('Plastik Kabinet 1', $categoryLabels);
        $this->assertContains('Plastik Kabinet 2', $categoryLabels);

        $grps = [];
        foreach ($reportData['categories'] as $cat) {
            foreach ($cat['grp_groups'] as $grp) {
                $grps[] = $grp['grp'];
            }
        }
        $this->assertContains('PINTU 8', $grps);
    }

    public function test_empty_data_throws_exception(): void
    {
        $emptyXml = '<?xml version="1.0"?><NewDataSet></NewDataSet>';
        $xmlFile = UploadedFile::fake()->createWithContent('empty.xml', $emptyXml);

        $response = $this->postJson('/api/internal/ascends/shared/custom-report/pengiriman-lemari-tahunan/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Data tidak ditemukan pada XML.']);
    }

    public function test_real_custom16_xml_file(): void
    {
        $realPath = 'C:\\Users\\ridho\\AppData\\Local\\Temp\\Custom16.xml';
        if (! file_exists($realPath)) {
            $this->markTestSkipped('Custom16.xml file not found at expected location.');
        }

        $user = User::factory()->make(['id' => 1, 'name' => 'Test User']);
        $token = $this->issueJwtForUser($user);

        $xmlFile = new UploadedFile($realPath, 'Custom16.xml', 'text/xml', null, true);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/pengiriman-lemari-tahunan/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2025-01-01',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $pdfContent = $response->getContent();
        $this->assertNotEmpty($pdfContent);
    }
}
