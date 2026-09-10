<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\GotenbergConnectionException;
use App\Exceptions\GotenbergConversionException;
use App\Services\GotenbergPdfClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait BuildsGotenbergPdfResponses
{
    /**
     * Render report HTML to PDF via Gotenberg and build the HTTP response.
     *
     * Failure modes are handled separately:
     * - Gotenberg unreachable            → 502 (upstream down)
     * - Gotenberg client error (4xx)     → 500 (our request was malformed)
     * - Gotenberg server error (5xx)     → 502 (bad gateway)
     *
     * @param  array<string, mixed>  $metrics  Output of PdfGenerator::paperMetrics()
     * @param  array<string, string>  $options  Extra Gotenberg form parameters
     * @param  array<string, string>  $extraHeaders  Overrides/additions to the base response headers
     */
    protected function buildGotenbergPdfResponse(
        Request $request,
        string $html,
        string $filename,
        array $metrics = [],
        ?string $footerHtml = null,
        string $disposition = 'attachment',
        array $options = [],
        array $extraHeaders = [],
    ): Response|JsonResponse|RedirectResponse {
        try {
            $pdfBytes = app(GotenbergPdfClient::class)->convertHtml($html, $metrics, $footerHtml, $options);
        } catch (GotenbergConnectionException $exception) {
            return $this->gotenbergFailureResponse($request, $exception->getMessage(), 502);
        } catch (GotenbergConversionException $exception) {
            $upstreamStatus = $exception->status();
            $httpStatus = $upstreamStatus !== null && $upstreamStatus >= 500 ? 502 : 500;

            return $this->gotenbergFailureResponse($request, $exception->getMessage(), $httpStatus);
        }

        return response($pdfBytes, 200, array_merge([
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
        ], $extraHeaders));
    }

    /**
     * Build a failure response when the PDF conversion service is unreachable
     * or returns an error. API/JSON callers receive JSON; web callers get
     * redirected back with an error bag entry.
     */
    protected function gotenbergFailureResponse(
        Request $request,
        string $message,
        int $status = 502,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], $status);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $message]);
    }
}
