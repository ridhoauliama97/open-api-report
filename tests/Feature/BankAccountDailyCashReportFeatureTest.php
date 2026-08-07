<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BankAccountDailyCashReportFeatureTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
<?xml version="1.0" standalone="yes"?>
<NewDataSet>
  <Table>
    <BankAccountCode>111.102.101</BankAccountCode>
    <BankName>BCA</BankName>
    <ReceiveDate>2026-07-31T00:00:00+07:00</ReceiveDate>
    <ReceiveNo>RV/IM (JU)/26/07/0022</ReceiveNo>
    <ReceiveRemarks>BCA GSU IDR</ReceiveRemarks>
    <ReceiveAmount>55323.0500</ReceiveAmount>
    <PaymentDate>2026-07-31T00:00:00+07:00</PaymentDate>
    <PaymentNo>PV/AP/26/07/0292</PaymentNo>
    <PaymentRemarks>BCA GSU IDR</PaymentRemarks>
    <PaymentAmount>11064.6100</PaymentAmount>
  </Table>
  <Table>
    <BankAccountCode>111.102.101</BankAccountCode>
    <BankName>BCA</BankName>
    <ReceiveDate>2026-07-31T00:00:00+07:00</ReceiveDate>
    <ReceiveNo>RV/IM/26/07/0226</ReceiveNo>
    <ReceiveRemarks>UD. ASIA JAYA RAYA PERABOT/ INTERNATIONAL MEBEL</ReceiveRemarks>
    <ReceiveAmount>699248.0000</ReceiveAmount>
  </Table>
  <Table>
    <BankAccountCode>111.102.109</BankAccountCode>
    <BankName>BRI</BankName>
    <ReceiveDate>2026-07-31T00:00:00+07:00</ReceiveDate>
    <ReceiveNo>RV/IM/26/07/0221</ReceiveNo>
    <ReceiveRemarks>AP</ReceiveRemarks>
    <ReceiveAmount>41200000.0000</ReceiveAmount>
  </Table>
</NewDataSet>
XML;

    public function test_bank_account_daily_cash_pdf_download_works(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('CustomReport.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/bank-account-daily-cash/pdf', [
            'xml_file' => $xmlFile,
            'DB_CompanyName' => 'GSU',
            'Sys_Username' => 'Ridho',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeaderContains('Content-Disposition', 'filename="Laporan Kas Harian GSU.pdf"');
    }

    public function test_bank_account_daily_cash_pdf_requires_db_company_name(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
        ]);

        $token = $this->issueJwtForUser($user);

        $xmlFile = UploadedFile::fake()->createWithContent('CustomReport.xml', self::SAMPLE_XML);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/internal/ascends/shared/custom-report/bank-account-daily-cash/pdf', [
            'xml_file' => $xmlFile,
            'Sys_Username' => 'Ridho',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Field DB_CompanyName wajib dikirim.']);
    }
}
