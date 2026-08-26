<?php

namespace App\Services;

use App\Exceptions\GotenbergConnectionException;
use App\Exceptions\GotenbergConversionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GotenbergPdfClient
{
    private const CONVERT_ENDPOINT = '/forms/chromium/convert/html';

    /**
     * Convert an HTML document into PDF bytes via a Gotenberg server.
     *
     * @param  array<string, mixed>  $metrics  Output of {@see PdfGenerator::paperMetrics()}:
     *                                         paper_width, paper_height, landscape.
     * @param  array<string, string>  $options  Additional/override Gotenberg form
     *                                          parameters (marginTop, marginBottom, ...).
     *
     * @throws GotenbergConnectionException
     * @throws GotenbergConversionException
     */
    public function convertHtml(string $html, array $metrics = [], ?string $footerHtml = null, array $options = []): string
    {
        $baseUrl = trim((string) config('services.gotenberg.url'));

        if ($baseUrl === '') {
            throw new GotenbergConversionException('URL Gotenberg belum dikonfigurasi.');
        }

        $request = Http::baseUrl($baseUrl)
            ->timeout((int) config('services.gotenberg.timeout', 300))
            ->connectTimeout((int) config('services.gotenberg.connect_timeout', 10))
            ->asMultipart()
            ->attach('files', $html, 'index.html');

        if ($footerHtml !== null && trim($footerHtml) !== '') {
            $request = $request->attach('files', $footerHtml, 'footer.html');
        }

        $formParams = array_merge([
            'paperWidth' => (string) ($metrics['paper_width'] ?? '21.0cm'),
            'paperHeight' => (string) ($metrics['paper_height'] ?? '29.7cm'),
            'landscape' => filter_var($metrics['landscape'] ?? false, FILTER_VALIDATE_BOOL) ? 'true' : 'false',
            'printBackground' => 'true',
            'marginTop' => '10mm',
            'marginBottom' => '10mm',
            'marginLeft' => '10mm',
            'marginRight' => '10mm',
        ], $options);

        try {
            $response = $request->post(self::CONVERT_ENDPOINT, $formParams);
        } catch (ConnectionException $exception) {
            Log::error('Gotenberg request failed', [
                'endpoint' => self::CONVERT_ENDPOINT,
                'error' => $exception->getMessage(),
            ]);

            throw GotenbergConnectionException::unreachable();
        }

        if ($response->failed()) {
            Log::error('Gotenberg conversion failed', [
                'endpoint' => self::CONVERT_ENDPOINT,
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 1000),
            ]);

            throw GotenbergConversionException::fromStatus($response->status());
        }

        return $response->body();
    }
}
