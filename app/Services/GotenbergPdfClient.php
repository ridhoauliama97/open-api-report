<?php

namespace App\Services;

use App\Exceptions\GotenbergConnectionException;
use App\Exceptions\GotenbergConversionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GotenbergPdfClient
{
    private const CONVERT_ENDPOINT = '/forms/chromium/convert/html';

    public function __construct(private readonly GotenbergThrottle $throttle) {}

    /**
     * Convert an HTML document into PDF bytes via a Gotenberg server.
     *
     * Conversions are throttled per bucket (interactive / warm / async) and
     * connection failures are retried with exponential backoff + jitter.
     * Gotenberg HTTP error responses (4xx/5xx) are never retried.
     *
     * @param  array<string, mixed>  $metrics  Output of {@see PdfGenerator::paperMetrics()}:
     *                                         paper_width, paper_height, landscape.
     * @param  array<string, string>  $options  Additional/override Gotenberg form
     *                                          parameters (marginTop, marginBottom, ...).
     *
     * @throws GotenbergConnectionException
     * @throws GotenbergConversionException
     * @throws GotenbergThrottleException
     */
    public function convertHtml(string $html, array $metrics = [], ?string $footerHtml = null, array $options = []): string
    {
        $baseUrl = trim((string) config('services.gotenberg.url'));

        if ($baseUrl === '') {
            throw new GotenbergConversionException('URL Gotenberg belum dikonfigurasi.');
        }

        $bucket = $options['bucket'] ?? GotenbergBucketContext::current();
        unset($options['bucket']);
        $metadata = $options['metadata'] ?? null;
        unset($options['metadata']);
        $slot = $this->throttle->acquire($bucket);

        try {
            return $this->convertHtmlWithRetry($baseUrl, $html, $metrics, $footerHtml, $options, $metadata);
        } finally {
            $this->throttle->release($slot);
        }
    }

    private function convertHtmlWithRetry(
        string $baseUrl,
        string $html,
        array $metrics,
        ?string $footerHtml,
        array $options,
        mixed $metadata,
    ): string {
        $attempts = max(1, (int) config('services.gotenberg.retry_attempts', 2));

        $request = Http::baseUrl($baseUrl)
            ->timeout((int) config('services.gotenberg.timeout', 300))
            ->connectTimeout((int) config('services.gotenberg.connect_timeout', 10))
            ->asMultipart()
            ->retry($attempts, fn (int $attempt): int => $this->backoffDelay($attempt), function (Throwable $exception): bool {
                return $exception instanceof ConnectionException;
            })
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

        if ($metadata !== null) {
            $formParams['metadata'] = is_array($metadata)
                ? json_encode($metadata, JSON_UNESCAPED_UNICODE)
                : (string) $metadata;
        }

        try {
            $response = $request->post(self::CONVERT_ENDPOINT, $formParams);
        } catch (ConnectionException $exception) {
            Log::error('Gotenberg request failed', [
                'endpoint' => self::CONVERT_ENDPOINT,
                'error' => $exception->getMessage(),
            ]);

            throw GotenbergConnectionException::unreachable();
        } catch (RequestException $exception) {
            $status = $exception->response?->status() ?? 0;

            Log::error('Gotenberg conversion failed', [
                'endpoint' => self::CONVERT_ENDPOINT,
                'status' => $status,
                'body' => mb_substr((string) ($exception->response?->body() ?? ''), 0, 1000),
            ]);

            throw GotenbergConversionException::fromStatus($status);
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

    /**
     * Exponential backoff (200, 400, 800... ms) with ±50% jitter.
     */
    private function backoffDelay(int $attempt): int
    {
        $base = 200 * (2 ** (max(1, $attempt) - 1));
        $jitter = (mt_rand() / mt_getrandmax()) - 0.5;

        return max(0, (int) round($base * (1 + $jitter)));
    }
}
