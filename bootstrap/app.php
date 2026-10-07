<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'api/telegram/webhook/*',
            'go/*',
            'cta/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Your session or security token was refreshed. Please try again.',
                    'csrf_token' => csrf_token(),
                ], 419);
            }
            return redirect()->back()
                ->withInput($request->except('_token', '_method', 'password', 'password_confirmation'))
                ->with('error', 'Your session was refreshed. Please click Save Changes again.');
        });
    })->create();

