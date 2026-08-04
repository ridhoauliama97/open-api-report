<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ascends\Shared\CustomReport\PengirimanKursiDanMejaHarianReportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PengirimanKursiDanMejaHarianReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <No>0</No>
    <DateID>2026-07-01T00:00:00+07:00</DateID>
    <ItemCode>2.1.3.1.01.09</ItemCode>
    <ItemName>MERONA KURSI MAKAN KM 2401 A BIRU</ItemName>
    <FamilyID>867</FamilyID>
    <Qty>6.0000</Qty>
  </Table>
  <Table>
    <No>0</No>
    <DateID>2026-07-02T00:00:00+07:00</DateID>
    <ItemCode>2.1.3.1.01.09</ItemCode>
    <ItemName>MERONA KURSI MAKAN KM 2401 A BIRU</ItemName>
    <FamilyID>867</FamilyID>
    <Qty>4.0000</Qty>
  </Table>
  <Table>
    <No>0</No>
    <DateID>2026-07-02T00:00:00+07:00</DateID>
    <ItemCode>2.1.3.1.01.02</ItemCode>
    <ItemName>MERONA KURSI BAKSO KT 2301 A BIRU</ItemName>
    <FamilyID>867</FamilyID>
    <Qty>2.0000</Qty>
  </Table>
  <Table>
    <No>0</No>
    <DateID>2026-07-01T00:00:00+07:00</DateID>
    <ItemCode>2.1.3.2.01.01</ItemCode>
    <ItemName>MERONA KURSI SANTAI KS 2501 A BIRU</ItemName>
    <FamilyID>2878</FamilyID>
    <Qty>5.0000</Qty>
  </Table>
  <Table>
    <No>0</No>
    <DateID>2026-07-03T00:00:00+07:00</DateID>
    <ItemCode>2.1.3.3.01.01</ItemCode>
    <ItemName>MORE MEJA SANTAI MS 2801 A BIRU</ItemName>
    <FamilyID>2878</FamilyID>
    <Qty>3.0000</Qty>
  </Table>
  <Table>
    <No>0</No>
    <DateID>2026-07-01T00:00:00+07:00</DateID>
    <ItemCode>2.1.3.1.09.99</ItemCode>
    <ItemName>PROMO KURSI MERONA KURSI MAKAN KM 2401</ItemName>
    <FamilyID>867</FamilyID>
    <Qty>10.0000</Qty>
  </Table>
  <Table>
    <No>0</No>
    <DateID>2026-07-01T00:00:00+07:00</DateID>
    <ItemCode>1.1.1.1.01.01</ItemCode>
    <ItemName>ENAMAL SPESIAL ITEM</ItemName>
    <FamilyID>875</FamilyID>
    <Qty>2.0000</Qty>
  </Table>
</NewDataSet>
XML;

    public function test_pengiriman_kursi_dan_meja_harian_pdf_generation(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('Custom2049.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/pengiriman-kursi-dan-meja-harian/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'TestUser',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename="Laporan Pengiriman Kursi Makan, Kursi Santai, Kursi Cafe Dan Meja Santai(Harian) GSU.pdf"; filename*=UTF-8\'\'Laporan%20Pengiriman%20Kursi%20Makan%2C%20Kursi%20Santai%2C%20Kursi%20Cafe%20Dan%20Meja%20Santai%28Harian%29%20GSU.pdf'
        );
    }

    public function test_promo_kursi_items_are_excluded(): void
    {
        $service = new PengirimanKursiDanMejaHarianReportService;
        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'Custom2049.xml', [
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $itemNames = [];
        foreach ($reportData['categories'] as $cat) {
            foreach ($cat['items'] as $item) {
                $itemNames[] = $item['item_name'];
            }
        }

        $allItemNames = implode(' ', $itemNames);
        $this->assertStringNotContainsString('PROMO KURSI', $allItemNames);
        $this->assertStringContainsString('MERONA KURSI MAKAN KM 2401 A BIRU', $allItemNames);
        $this->assertStringContainsString('MERONA KURSI SANTAI KS 2501 A BIRU', $allItemNames);
    }

    public function test_non_furniture_items_are_excluded(): void
    {
        $service = new PengirimanKursiDanMejaHarianReportService;
        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'Custom2049.xml', [
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $itemNames = [];
        foreach ($reportData['categories'] as $cat) {
            foreach ($cat['items'] as $item) {
                $itemNames[] = $item['item_name'];
            }
        }

        $allItemNames = implode(' ', $itemNames);
        $this->assertStringNotContainsString('ENAMAL SPESIAL ITEM', $allItemNames);
    }

    public function test_category_grouping_and_totals(): void
    {
        $service = new PengirimanKursiDanMejaHarianReportService;
        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'Custom2049.xml', [
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $this->assertSame([1, 2, 3], $reportData['day_numbers']);

        $categoryTotals = [];
        $categoryLabels = [];
        foreach ($reportData['categories'] as $cat) {
            $categoryLabels[] = $cat['label'];
            $categoryTotals[$cat['label']] = $cat['total_qty'];
        }

        $this->assertContains('Plastik Furniture 1', $categoryLabels);
        $this->assertContains('Plastik Furniture 2', $categoryLabels);
        $this->assertEquals(12.0, $categoryTotals['Plastik Furniture 1']);
        $this->assertEquals(8.0, $categoryTotals['Plastik Furniture 2']);
    }

    public function test_date_range_filter(): void
    {
        $service = new PengirimanKursiDanMejaHarianReportService;
        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'Custom2049.xml', [
            'StartDate' => '2026-07-02',
            'EndDate' => '2026-07-03',
        ]);

        $this->assertSame([2, 3], $reportData['day_numbers']);

        $pf1 = $reportData['categories'][0];
        $this->assertSame('Plastik Furniture 1', $pf1['label']);
        $this->assertEquals(6.0, $pf1['total_qty']);
        $this->assertEquals(6.0, $pf1['daily'][2]);
        $this->assertEquals(0.0, $pf1['daily'][3] ?? 0.0);
    }

    public function test_period_label_uses_indo_format(): void
    {
        $service = new PengirimanKursiDanMejaHarianReportService;
        $reportData = $service->buildReportDataFromXml(self::SAMPLE_XML, 'Custom2049.xml', [
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
        ]);

        $this->assertSame('Dari 01-Jul-26 s/d 31-Jul-26', $reportData['period_label']);
    }

    public function test_empty_data_throws_exception(): void
    {
        $emptyXml = '<?xml version="1.0"?><NewDataSet></NewDataSet>';
        $xmlFile = UploadedFile::fake()->createWithContent('empty.xml', $emptyXml);

        $response = $this->postJson('/api/internal/ascends/shared/custom-report/pengiriman-kursi-dan-meja-harian/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Data tidak ditemukan pada XML.']);
    }
}
