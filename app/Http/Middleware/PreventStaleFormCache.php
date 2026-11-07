<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventStaleFormCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
