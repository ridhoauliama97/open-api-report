<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DaftarHargaFurnitureSalesProjectReportFeatureTest extends TestCase
{
    public function test_daftar_harga_furniture_sales_project_pdf_download_works_without_db_company_name(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-furniture-sales-project/pdf', [
            'Sys_Username' => 'Ridho',
            'xml' => $this->sampleXml(),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    private function sampleXml(): string
    {
        $rows = [];

        foreach ([
            [
                'family' => 'FURNITURE LIPAT',
                'group' => 'MERONA KURSI MAKAN KM 2401',
                'after_discs' => ['100000', '99000', '98000'],
            ],
            [
                'family' => 'FURNITURE LIPAT',
                'group' => 'MORE 2 LEMARI MS 2814',
                'after_discs' => ['95000', '92000', '90000', '88000'],
            ],
        ] as $item) {
            $rows[] = $this->salesRows($item['group'], $item['after_discs']);
        }

        return '<NewDataSet>'.implode('', array_merge(...$rows)).'</NewDataSet>';
    }

    private function salesRows(string $group, array $afterDiscs): array
    {
        $rows = [];

        foreach ($afterDiscs as $afterDisc) {
            $rows[] = $this->table($group, '12. Harga Sales Project', '120000', $afterDisc);
        }

        return $rows;
    }

    private function table(string $group, string $priceLevel, string $price, string $afterDisc): string
    {
        return '<Table>'
            .'<FamilyName>FURNITURE LIPAT</FamilyName>'
            ."<PriceGroupName>{$group}</PriceGroupName>"
            ."<PriceGroupDescription>{$group}</PriceGroupDescription>"
            ."<PriceLevelName>{$priceLevel}</PriceLevelName>"
            ."<Price>{$price}</Price>"
            ."<PriceAfterDisc>{$afterDisc}</PriceAfterDisc>"
            .'</Table>';
    }
}
