<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Multitenancy\Models\Tenant;

class EnsureTenantExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se il tenant esiste per questo dominio
        $tenant = Tenant::where('domain', $request->getHost())->first();
        
        if (!$tenant) {
            // Il tenant non esiste, mostra una pagina 404 personalizzata
            abort(404, 'Tenant non trovato per il dominio: ' . $request->getHost());
        }

        return $next($request);
    }
}