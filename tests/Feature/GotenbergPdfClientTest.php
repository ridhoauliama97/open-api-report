<?php

namespace Tests\Feature;

use App\Exceptions\GotenbergConnectionException;
use App\Exceptions\GotenbergConversionException;
use App\Services\GotenbergPdfClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GotenbergPdfClientTest extends TestCase
{
    /**
     * Execute test convert html returns pdf bytes and sends expected multipart parts logic.
     */
    public function test_convert_html_returns_pdf_bytes_and_sends_expected_multipart_parts(): void
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $pdf = app(GotenbergPdfClient::class)->convertHtml(
            '<html><body>report</body></html>',
            ['paper_width' => '21.0cm', 'paper_height' => '29.7cm', 'landscape' => true],
            '<html><body>footer</body></html>'
        );

        $this->assertSame('%PDF-1.4 fake', $pdf);

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && str_contains((string) $request->body(), 'filename="index.html"')
            && str_contains((string) $request->body(), 'filename="footer.html"')
            && str_contains((string) $request->body(), 'name="paperWidth"')
            && str_contains((string) $request->body(), '21.0cm')
            && str_contains((string) $request->body(), 'landscape'));
    }

    /**
     * Execute test convert html omits footer part when footer html is null logic.
     */
    public function test_convert_html_omits_footer_part_when_footer_html_is_null(): void
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response('%PDF-1.4 fake', 200),
        ]);

        app(GotenbergPdfClient::class)->convertHtml('<html><body>report</body></html>', []);

        Http::assertSent(fn (Request $request): bool => ! str_contains((string) $request->body(), 'filename="footer.html"'));
    }

    /**
     * Execute test convert html throws connection exception when gotenberg unreachable logic.
     */
    public function test_convert_html_throws_connection_exception_when_gotenberg_unreachable(): void
    {
        Http::fake([
            'http://localhost:3000/*' => Http::failedConnection(),
        ]);

        $this->expectException(GotenbergConnectionException::class);
        $this->expectExceptionMessage('Gotenberg tidak dapat dihubungi. Coba lagi nanti.');

        app(GotenbergPdfClient::class)->convertHtml('<html></html>');
    }

    /**
     * Execute test convert html throws conversion exception when gotenberg returns http error logic.
     */
    public function test_convert_html_throws_conversion_exception_when_gotenberg_returns_http_error(): void
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response('boom', 500),
        ]);

        $this->expectException(GotenbergConversionException::class);
        $this->expectExceptionMessage('Gotenberg gagal memproses dokumen (HTTP 500). Coba lagi nanti.');

        app(GotenbergPdfClient::class)->convertHtml('<html></html>');
    }
}
