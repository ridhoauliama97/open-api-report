<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DaftarHargaFurnitureSemiGrosirReportFeatureTest extends TestCase
{
    public function test_daftar_harga_furniture_semi_grosir_pdf_download_works_without_db_company_name(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-furniture-semi-grosir/pdf', [
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
                'family' => 'PLASTIK FURNITURE 1',
                'group' => 'MERONA KURSI MAKAN KM 2401 PREMIUM',
                'per_dus' => '15',
            ],
            [
                'family' => 'PLASTIK KABINET 1',
                'group' => 'HANA PLASTIK KABINET PK 3101 1TX2P',
                'per_dus' => '1',
            ],
        ] as $item) {
            $rows[] = $this->priceRows($item['family'], $item['group'], $item['per_dus']);
        }

        return '<NewDataSet>'.implode('', array_merge(...$rows)).'</NewDataSet>';
    }

    private function priceRows(string $family, string $group, string $perDus): array
    {
        return [
            $this->table($family, $group, $perDus, '01. Harga Retail', '120000', '114000'),
            $this->table($family, $group, $perDus, '02. Harga Semi Grosir', '120000', '111600'),
        ];
    }

    private function table(string $family, string $group, string $perDus, string $priceLevel, string $price, string $afterDisc): string
    {
        return '<Table>'
            ."<FamilyName>{$family}</FamilyName>"
            ."<PriceGroupName>{$group}</PriceGroupName>"
            ."<PriceGroupDescription>{$group}</PriceGroupDescription>"
            ."<PerDus>{$perDus}</PerDus>"
            ."<PriceLevelName>{$priceLevel}</PriceLevelName>"
            ."<Price>{$price}</Price>"
            ."<PriceAfterDisc>{$afterDisc}</PriceAfterDisc>"
            .'</Table>';
    }
}
