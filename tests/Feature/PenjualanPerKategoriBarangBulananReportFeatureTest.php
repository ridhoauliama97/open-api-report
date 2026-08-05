<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PenjualanPerKategoriBarangBulananReportFeatureTest extends TestCase
{
    public function test_penjualan_per_kategori_barang_bulanan_pdf_download_returns_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlPath = 'C:\\Users\\ridho\\AppData\\Local\\Temp\\Custom23.xml';
        if (! file_exists($xmlPath)) {
            $this->markTestSkipped('Custom23.xml not found.');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/penjualan-per-kategori-barang-bulanan/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
            'JumlahHariKerja' => 26,
            'xml_file' => new UploadedFile(
                $xmlPath,
                'Custom23.xml',
                'text/xml',
                null,
                true
            ),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_penjualan_per_kategori_barang_bulanan_pdf_accepts_target_map(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlPath = 'C:\\Users\\ridho\\AppData\\Local\\Temp\\Custom23.xml';
        if (! file_exists($xmlPath)) {
            $this->markTestSkipped('Custom23.xml not found.');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/penjualan-per-kategori-barang-bulanan/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
            'JumlahHariKerja' => 26,
            'Target' => json_encode([
                'FENDY' => ['pf' => 0, 'pk1' => 2076540375, 'pk2' => 1118137125, 'enamel' => 410744250, 'fl' => 45638250],
            ]),
            'xml_file' => new UploadedFile(
                $xmlPath,
                'Custom23.xml',
                'text/xml',
                null,
                true
            ),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_penjualan_per_kategori_barang_bulanan_pdf_accepts_pk1_only_target(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlPath = 'C:\\Users\\ridho\\AppData\\Local\\Temp\\Custom23.xml';
        if (! file_exists($xmlPath)) {
            $this->markTestSkipped('Custom23.xml not found.');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/penjualan-per-kategori-barang-bulanan/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-07-01',
            'EndDate' => '2026-07-31',
            'JumlahHariKerja' => 26,
            'Target' => json_encode(['FENDY' => 2076540375]),
            'xml_file' => new UploadedFile(
                $xmlPath,
                'Custom23.xml',
                'text/xml',
                null,
                true
            ),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
