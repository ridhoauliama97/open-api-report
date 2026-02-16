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
            ->assertJsonMissingPath('paths./api/reports/sales')
            ->assertJsonPath('components.securitySchemes.bearerAuth.scheme', 'bearer')
            ->assertJsonPath('components.securitySchemes.bearerAuth.bearerFormat', 'JWT')
            ->assertJsonPath('components.schemas.AuthTokenResponse.properties.token_type.example', 'bearer');
    }
}
