<?php

namespace Tests\Feature;

use App\Exceptions\GotenbergThrottleException;
use App\Models\User;
use App\Services\GotenbergPdfClient;
use App\Services\MutasiBarangJadiReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class GotenbergPdfThrottleTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['gotenberg:interactive:1', 'gotenberg:interactive:2', 'gotenberg:warm:1', 'gotenberg:async:1'] as $key) {
            optional(Cache::lock($key))->release();
        }

        parent::tearDown();
    }

    /**
     * Execute test convert html throws throttle exception when all interactive slots busy logic.
     */
    public function test_convert_html_throws_throttle_exception_when_all_interactive_slots_busy(): void
    {
        config()->set('services.gotenberg.wait_interactive_seconds', 0);
        config()->set('services.gotenberg.max_interactive', 2);

        $this->assertTrue(Cache::lock('gotenberg:interactive:1')->get());
        $this->assertTrue(Cache::lock('gotenberg:interactive:2')->get());

        Http::fake();

        $this->expectException(GotenbergThrottleException::class);
        $this->expectExceptionMessage('Antrian konversi PDF (interactive) penuh. Coba lagi beberapa saat.');

        app(GotenbergPdfClient::class)->convertHtml('<html></html>');
    }

    /**
     * Execute test controller returns 503 with retry after header when bucket saturated logic.
     */
    public function test_controller_returns_503_with_retry_after_header_when_bucket_saturated(): void
    {
        config()->set('services.gotenberg.wait_interactive_seconds', 0);

        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiBarangJadiReportService::class);
        $service->shouldReceive('fetch')->once()->andReturn([]);
        $service->shouldReceive('fetchSubReport')->once()->andReturn([]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator->shouldReceive('renderHtml')->once()->andReturn('<html></html>');
        $pdfGenerator->shouldReceive('paperMetrics')->once()->andReturn([]);

        $this->app->instance(MutasiBarangJadiReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->assertTrue(Cache::lock('gotenberg:interactive:1')->get());
        $this->assertTrue(Cache::lock('gotenberg:interactive:2')->get());

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->issueJwtForUser($user),
            'Accept' => 'application/json',
        ])
            ->get('/api/reports/mutasi-barang-jadi/pdf?TglAwal=2026-01-01&TglAkhir=2026-01-31')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '5');
    }

    /**
     * Execute test throttle exception carries retry after seconds from config logic.
     */
    public function test_throttle_exception_carries_retry_after_seconds_from_config(): void
    {
        config()->set('services.gotenberg.throttle_retry_after', 7);

        $this->assertSame(7, GotenbergThrottleException::busy('interactive')->retryAfterSeconds);
    }

    /**
     * Execute test convert html releases the interactive slot after conversion logic.
     */
    public function test_convert_html_releases_slot_after_conversion(): void
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response('%PDF-1.4 fake', 200),
        ]);

        app(GotenbergPdfClient::class)->convertHtml('<html><body>report</body></html>');

        $this->assertTrue(Cache::lock('gotenberg:interactive:1')->get());
    }

    /**
     * Execute test convert html does not send bucket as gotenberg form field logic.
     */
    public function test_convert_html_does_not_send_bucket_as_form_field(): void
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response('%PDF-1.4 fake', 200),
        ]);

        app(GotenbergPdfClient::class)->convertHtml('<html></html>', [], null, ['bucket' => 'warm']);

        Http::assertSent(fn (Request $request): bool => ! str_contains((string) $request->body(), 'name="bucket"'));
    }
}
