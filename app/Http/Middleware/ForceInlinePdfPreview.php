<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceInlinePdfPreview
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->boolean('preview_pdf')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        if (stripos($contentType, 'application/pdf') === false) {
            return $response;
        }

        $disposition = (string) $response->headers->get('Content-Disposition', '');

        if ($disposition === '' || stripos($disposition, 'attachment;') !== 0) {
            return $response;
        }

        $response->headers->set(
            'Content-Disposition',
            preg_replace('/^attachment;/i', 'inline;', $disposition, 1) ?? $disposition
        );

        return $response;
    }
}
