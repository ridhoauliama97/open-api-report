<?php

namespace App\Services\Pdf;

use App\Services\GotenbergPdfClient;
use App\Services\PdfGenerator;
use InvalidArgumentException;
use Spatie\LaravelPdf\Drivers\PdfDriver;
use Spatie\LaravelPdf\Drivers\SupportsReadiness;
use Spatie\LaravelPdf\Enums\Orientation;
use Spatie\LaravelPdf\PdfOptions;

/**
 * Spatie Laravel-Pdf driver that delegates rendering to the project's
 * Gotenberg pipeline ({@see GotenbergPdfClient}).
 *
 * Delegating instead of calling Gotenberg directly keeps the P3 behavior in a
 * single code path for every consumer of the `Pdf` facade: bucket throttling
 * (GotenbergThrottle), connection retry with backoff, and the
 * GotenbergConnectionException / GotenbergConversionException -> 502 mapping.
 *
 * Header HTML is rejected because the pipeline only supports a separate
 * footer part; embed headers in the main view instead.
 */
class GotenbergPipelineDriver implements PdfDriver, SupportsReadiness
{
    public function __construct(
        private readonly GotenbergPdfClient $client,
        private readonly PdfGenerator $pdfGenerator,
    ) {}

    public function generatePdf(string $html, ?string $headerHtml, ?string $footerHtml, PdfOptions $options): string
    {
        if ($headerHtml !== null && trim($headerHtml) !== '') {
            throw new InvalidArgumentException(
                'Header HTML terpisah belum didukung pipeline Gotenberg; sisipkan header pada view utama.'
            );
        }

        return $this->client->convertHtml(
            $html,
            $this->metrics($options),
            $footerHtml,
            $this->formOptions($options),
        );
    }

    public function savePdf(string $html, ?string $headerHtml, ?string $footerHtml, PdfOptions $options, string $path): void
    {
        file_put_contents($path, $this->generatePdf($html, $headerHtml, $footerHtml, $options));
    }

    /**
     * Map PdfOptions to the paper metrics contract of GotenbergPdfClient
     * (paper_width, paper_height, landscape), reusing the canonical
     * {@see PdfGenerator::paperMetrics()} dimension table (cm).
     *
     * @return array{paper_width?: string, paper_height?: string, landscape?: bool}
     */
    private function metrics(PdfOptions $options): array
    {
        if ($options->paperSize !== null) {
            $unit = $options->paperSize['unit'] ?? 'mm';

            return [
                'paper_width' => $options->paperSize['width'].$unit,
                'paper_height' => $options->paperSize['height'].$unit,
                'landscape' => $options->orientation === Orientation::Landscape->value,
            ];
        }

        if ($options->format !== null) {
            return $this->pdfGenerator->paperMetrics($options->format, $options->orientation ?? '');
        }

        // No explicit paper options: GotenbergPdfClient applies its own
        // defaults (A4 portrait, 10mm margins), matching the controller path.
        return [];
    }

    /**
     * Map the remaining PdfOptions onto Gotenberg form parameters. The client
     * merges these over its defaults (printBackground, 10mm margins), so only
     * explicitly requested values are sent.
     *
     * @return array<string, string>
     */
    private function formOptions(PdfOptions $options): array
    {
        $form = [];

        if ($options->margins !== null) {
            $unit = $options->margins['unit'] ?? 'mm';
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                if (array_key_exists($side, $options->margins)) {
                    $form['margin'.ucfirst($side)] = $options->margins[$side].$unit;
                }
            }
        }

        if ($options->scale !== null) {
            $form['scale'] = (string) $options->scale;
        }

        if ($options->pageRanges !== null) {
            $form['nativePageRanges'] = $options->pageRanges;
        }

        if ($options->tagged) {
            $form['generateTaggedPdf'] = 'true';
        }

        if ($options->documentOutline) {
            $form['generateDocumentOutline'] = 'true';
        }

        if ($options->waitForReady !== null) {
            $form['waitForExpression'] = $options->waitForReady;
        }

        return $form;
    }
}
