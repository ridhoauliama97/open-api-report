<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class DaftarHargaEnamelRetailReportFeatureTest extends TestCase
{
    public function test_daftar_harga_enamel_retail_pdf_endpoint_returns_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlPath = 'C:\\Users\\ridho\\AppData\\Local\\Temp\\Custom2084.xml';
        if (! file_exists($xmlPath)) {
            $this->markTestSkipped('Custom2084.xml not found.');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/check-price-group-a/daftar-harga-enamel-retail/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'xml_file' => new UploadedFile(
                $xmlPath,
                'Custom2084.xml',
                'text/xml',
                null,
                true
            ),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
