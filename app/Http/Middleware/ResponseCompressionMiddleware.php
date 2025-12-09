<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseCompressionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if client accepts gzip encoding
        if (!$this->clientAcceptsGzip($request)) {
            return $response;
        }

        // Check if response is compressible
        if (!$this->isCompressible($response)) {
            return $response;
        }

        // Compress response content
        $content = $response->getContent();
        $compressedContent = gzencode($content, 6); // Compression level 6 (balance between speed and size)

        if ($compressedContent === false) {
            return $response;
        }

        $response->setContent($compressedContent);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', strlen($compressedContent));
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }

    /**
     * Check if client accepts gzip encoding
     */
    protected function clientAcceptsGzip(Request $request): bool
    {
        $acceptEncoding = $request->header('Accept-Encoding', '');
        return str_contains($acceptEncoding, 'gzip');
    }

    /**
     * Check if response is compressible
     */
    protected function isCompressible(Response $response): bool
    {
        // Don't compress if already compressed
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        // Only compress text-based content types
        $contentType = $response->headers->get('Content-Type', '');
        $compressibleTypes = [
            'text/html',
            'text/css',
            'text/javascript',
            'text/xml',
            'text/plain',
            'application/json',
            'application/javascript',
            'application/xml',
        ];

        foreach ($compressibleTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }
}