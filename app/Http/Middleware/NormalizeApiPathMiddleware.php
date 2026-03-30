<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeApiPathMiddleware
{
    /**
     * Collapse duplicate slashes in request path and redirect to canonical URL.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uri = (string) $request->getRequestUri();
        $parts = parse_url($uri);

        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';

        // Keep a single leading slash and collapse any repeated slashes in the path.
        $normalizedPath = preg_replace('#/+#', '/', $path);
        if ($normalizedPath === null || $normalizedPath === '') {
            $normalizedPath = '/';
        }

        $normalizedPath = '/' . ltrim($normalizedPath, '/');
        $normalizedUri = $normalizedPath . $query;

        if ($normalizedUri !== $uri) {
            $request->server->set('REQUEST_URI', $normalizedUri);
            $request->server->set('PATH_INFO', $normalizedPath);
            $request->server->set('UNENCODED_URL', $normalizedUri);
        }

        return $next($request);
    }
}