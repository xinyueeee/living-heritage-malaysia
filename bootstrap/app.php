<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway terminates HTTPS at its edge and forwards requests to this
        // container over plain HTTP, adding X-Forwarded-Proto/Host headers.
        // Without trusting the proxy, Laravel believes every request is
        // HTTP and generates insecure asset/URL links, which browsers then
        // block as mixed content on the HTTPS page.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
