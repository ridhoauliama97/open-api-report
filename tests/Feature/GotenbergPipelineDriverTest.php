<?php

namespace Tests\Feature;

use App\Services\Pdf\GotenbergPipelineDriver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Spatie\LaravelPdf\Drivers\PdfDriver;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\TestCase;

class GotenbergPipelineDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.gotenberg.url', 'http://gotenberg-test:3000');
        Http::fake([
            'http://gotenberg-test:3000/*' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
        ]);
    }

    /**
     * Execute test default pdf driver resolves to pipeline driver logic.
     */
    public function test_default_pdf_driver_resolves_to_pipeline_driver(): void
    {
        $driver = app(PdfDriver::class);

        $this->assertInstanceOf(GotenbergPipelineDriver::class, $driver);
        $this->assertSame($driver, app('laravel-pdf.driver.gotenberg'));
    }

    /**
     * Execute test pdf facade routes through gotenberg pipeline with mapped metrics logic.
     */
    public function test_pdf_facade_routes_through_gotenberg_pipeline_with_mapped_metrics(): void
    {
        $content = Pdf::html('<html><body>report</body></html>')
            ->format('A4')
            ->landscape()
            ->generatePdfContent();

        $this->assertSame('%PDF-1.4 fake', $content);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://gotenberg-test:3000/forms/chromium/convert/html'
            && str_contains((string) $request->body(), 'filename="index.html"')
            && str_contains((string) $request->body(), 'name="paperWidth"')
            && str_contains((string) $request->body(), 'name="paperHeight"')
            && str_contains((string) $request->body(), '21.0cm')
            && str_contains((string) $request->body(), '29.7cm')
            && str_contains((string) $request->body(), 'name="landscape"')
            && str_contains((string) $request->body(), 'true'));
    }

    /**
     * Execute test custom paper size and margins are forwarded as gotenberg form fields logic.
     */
    public function test_custom_paper_size_and_margins_are_forwarded_as_gotenberg_form_fields(): void
    {
        Pdf::html('<html><body>report</body></html>')
            ->paperSize(210, 297, 'mm')
            ->margins(top: 12, bottom: 15)
            ->scale(1.25)
            ->pageRanges('1-3')
            ->generatePdfContent();

        Http::assertSent(fn (Request $request): bool => str_contains((string) $request->body(), '210mm')
            && str_contains((string) $request->body(), '297mm')
            && str_contains((string) $request->body(), 'name="marginTop"')
            && str_contains((string) $request->body(), '12mm')
            && str_contains((string) $request->body(), 'name="marginBottom"')
            && str_contains((string) $request->body(), '15mm')
            && str_contains((string) $request->body(), 'name="marginLeft"')
            && str_contains((string) $request->body(), '0mm')
            && str_contains((string) $request->body(), 'name="scale"')
            && str_contains((string) $request->body(), '1.25')
            && str_contains((string) $request->body(), 'name="nativePageRanges"')
            && str_contains((string) $request->body(), '1-3'));
    }

    /**
     * Execute test footer html is attached as separate gotenberg part logic.
     */
    public function test_footer_html_is_attached_as_separate_gotenberg_part(): void
    {
        Pdf::html('<html><body>report</body></html>')
            ->footerHtml('<html><body>footer</body></html>')
            ->generatePdfContent();

        Http::assertSent(fn (Request $request): bool => str_contains((string) $request->body(), 'filename="footer.html"'));
    }

    /**
     * Execute test header html is rejected because pipeline has no header part logic.
     */
    public function test_header_html_is_rejected_because_pipeline_has_no_header_part(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Pdf::html('<html><body>report</body></html>')
            ->headerHtml('<html><body>header</body></html>')
            ->generatePdfContent();
    }

    /**
     * Execute test wait until ready expression is forwarded to gotenberg logic.
     */
    public function test_wait_until_ready_expression_is_forwarded_to_gotenberg(): void
    {
        Pdf::html('<html><body>report</body></html>')
            ->waitUntilReady("window.status === 'ready'")
            ->generatePdfContent();

        Http::assertSent(fn (Request $request): bool => str_contains((string) $request->body(), 'name="waitForExpression"')
            && str_contains((string) $request->body(), 'window.status'));
    }
}
