<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gzips JSON and HTML responses for browsers that accept it (all do). The lists (e.g. every employee for the
 * pickers) are repetitive JSON that shrinks ~90%, so pages load much faster over the office network. Works without
 * Apache's mod_deflate; if that is turned on later, it leaves these already-compressed responses alone.
 */
class CompressResponse
{
    /** Smaller responses aren't worth compressing */
    private const MIN_BYTES = 1024;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->compressible($request, $response)) {
            return $response;
        }

        $compressed = gzencode((string) $response->getContent(), 5);
        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->setVary(array_unique(array_merge($response->getVary(), ['Accept-Encoding'])));

        return $response;
    }

    private function compressible(Request $request, Response $response): bool
    {
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) return false;
        if (!function_exists('gzencode') || $response->headers->has('Content-Encoding')) return false;
        if (!str_contains((string) $request->header('Accept-Encoding'), 'gzip')) return false;

        $type = (string) $response->headers->get('Content-Type');
        if (!str_contains($type, 'json') && !str_contains($type, 'text/html')) return false;

        $content = $response->getContent();

        return is_string($content) && strlen($content) >= self::MIN_BYTES;
    }
}
