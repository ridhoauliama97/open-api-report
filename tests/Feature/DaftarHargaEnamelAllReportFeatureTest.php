<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DaftarHargaEnamelAllReportFeatureTest extends TestCase
{
    public function test_daftar_harga_enamel_all_pdf_download_works_without_db_company_name(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-enamel-all/pdf', [
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
                'family' => 'ENAMEL',
                'group' => 'SEKAR PIRING SOUP',
                'per_dus' => '12',
            ],
            [
                'family' => 'ENAMEL',
                'group' => 'BASKOM DALAM 24 CM',
                'per_dus' => '6',
            ],
        ] as $item) {
            $rows[] = $this->priceRows($item['family'], $item['group'], $item['per_dus']);
        }

        return '<NewDataSet>'.implode('', array_merge(...$rows)).'</NewDataSet>';
    }

    private function priceRows(string $family, string $group, string $perDus, string $conversion = '1'): array
    {
        return [
            $this->table($family, $group, $perDus, $conversion, '01. Harga Retail', '50000', '48500'),
            $this->table($family, $group, $perDus, $conversion, '02. Harga Semi Grosir', '50000', '47500'),
            $this->table($family, $group, $perDus, $conversion, '03. Harga Grosir', '50000', '46500'),
            $this->table($family, $group, $perDus, $conversion, '04. Harga Akun Special', '50000', '45500'),
        ];
    }

    private function table(string $family, string $group, string $perDus, string $conversion, string $priceLevel, string $price, string $afterDisc): string
    {
        return '<Table>'
            ."<FamilyName>{$family}</FamilyName>"
            ."<PriceGroupName>{$group}</PriceGroupName>"
            ."<PriceGroupDescription>{$group}</PriceGroupDescription>"
            ."<PerDus>{$perDus}</PerDus>"
            ."<Conversion>{$conversion}</Conversion>"
            ."<PriceLevelName>{$priceLevel}</PriceLevelName>"
            ."<Price>{$price}</Price>"
            ."<PriceAfterDisc>{$afterDisc}</PriceAfterDisc>"
            .'</Table>';
    }
}
