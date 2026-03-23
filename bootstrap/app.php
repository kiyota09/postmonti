<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Register custom middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'position' => \App\Http\Middleware\CheckPosition::class,
        ]);

        /**
         * ✅ Dynamic Redirection Logic
         * This solves the redirect loop. It checks which user is logged in
         * and sends them to the correct dashboard.
         */
        $middleware->redirectUsersTo(function () {
            // If a B2B Client is logged in, send them to the Partner Portal
            if (Auth::guard('client')->check()) {
                return route('client.dashboard');
            }

            // Otherwise (Employees/Staff), send them to the main ERP Dashboard
            return route('dashboard');
        });

        /**
         * ✅ Guest Redirection Logic
         * If a guest tries to access a protected page, redirect them to the
         * correct login page based on the URL prefix.
         */
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('partner/*') || $request->is('client/*')) {
                return route('client.login');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
