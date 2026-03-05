<?php

namespace App\Http\Middleware;

use App\Support\ReportFilenameFormatter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizePdfDownloadFilename
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($this->shouldSkip($request, $response)) {
            return $response;
        }

        $contentDisposition = (string) $response->headers->get('Content-Disposition', '');
        if ($contentDisposition === '') {
            return $response;
        }

        $filename = $this->extractFilename($contentDisposition);
        if ($filename === null || $filename === '') {
            return $response;
        }

        $formattedFilename = ReportFilenameFormatter::fromLegacy($filename);
        $dispositionType = $this->extractDispositionType($contentDisposition);

        $response->headers->set(
            'Content-Disposition',
            sprintf(
                '%s; filename="%s"; filename*=UTF-8\'\'%s',
                $dispositionType,
                addcslashes($formattedFilename, "\"\\"),
                rawurlencode($formattedFilename)
            )
        );

        return $response;
    }

    private function shouldSkip(Request $request, Response $response): bool
    {
        $action = (string) optional($request->route())->getActionName();
        if (str_contains($action, '\\PPS\\')) {
            return true;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        return !str_contains($contentType, 'application/pdf');
    }

    private function extractDispositionType(string $contentDisposition): string
    {
        if (preg_match('/^\s*([a-zA-Z]+)/', $contentDisposition, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return 'attachment';
    }

    private function extractFilename(string $contentDisposition): ?string
    {
        if (preg_match("~filename\\*=UTF-8''([^;]+)~i", $contentDisposition, $matches) === 1) {
            return rawurldecode(trim($matches[1], "\"' "));
        }

        if (preg_match('/filename=\"([^\"]+)\"/i', $contentDisposition, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/filename=([^;]+)/i', $contentDisposition, $matches) === 1) {
            return trim($matches[1], "\"' ");
        }

        return null;
    }
}
