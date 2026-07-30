<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BiayaMobilTrukReportFeatureTest extends TestCase
{
    public function test_biaya_mobil_truk_pdf_download_endpoint_returns_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $token = $this->issueJwtForUser($user);

        $xmlPath = 'C:\\Users\\ridho\\AppData\\Local\\Temp\\Custom22.xml';
        if (! file_exists($xmlPath)) {
            $this->markTestSkipped('Custom22.xml not found.');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post('/api/internal/ascends/shared/custom-report/biaya-mobil-truk/pdf', [
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
            'StartDate' => '2026-01-01',
            'EndDate' => '2026-06-30',
            'xml_file' => new UploadedFile(
                $xmlPath,
                'Custom22.xml',
                'text/xml',
                null,
                true
            ),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
