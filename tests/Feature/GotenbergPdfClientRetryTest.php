<?php

namespace Tests\Feature;

use App\Exceptions\GotenbergConnectionException;
use App\Exceptions\GotenbergConversionException;
use App\Services\GotenbergPdfClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GotenbergPdfClientRetryTest extends TestCase
{
    /**
     * Execute test connection failures are retried until success logic.
     */
    public function test_connection_failures_are_retried_until_success(): void
    {
        Http::fake([
            'http://localhost:3000/*' => Http::sequence()
                ->pushFailedConnection()
                ->push('%PDF-1.4 retried', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $pdf = app(GotenbergPdfClient::class)->convertHtml('<html></html>');

        $this->assertSame('%PDF-1.4 retried', $pdf);
    }

    /**
     * Execute test gotenberg http error responses are not retried logic.
     */
    public function test_gotenberg_http_error_responses_are_not_retried(): void
    {
        Http::fake([
            'http://localhost:3000/*' => Http::sequence()
                ->push('boom', 500, ['Content-Type' => 'text/plain'])
                ->push('%PDF-1.4 should-not-happen', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $this->expectException(GotenbergConversionException::class);

        try {
            app(GotenbergPdfClient::class)->convertHtml('<html></html>');
        } finally {
            Http::assertSentCount(1);
        }
    }

    /**
     * Execute test connection failures give up after configured attempts logic.
     */
    public function test_connection_failures_give_up_after_configured_attempts(): void
    {
        config()->set('services.gotenberg.retry_attempts', 2);

        Http::fake([
            'http://localhost:3000/*' => Http::failedConnection(),
        ]);

        $this->expectException(GotenbergConnectionException::class);

        try {
            app(GotenbergPdfClient::class)->convertHtml('<html></html>');
        } finally {
            Http::assertSentCount(2);
        }
    }
}
