<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\PreventStaleFormCache;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [PreventStaleFormCache::class, SecurityHeaders::class]);
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->trustHosts(
            fn (): array => ['^'.preg_quote((string) parse_url((string) config('app.url'), PHP_URL_HOST), '/').'$'],
            subdomains: false,
        );
        $middleware->validateCsrfTokens(except: [
            'webhooks/nomod',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            $request = request();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your secure session expired. Refresh and try again.'], 419);
            }

            if ($request->is('logout')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'Signed out. You can now use another account.');
            }

            if ($request->is('login')) {
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withInput($request->only('email'))
                    ->with('status', 'Your secure session was refreshed. Please sign in again.');
            }

            return redirect()->route('login')
                ->with('status', 'Your session expired safely. Please sign in again to continue.');
        });
    })->create();
