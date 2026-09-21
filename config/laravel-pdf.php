<?php

use Spatie\LaravelPdf\Caching\DefaultPdfCache;

return [

    /*
    * The default driver to use for PDF generation.
    *
    * This project pins the default to "gotenberg". The "gotenberg" binding is
    * overridden in AppServiceProvider::register() with
    * App\Services\Pdf\GotenbergPipelineDriver, which delegates to
    * GotenbergPdfClient so bucket throttling, connection retry with backoff,
    * and GotenbergConnectionException/GotenbergConversionException -> 502
    * handling stay in a single code path (see AGENT_INSTRUCTIONS.md §10.1).
    *
    * Supported by the package: "browsershot", "cloudflare", "dompdf",
    * "gotenberg", "chrome" (and "weasyprint" when installed).
    */
    'driver' => env('LARAVEL_PDF_DRIVER', 'gotenberg'),

    /*
    * Render caching. When you call `->cache()` on a PDF, the generated
    * content is stored so identical renders are served from the cache.
    *
    * Note: report-level render caching conventions live in config/app.php
    * (pdf_render_cache_store / pdf_render_cache_ttl_seconds); keep this
    * automatic flag off to avoid double caching.
    */
    'cache' => [
        'class' => DefaultPdfCache::class,

        'automatic' => env('LARAVEL_PDF_CACHE_AUTOMATIC', false),

        'store' => env('LARAVEL_PDF_CACHE_STORE'),

        'prefix' => 'laravel-pdf',

        'ttl' => env('LARAVEL_PDF_CACHE_TTL', 60 * 60 * 24),
    ],

    /*
    * Gotenberg driver configuration.
    *
    * Requires a running Gotenberg instance (Docker recommended).
    * The URL reuses the same env var as services.gotenberg (GotenbergPdfClient)
    * so both paths always point at the same server.
    * https://gotenberg.dev
    */
    'gotenberg' => [
        'url' => env('GOTENBERG_URL', 'http://localhost:3000'),
        'username' => env('GOTENBERG_USERNAME'),
        'password' => env('GOTENBERG_PASSWORD'),
    ],

];
