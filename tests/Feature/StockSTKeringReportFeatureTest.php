<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FilePdfJobStore;
use App\Services\PdfGenerator;
use App\Services\StockSTKeringReportService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class StockSTKeringReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        File::deleteDirectory(storage_path('app/pdf-job-statuses'));
        Storage::disk((string) config('app.pdf_storage_disk', 'local'))->deleteDirectory(
            trim((string) config('app.pdf_storage_path', 'pdf_reports'), '/'),
        );

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('reports.report_auth.issuers', []);
        config()->set('reports.report_auth.audiences', []);
        config()->set('reports.report_auth.enforce_scope', false);
    }

    public function test_preview_pdf_renders_to_file_for_lower_memory_usage(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StockSTKeringReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-05-12')
            ->andReturn([
                [
                    'Jenis' => 'JABON',
                    'Produk' => 'KD',
                    'NoST' => 'ST-001',
                    'DateCreate' => '2026-05-12',
                    'Tebal' => 10,
                    'Lebar' => 20,
                    'Panjang' => 8,
                    'IdLokasi' => 'A1',
                    'Pcs' => 1,
                    'Ton' => '0.107',
                ],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('renderToFile')
            ->once()
            ->withArgs(function (string $view, array $data, string $outputPath): bool {
                file_put_contents($outputPath, '%PDF-1.4 mocked stock st kering');

                return $view === 'reports.sawn-timber.stock-st-kering-pdf'
                    && ($data['endDate'] ?? null) === '2026-05-12'
                    && is_string($outputPath);
            });

        $pdfGenerator
            ->shouldNotReceive('render');

        $this->app->instance(StockSTKeringReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/stock-st-kering/preview-pdf', [
                'end_date' => '2026-05-12',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Stock ST Kering');
    }

    public function test_web_async_creates_file_based_job_for_stock_st_kering(): void
    {
        $user = User::factory()->make([
            'Username' => 'tester',
            'Nama' => 'Tester',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/reports/sawn-timber/stock-st-kering/pdf/async', [
                'end_date' => '2026-05-12',
            ])
            ->assertAccepted()
            ->assertJsonPath('status', FilePdfJobStore::STATUS_QUEUED);

        $jobId = (string) $response->json('job_id');
        $job = app(FilePdfJobStore::class)->find($jobId);

        $this->assertSame('sawn-timber/stock-st-kering', $job['report_type'] ?? null);
        $this->assertSame(['end_date' => '2026-05-12'], $job['request_payload'] ?? null);
        $this->assertSame('tester', $job['requested_by'] ?? null);
    }

    public function test_api_async_reuses_completed_stock_st_kering_pdf_for_same_user_and_date(): void
    {
        $user = User::factory()->make([
            'Username' => 'tester',
            'Nama' => 'Tester',
        ]);
        $token = $this->issueJwtForUser($user, ['username' => 'tester', 'name' => 'Tester']);
        $jobStore = app(FilePdfJobStore::class);
        $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));
        $storagePath = trim((string) config('app.pdf_storage_path', 'pdf_reports'), '/').'/stock-st-kering-cached.pdf';

        $disk->put($storagePath, '%PDF-1.4 cached stock st kering');
        $job = $jobStore->create('sawn-timber/stock-st-kering', ['end_date' => '2026-05-12'], 'tester');
        $jobStore->markDone((string) $job['job_id'], $storagePath);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/reports/sawn-timber/stock-st-kering/pdf/async', [
                'end_date' => '2026-05-12',
            ])
            ->assertOk()
            ->assertJsonPath('job_id', $job['job_id'])
            ->assertJsonPath('status', FilePdfJobStore::STATUS_DONE)
            ->assertJsonPath('message', 'PDF sudah tersedia.');
    }

    public function test_api_async_reuses_shared_system_stock_st_kering_pdf_for_same_date(): void
    {
        $user = User::factory()->make([
            'Username' => 'frontend-user',
            'Nama' => 'Frontend User',
        ]);
        $token = $this->issueJwtForUser($user, ['username' => 'frontend-user', 'name' => 'Frontend User']);
        $jobStore = app(FilePdfJobStore::class);
        $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));
        $storagePath = trim((string) config('app.pdf_storage_path', 'pdf_reports'), '/').'/stock-st-kering-system.pdf';

        $disk->put($storagePath, '%PDF-1.4 shared stock st kering');
        $job = $jobStore->create('sawn-timber/stock-st-kering', ['end_date' => '2026-05-12'], 'system');
        $jobStore->markDone((string) $job['job_id'], $storagePath);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/reports/sawn-timber/stock-st-kering/pdf/async', [
                'end_date' => '2026-05-12',
            ])
            ->assertOk()
            ->assertJsonPath('job_id', $job['job_id'])
            ->assertJsonPath('status', FilePdfJobStore::STATUS_DONE)
            ->assertJsonPath('message', 'PDF sudah tersedia.');
    }

    public function test_api_async_does_not_reuse_shared_system_stock_st_kering_pdf_for_different_date(): void
    {
        $user = User::factory()->make([
            'Username' => 'frontend-user',
            'Nama' => 'Frontend User',
        ]);
        $token = $this->issueJwtForUser($user, ['username' => 'frontend-user', 'name' => 'Frontend User']);
        $jobStore = app(FilePdfJobStore::class);
        $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));
        $storagePath = trim((string) config('app.pdf_storage_path', 'pdf_reports'), '/').'/stock-st-kering-system.pdf';

        $disk->put($storagePath, '%PDF-1.4 shared stock st kering');
        $job = $jobStore->create('sawn-timber/stock-st-kering', ['end_date' => '2026-05-12'], 'system');
        $jobStore->markDone((string) $job['job_id'], $storagePath);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/reports/sawn-timber/stock-st-kering/pdf/async', [
                'end_date' => '2026-05-13',
            ])
            ->assertAccepted()
            ->assertJsonPath('status', FilePdfJobStore::STATUS_QUEUED);
    }

    public function test_api_pdf_endpoint_can_open_completed_stock_st_kering_pdf_by_job_id_without_end_date(): void
    {
        $user = User::factory()->make([
            'Username' => 'tester',
            'Nama' => 'Tester',
        ]);
        $token = $this->issueJwtForUser($user, ['username' => 'tester', 'name' => 'Tester']);
        $jobStore = app(FilePdfJobStore::class);
        $disk = Storage::disk((string) config('app.pdf_storage_disk', 'local'));
        $storagePath = trim((string) config('app.pdf_storage_path', 'pdf_reports'), '/').'/stock-st-kering-ready.pdf';

        $disk->put($storagePath, '%PDF-1.4 ready stock st kering');
        $job = $jobStore->create('sawn-timber/stock-st-kering', ['end_date' => '2026-05-12'], 'tester');
        $jobStore->markDone((string) $job['job_id'], $storagePath);

        $service = Mockery::mock(StockSTKeringReportService::class);
        $service->shouldNotReceive('fetch');
        $this->app->instance(StockSTKeringReportService::class, $service);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/reports/sawn-timber/stock-st-kering/pdf?job_id='.$job['job_id'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'stock st kering ready');
    }
}
