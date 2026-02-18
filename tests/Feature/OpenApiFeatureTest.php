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
            ->assertJsonPath('paths./api/reports/rangkuman-label-input.post.summary', 'Preview data laporan rangkuman jumlah label input')
            ->assertJsonPath('paths./api/reports/rangkuman-label-input/pdf.post.summary', 'Generate laporan rangkuman jumlah label input PDF')
            ->assertJsonPath('paths./api/reports/rangkuman-label-input/health.post.summary', 'Cek kesehatan struktur output SPWps_LapRangkumanJlhLabelInput')
            ->assertJsonMissingPath('paths./api/reports/sales')
            ->assertJsonPath('components.securitySchemes.bearerAuth.scheme', 'bearer')
            ->assertJsonPath('components.securitySchemes.bearerAuth.bearerFormat', 'JWT')
            ->assertJsonPath('components.schemas.AuthTokenResponse.properties.token_type.example', 'bearer');
    }
}
