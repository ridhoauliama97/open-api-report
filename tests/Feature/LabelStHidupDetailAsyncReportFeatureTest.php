<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FilePdfJobStore;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LabelStHidupDetailAsyncReportFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:o3o/5d9ChKozyW6gCUVa7mja466h05CMsFIsCYN9HkU=');
        config()->set('reports.report_auth.issuers', []);
        config()->set('reports.report_auth.audiences', []);

        File::deleteDirectory(storage_path('app/pdf-job-statuses'));
        Storage::disk('local')->deleteDirectory('pdf_reports');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/pdf-job-statuses'));
        Storage::disk('local')->deleteDirectory('pdf_reports');

        parent::tearDown();
    }

    public function test_web_async_endpoint_creates_file_based_pdf_job(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'Username' => 'tester',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/reports/sawn-timber/label-st-hidup-detail/pdf/async')
            ->assertAccepted()
            ->assertJsonPath('status', 'queued')
            ->assertJsonPath('message', 'PDF sedang diproses di background.');

        $jobId = (string) $response->json('job_id');
        $statusFile = storage_path("app/pdf-job-statuses/{$jobId}.json");

        $this->assertFileExists($statusFile);

        $status = json_decode((string) file_get_contents($statusFile), true);

        $this->assertSame('sawn-timber/label-st-hidup-detail', $status['report_type'] ?? null);
        $this->assertSame('queued', $status['status'] ?? null);
        $this->assertSame('tester', $status['requested_by'] ?? null);
    }

    public function test_api_async_endpoint_creates_file_based_pdf_job(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'Username' => 'tester',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->issueJwtForUser($user))
            ->postJson('/api/reports/sawn-timber/label-st-hidup-detail/pdf/async')
            ->assertAccepted()
            ->assertJsonPath('status', 'queued')
            ->assertJsonPath('message', 'PDF sedang diproses di background.');

        $this->assertStringContainsString(
            '/api/reports/sawn-timber/label-st-hidup-detail/jobs/',
            (string) $response->json('status_url'),
        );
    }

    public function test_api_pdf_endpoint_can_stream_completed_file_job_inline(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'Username' => 'tester',
        ]);

        $jobStore = app(FilePdfJobStore::class);
        $job = $jobStore->create('sawn-timber/label-st-hidup-detail', [], 'tester');
        Storage::disk('local')->put('pdf_reports/test-label-st-hidup-detail.pdf', '%PDF-1.4 test');
        $jobStore->markDone((string) $job['job_id'], 'pdf_reports/test-label-st-hidup-detail.pdf');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->issueJwtForUser($user))
            ->get('/api/reports/sawn-timber/label-st-hidup-detail/pdf?job_id='.$job['job_id'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'test label st hidup detail');
        $this->assertSame('%PDF-1.4 test', $response->getContent());
    }

    public function test_api_async_endpoint_reuses_completed_pdf_for_same_user(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'Username' => 'tester',
        ]);

        $jobStore = app(FilePdfJobStore::class);
        $job = $jobStore->create('sawn-timber/label-st-hidup-detail', [], 'tester');
        Storage::disk('local')->put('pdf_reports/test-label-st-hidup-detail.pdf', '%PDF-1.4 test');
        $jobStore->markDone((string) $job['job_id'], 'pdf_reports/test-label-st-hidup-detail.pdf');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->issueJwtForUser($user))
            ->postJson('/api/reports/sawn-timber/label-st-hidup-detail/pdf/async')
            ->assertOk()
            ->assertJsonPath('job_id', $job['job_id'])
            ->assertJsonPath('status', 'done')
            ->assertJsonPath('message', 'PDF sudah tersedia.');

        $this->assertNotEmpty($response->json('pdf_url'));
        $this->assertNotEmpty($response->json('download_url'));
    }

    public function test_api_async_endpoint_reuses_shared_system_pdf_for_any_user(): void
    {
        $user = User::factory()->make([
            'id' => 2,
            'Username' => 'different-user',
        ]);

        $jobStore = app(FilePdfJobStore::class);
        $job = $jobStore->create('sawn-timber/label-st-hidup-detail', ['warmup' => true], 'system');
        Storage::disk('local')->put('pdf_reports/shared-label-st-hidup-detail.pdf', '%PDF-1.4 shared');
        $jobStore->markDone((string) $job['job_id'], 'pdf_reports/shared-label-st-hidup-detail.pdf');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$this->issueJwtForUser($user, ['username' => 'different-user']))
            ->postJson('/api/reports/sawn-timber/label-st-hidup-detail/pdf/async')
            ->assertOk()
            ->assertJsonPath('job_id', $job['job_id'])
            ->assertJsonPath('status', 'done')
            ->assertJsonPath('message', 'PDF sudah tersedia.');

        $this->assertStringContainsString('job_id='.$job['job_id'], (string) $response->json('pdf_url'));
    }
}
