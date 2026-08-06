<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DaftarHargaEnamelGrosirReportFeatureTest extends TestCase
{
    public function test_daftar_harga_enamel_grosir_pdf_download_works_without_db_company_name(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-enamel-grosir/pdf', [
            'Sys_Username' => 'Ridho',
            'xml' => $this->sampleXml(),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    private function sampleXml(): string
    {
        $rows = [
            $this->table('ENAMEL', 'SEKAR PIRING SOUP', '12', '1', '01. Harga Retail', '50000', '48500'),
            $this->table('ENAMEL', 'SEKAR PIRING SOUP', '12', '1', '03. Harga Grosir', '50000', '46500'),
            $this->table('ENAMEL', 'BASKOM DALAM 24 CM', '6', '1', '01. Harga Retail', '50000', '48500'),
            $this->table('ENAMEL', 'BASKOM DALAM 24 CM', '6', '1', '03. Harga Grosir', '50000', '46500'),
        ];

        return '<NewDataSet>'.implode('', $rows).'</NewDataSet>';
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
            ."<PriceBeforeDisc>{$price}</PriceBeforeDisc>"
            ."<PriceAfterDisc>{$afterDisc}</PriceAfterDisc>"
            .'</Table>';
    }
}
