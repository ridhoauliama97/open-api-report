<?php

namespace Tests\Feature;

use Tests\TestCase;

class OpenApiFeatureTest extends TestCase
{
    /**
     * Execute test openapi json endpoint is available logic.
     */
    public function test_openapi_json_endpoint_is_available(): void
    {
        $this->get('/api/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonPath('paths./api/auth/register.post.summary', 'Registrasi user baru')
            ->assertJsonPath('paths./api/auth/login.post.summary', 'Login user')
            ->assertJsonPath('paths./api/auth/logout.post.summary', 'Logout user (invalidate token)')
            ->assertJsonPath('paths./api/auth/refresh.post.summary', 'Refresh access token')
            ->assertJsonPath('paths./api/auth/me.get.summary', 'Data user yang sedang login')
            ->assertJsonPath('paths./api/reports/mutasi-barang-jadi.post.summary', 'Preview data laporan mutasi barang jadi')
            ->assertJsonPath('paths./api/reports/mutasi-barang-jadi/pdf.post.summary', 'Generate laporan mutasi barang jadi PDF')
            ->assertJsonPath('paths./api/reports/mutasi-barang-jadi/health.post.summary', 'Cek kesehatan struktur output SP mutasi barang jadi')
            ->assertJsonPath('paths./api/reports/mutasi-finger-joint.post.summary', 'Preview data laporan mutasi finger joint')
            ->assertJsonPath('paths./api/reports/mutasi-finger-joint/pdf.post.summary', 'Generate laporan mutasi finger joint PDF')
            ->assertJsonPath('paths./api/reports/mutasi-finger-joint/health.post.summary', 'Cek kesehatan struktur output SP mutasi finger joint')
            ->assertJsonPath('paths./api/reports/mutasi-moulding.post.summary', 'Preview data laporan mutasi moulding')
            ->assertJsonPath('paths./api/reports/mutasi-moulding/pdf.post.summary', 'Generate laporan mutasi moulding PDF')
            ->assertJsonPath('paths./api/reports/mutasi-moulding/health.post.summary', 'Cek kesehatan struktur output SP mutasi moulding')
            ->assertJsonPath('paths./api/reports/mutasi-s4s.post.summary', 'Preview data laporan mutasi s4s')
            ->assertJsonPath('paths./api/reports/mutasi-s4s/pdf.post.summary', 'Generate laporan mutasi s4s PDF')
            ->assertJsonPath('paths./api/reports/mutasi-s4s/health.post.summary', 'Cek kesehatan struktur output SP mutasi s4s')
            ->assertJsonPath('paths./api/reports/mutasi-st.post.summary', 'Preview data laporan mutasi st')
            ->assertJsonPath('paths./api/reports/mutasi-st/pdf.post.summary', 'Generate laporan mutasi st PDF')
            ->assertJsonPath('paths./api/reports/mutasi-st/health.post.summary', 'Cek kesehatan struktur output SP mutasi st')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat.post.summary', 'Preview data laporan mutasi kayu bulat')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat/pdf.post.summary', 'Generate laporan mutasi kayu bulat PDF')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat/health.post.summary', 'Cek kesehatan struktur output SP mutasi kayu bulat')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat-v2.post.summary', 'Preview data laporan mutasi kayu bulat v2')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat-v2/pdf.post.summary', 'Generate laporan mutasi kayu bulat v2 PDF')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat-v2/health.post.summary', 'Cek kesehatan struktur output SP mutasi kayu bulat v2')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat-kgv2.post.summary', 'Preview data laporan mutasi kayu bulat kgv2')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat-kgv2/pdf.post.summary', 'Generate laporan mutasi kayu bulat kgv2 PDF')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat-kgv2/health.post.summary', 'Cek kesehatan struktur output SP mutasi kayu bulat kgv2')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat-kg.post.summary', 'Preview data laporan mutasi kayu bulat kg')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat-kg/pdf.post.summary', 'Generate laporan mutasi kayu bulat kg PDF')
            ->assertJsonPath('paths./api/reports/mutasi-kayu-bulat-kg/health.post.summary', 'Cek kesehatan struktur output SP mutasi kayu bulat kg')
            ->assertJsonPath('paths./api/reports/rangkuman-label-input.post.summary', 'Preview data laporan rangkuman jumlah label input')
            ->assertJsonPath('paths./api/reports/rangkuman-label-input/pdf.post.summary', 'Generate laporan rangkuman jumlah label input PDF')
            ->assertJsonPath('paths./api/reports/rangkuman-label-input/health.post.summary', 'Cek kesehatan struktur output SPWps_LapRangkumanJlhLabelInput')
            ->assertJsonPath('paths./api/reports/mutasi-hasil-racip.post.summary', 'Preview data laporan mutasi hasil racip')
            ->assertJsonPath('paths./api/reports/mutasi-hasil-racip/pdf.post.summary', 'Generate laporan mutasi hasil racip PDF')
            ->assertJsonPath('paths./api/reports/mutasi-hasil-racip/health.post.summary', 'Cek kesehatan struktur output SPWps_LapMutasiHasilRacip')
            ->assertJsonPath('paths./api/reports/label-nyangkut.post.summary', 'Preview data laporan label nyangkut')
            ->assertJsonPath('paths./api/reports/label-nyangkut/pdf.post.summary', 'Generate laporan label nyangkut PDF')
            ->assertJsonPath('paths./api/reports/label-nyangkut/health.post.summary', 'Cek kesehatan struktur output SPWps_LapLabelNyangkut')
            ->assertJsonMissingPath('paths./api/reports/sales')
            ->assertJsonPath('components.securitySchemes.bearerAuth.scheme', 'bearer')
            ->assertJsonPath('components.securitySchemes.bearerAuth.bearerFormat', 'JWT')
            ->assertJsonPath('components.schemas.AuthTokenResponse.properties.token_type.example', 'bearer');
    }
}
