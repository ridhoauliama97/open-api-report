<?php

namespace Tests\Unit;

use App\Services\Ascends\Shared\CustomReport\PembelianKayuPerPeriodeReportService;
use Tests\TestCase;

class PembelianKayuPerPeriodeReportServiceTest extends TestCase
{
    protected $defaultTimeZone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultTimeZone = date_default_timezone_get();
        date_default_timezone_set('Asia/Jakarta');
        config()->set('app.timezone', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->defaultTimeZone);

        parent::tearDown();
    }

    public function test_build_report_data_groups_by_uom_and_sorts_suppliers_and_items_by_qty_desc(): void
    {
        $data = $this->build();

        $this->assertSame('Laporan Pembelian Kayu Per Periode', $data['title']);
        $this->assertSame('RU', $data['company']);
        $this->assertSame('Ridho', $data['printed_by']);

        $uoms = array_column($data['sections'], 'uom');
        $this->assertSame(['KG', 'TON'], $uoms);

        $kg = $data['sections'][0];
        $suppliers = array_column($kg['suppliers'], 'supplier');
        $this->assertSame(['PUTRA.T - (MUSLIMIN)', 'PUTRA.T - (ADEK)'], $suppliers);

        $muslimin = $kg['suppliers'][0];
        $items = array_column($muslimin['items'], 'item');
        $this->assertSame(['KB RAMBUNG - STD', 'KB RAMBUNG - MC'], $items);

        $this->assertEqualsWithDelta(85811.0, $kg['qty'], 0.0001);
        $this->assertEqualsWithDelta(54404541.2475, $kg['total'], 0.0001);
    }

    public function test_aggregates_quantity_and_hasil_per_supplier_and_item(): void
    {
        $data = $this->build();

        $kg = $data['sections'][0];
        $muslimin = $kg['suppliers'][0];

        // STD split over two purchases (27681 + 27773).
        $std = collect($muslimin['items'])->firstWhere('item', 'KB RAMBUNG - STD');
        $this->assertEqualsWithDelta(55454.0, $std['qty'], 0.0001);
        $this->assertEqualsWithDelta(35286531.4876, $std['total'], 0.0001);

        $mc = collect($muslimin['items'])->firstWhere('item', 'KB RAMBUNG - MC');
        $this->assertEqualsWithDelta(414.0, $mc['qty'], 0.0001);
        $this->assertEqualsWithDelta(82793.7844, $mc['total'], 0.0001);

        $this->assertEqualsWithDelta(55868.0, $muslimin['qty'], 0.0001);
        $this->assertEqualsWithDelta(35369325.272, $muslimin['total'], 0.0001);
    }

    public function test_percent_is_supplier_qty_over_uom_total_qty(): void
    {
        $data = $this->build();

        $kg = $data['sections'][0];
        $muslimin = $kg['suppliers'][0];
        $adek = $kg['suppliers'][1];

        $this->assertEqualsWithDelta(55868 / 85811 * 100, $muslimin['percent'], 0.0001);
        $this->assertEqualsWithDelta(29943 / 85811 * 100, $adek['percent'], 0.0001);

        $ton = $data['sections'][1];
        $yenni = $ton['suppliers'][0];
        $this->assertEqualsWithDelta(6.3839 / 12.2885 * 100, $yenni['percent'], 0.0001);
    }

    public function test_filters_by_date_range_and_derives_period_month_from_earliest_row(): void
    {
        $data = $this->build(['StartDate' => '2026-07-16', 'EndDate' => '2026-07-31']);

        $this->assertSame('Dari 16-Jul-26 s/d 31-Jul-26', $data['period_label']);
        $this->assertSame('Jul - 2026', $data['period_month']);

        // All rows before 2026-07-16 are excluded (KG section disappears).
        $uoms = array_column($data['sections'], 'uom');
        $this->assertSame(['TON'], $uoms);

        $ton = $data['sections'][0];
        $tonSuppliers = array_column($ton['suppliers'], 'supplier');
        $this->assertSame(['YENNI', 'PUTRA.T - (ANNES)'], $tonSuppliers);
        $this->assertEqualsWithDelta(12.2885, $ton['qty'], 0.0001);
    }

    public function test_throws_when_no_rows_after_date_filter(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada data dalam rentang tanggal yang dipilih.');

        $this->build(['StartDate' => '2026-08-01', 'EndDate' => '2026-08-31']);
    }

    public function test_throws_when_xml_has_no_table_rows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Data tidak ditemukan pada XML.');

        (new PembelianKayuPerPeriodeReportService)->buildReportDataFromXml('<NewDataSet></NewDataSet>', 'test');
    }

    private function build(array $filters = []): array
    {
        return (new PembelianKayuPerPeriodeReportService)->buildReportDataFromXml(
            $this->rowsXml(),
            'test',
            array_merge([
                'StartDate' => '2026-07-01',
                'EndDate' => '2026-07-31',
                'Sys_Username' => 'Ridho',
                'DB_CompanyName' => 'RU',
            ], $filters),
        );
    }

    private function rowsXml(): string
    {
        return implode("\n", [
            '<NewDataSet>',
            $this->table('2026-07-02', 'PUTRA.T - (MUSLIMIN)', 'KB RAMBUNG - STD', '27681.0000', 'KG', '17615720.8914'),
            $this->table('2026-07-02', 'PUTRA.T - (MUSLIMIN)', 'KB RAMBUNG - MC', '414.0000', 'KG', '82793.7844'),
            $this->table('2026-07-15', 'PUTRA.T - (MUSLIMIN)', 'KB RAMBUNG - STD', '27773.0000', 'KG', '17670810.5962'),
            $this->table('2026-07-06', 'PUTRA.T - (ADEK)', 'KB RAMBUNG - STD', '29943.0000', 'KG', '19035215.9755'),
            $this->table('2026-07-22', 'YENNI', 'KB JABON - STD', '6.3839', 'TON', '12766041.4832'),
            $this->table('2026-07-29', 'PUTRA.T - (ANNES)', 'KB JABON TG - STD', '5.9046', 'TON', '11809000.0000'),
            '</NewDataSet>',
        ]);
    }

    private function table(string $date, string $supplier, string $item, string $qty, string $uom, string $hasil): string
    {
        return '<Table>'
            .'<PurchaseType>PI</PurchaseType>'
            .'<PurchaseNumber>RU/PI/26/07/0001</PurchaseNumber>'
            ."<PurchaseDate>{$date}T00:00:00+07:00</PurchaseDate>"
            ."<SupplierName>{$supplier}</SupplierName>"
            .'<Name>GUDANG KAYU BULAT</Name>'
            ."<ItemName>{$item}</ItemName>"
            ."<Quantity>{$qty}</Quantity>"
            ."<UOMCode>{$uom}</UOMCode>"
            .'<LineTotal>0.0000</LineTotal>'
            .'<ExtraInvoiceDiscount>0.0000</ExtraInvoiceDiscount>'
            .'<OtherLineTotal>0.0000</OtherLineTotal>'
            ."<Hasil>{$hasil}</Hasil>"
            .'</Table>';
    }
}
