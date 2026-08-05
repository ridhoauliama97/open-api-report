<?php

namespace Tests\Unit;

use App\Services\Ascends\Shared\CustomReport\PenjualanPerKategoriBarangBulananReportService;
use Tests\TestCase;

class PenjualanPerKategoriBarangBulananReportServiceTest extends TestCase
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

    public function test_section_uses_target_map_for_monthly_and_per_day_targets(): void
    {
        $data = $this->build();

        $section = $data['sections'][0];

        $this->assertSame('FENDY', $section['sales_name']);
        $this->assertSame([
            'pf' => 500.0, 'pk1' => 1000.0, 'pk2' => 800.0, 'enamel' => 700.0, 'fl' => 900.0,
        ], $section['monthly_target']);
        $this->assertSame(3900.0, $section['monthly_target_total']);
        $this->assertSame(19.0, $section['target_per_hari']['pf']); // round(500 / 26)
        $this->assertSame(150.0, $section['target_total_per_hari']);
    }

    public function test_daily_records_enumerate_all_working_days_and_fix_deviation_by_row_index(): void
    {
        $data = $this->build();

        $section = $data['sections'][0];

        // July 2026 has 27 non-Sunday days.
        $this->assertCount(27, $section['daily_records']);

        // day 2 is the 2nd non-Sunday row. running pf = 100 (day2). daily target = round(500/26) = 19.
        $day2 = $section['daily_records'][1];
        $this->assertSame(2, $day2['day']);
        $this->assertSame(100.0, $day2['pf_running']);
        $this->assertSame(62.0, $day2['pf_dev']); // 100 - (19 * 2)

        // day 7 is the 6th non-Sunday row. running pf = 160 (day2 + day7).
        $day7 = $section['daily_records'][5];
        $this->assertSame(7, $day7['day']);
        $this->assertSame(160.0, $day7['pf_running']);
        $this->assertSame(46.0, $day7['pf_dev']); // 160 - (19 * 6)
    }

    public function test_pk1_subtotal_deviation_uses_total_actual_not_pk1_actual(): void
    {
        $data = $this->build();

        $section = $data['sections'][0];

        // total actual = 160 (pf) + 950 (pk1) = 1110
        $this->assertSame(1110.0, $section['total_rp_all']);

        $this->assertSame(340.0, $section['cat_totals']['pf']['dev']);      // 500 - 160
        $this->assertSame(-110.0, $section['cat_totals']['pk1']['dev']);    // 1000 - 1110 (quirk)
        $this->assertSame(2790.0, $section['total_dev']);                   // 3900 - 1110
    }

    public function test_weekly_analysis_groups_by_xml_week_and_percent_of_monthly_target(): void
    {
        $data = $this->build();

        $section = $data['sections'][0];
        $weekly = $section['weekly_analysis'];

        $pf = collect($weekly)->firstWhere('category', 'pf');
        $this->assertSame(100.0, $pf['weeks'][1]['penjualan']);
        $this->assertSame(60.0, $pf['weeks'][2]['penjualan']);
        $this->assertEqualsWithDelta(100 / 500 * 100, $pf['weeks'][1]['pct'], 0.0001);

        $pk1 = collect($weekly)->firstWhere('category', 'pk1');
        $this->assertSame(50.0, $pk1['weeks'][1]['penjualan']);
        $this->assertSame(900.0, $pk1['weeks'][5]['penjualan']);
        $this->assertEqualsWithDelta(900 / 1000 * 100, $pk1['weeks'][5]['pct'], 0.0001);

        $total = collect($weekly)->firstWhere('category', 'total');
        $this->assertSame(3900.0, $total['target']);
        $this->assertSame(150.0, $total['weeks'][1]['penjualan']); // 100 + 50
    }

    public function test_daily_analysis_uses_gross_for_terendah_and_linetotal_for_pf_tertinggi(): void
    {
        $data = $this->build();

        $section = $data['sections'][0];
        $daily = $section['daily_analysis'];

        $pf = collect($daily)->firstWhere('category', 'pf');
        // days with data: day2 (rp 100, gross 110), day7 (rp 60, gross 66)
        $this->assertEqualsWithDelta(160 / 27, $pf['rata_rata'], 0.0001);
        $this->assertSame(66.0, $pf['terendah']);        // min gross
        $this->assertSame(100.0, $pf['tertinggi']);      // max LineTotal (pf quirk)

        $pk1 = collect($daily)->firstWhere('category', 'pk1');
        // days with data: day2 (rp 50, gross 60), day30 (rp 900, gross 1000)
        $this->assertSame(60.0, $pk1['terendah']);
        $this->assertSame(1000.0, $pk1['tertinggi']);    // max gross (non-pf)
    }

    public function test_missing_target_falls_back_to_actual_category_totals(): void
    {
        $xml = $this->rowsXml();
        $data = (new PenjualanPerKategoriBarangBulananReportService)->buildReportDataFromXml(
            $xml,
            'test',
            ['StartDate' => '2026-07-01', 'EndDate' => '2026-07-31', 'JumlahHariKerja' => 26]
        );

        $section = $data['sections'][0];

        $this->assertSame(160.0, $section['monthly_target']['pf']);
        $this->assertSame(950.0, $section['monthly_target']['pk1']);
        $this->assertSame(0.0, $section['monthly_target']['enamel']);
    }

    public function test_pk1_only_target_expands_with_reference_percentages(): void
    {
        $data = (new PenjualanPerKategoriBarangBulananReportService)->buildReportDataFromXml(
            $this->rowsXml(),
            'test',
            [
                'StartDate' => '2026-07-01',
                'EndDate' => '2026-07-31',
                'JumlahHariKerja' => 26,
                'Target' => json_encode(['FENDY' => 2076540375]),
            ]
        );

        $section = $data['sections'][0];

        $this->assertSame(0.0, $section['monthly_target']['pf']);
        $this->assertSame(2076540375.0, $section['monthly_target']['pk1']);
        $this->assertSame(round(2076540375 * (7 / 13)), $section['monthly_target']['pk2']);
        $this->assertSame(round(2076540375 * (18 / 91)), $section['monthly_target']['enamel']);
        $this->assertSame(round(2076540375 * (2 / 91)), $section['monthly_target']['fl']);
        $this->assertSame(2076540375 + round(2076540375 * (7 / 13)) + round(2076540375 * (18 / 91)) + round(2076540375 * (2 / 91)), $section['monthly_target_total']);
    }

    private function build(): array
    {
        return (new PenjualanPerKategoriBarangBulananReportService)->buildReportDataFromXml(
            $this->rowsXml(),
            'test',
            [
                'StartDate' => '2026-07-01',
                'EndDate' => '2026-07-31',
                'JumlahHariKerja' => 26,
                'Target' => json_encode([
                    'FENDY' => ['pf' => 500, 'pk1' => 1000, 'pk2' => 800, 'enamel' => 700, 'fl' => 900],
                ]),
            ]
        );
    }

    private function rowsXml(): string
    {
        return implode("\n", [
            '<NewDataSet>',
            $this->table('2026-07-02', 'Thursday', '1', 'PLASTIK FURNITURE 1', 'ITEM A', '5', '100', '110'),
            $this->table('2026-07-02', 'Thursday', '1', 'PLASTIK KABINET 1', 'ITEM B', '2', '50', '60'),
            $this->table('2026-07-07', 'Tuesday', '2', 'PLASTIK FURNITURE 1', 'ITEM C', '3', '60', '66'),
            $this->table('2026-07-30', 'Thursday', '5', 'PLASTIK KABINET 1', 'ITEM D', '10', '900', '1000'),
            '</NewDataSet>',
        ]);
    }

    private function table(string $date, string $day, string $week, string $family, string $item, string $qty, string $lt, string $gross): string
    {
        return '<Table>'
            ."<Date>{$date}T00:00:00+07:00</Date>"
            ."<Day>{$day}</Day>"
            ."<Week>{$week}</Week>"
            .'<SP_x0020_Name>FENDY</SP_x0020_Name>'
            ."<Family_x0020_Name>{$family}</Family_x0020_Name>"
            ."<Item_x0020_Name>{$item}</Item_x0020_Name>"
            ."<Quantity>{$qty}</Quantity>"
            ."<LineTotal>{$lt}</LineTotal>"
            ."<LineGrossTotal>{$gross}</LineGrossTotal>"
            .'</Table>';
    }
}
