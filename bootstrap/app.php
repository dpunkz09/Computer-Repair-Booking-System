<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\VerifyRole::class,
            'full.admin' => \App\Http\Middleware\EnsureFullAdmin::class,
            'verified.when.required' => \App\Http\Middleware\EnsureEmailVerifiedWhenRequired::class,
            'admin.two.factor' => \App\Http\Middleware\EnsureAdminTwoFactor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->user()?->isDemoAdmin() && ! $request->expectsJson()) {
                return redirect()
                    ->back(fallback: route('dashboard'))
                    ->with('error', 'This action is not available in demo mode.');
            }

            return null;
        });
    })->create();
