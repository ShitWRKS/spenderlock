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
    ->withMiddleware(function (Middleware $middleware) {
        /**
         * Middleware per la gestione multi-tenant:
         * - EnsureValidTenantSession: Verifica che il tenant nella sessione sia valido
         * - NeedsTenant: Assicura che un tenant sia identificato per la richiesta
         * 
         * Questi middleware lavorano insieme al TenantFinder per identificare
         * il tenant corrente basato sul dominio della richiesta e switchare
         * automaticamente il database corrispondente.
         */
        $middleware->web([
            \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
            \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
