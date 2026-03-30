<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceApiJsonMiddleware
{
    /**
     * Ensure API-like requests are negotiated and returned as JSON.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uri = (string) $request->getRequestUri();
        $path = '/' . ltrim((string) $request->path(), '/');
        $normalizedUri = preg_replace('#/+#', '/', $uri) ?: $uri;
        $normalizedPath = preg_replace('#/+#', '/', $path) ?: $path;

        $isApiLike = $request->is('api/*')
            || str_contains($normalizedUri, '/api/')
            || str_starts_with($normalizedUri, '/api')
            || str_contains($normalizedPath, '/api/')
            || str_starts_with($normalizedPath, '/api')
            || str_contains($normalizedUri, '/auth/check')
            || str_contains($normalizedUri, '/auth/line-login');

        if ($isApiLike) {
            $request->headers->set('Accept', 'application/json');
        }

        $response = $next($request);

        if ($isApiLike) {
            $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        }

        return $response;
    }
}
