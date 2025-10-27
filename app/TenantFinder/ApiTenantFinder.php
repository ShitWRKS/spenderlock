<?php

namespace App\TenantFinder;

use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;

class ApiTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        // Per le richieste API, cerca il tenant nell'header X-Tenant-ID o X-Tenant-Domain
        if ($request->is('api/*')) {
            // Cerca per ID
            if ($tenantId = $request->header('X-Tenant-ID')) {
                return app(IsTenant::class)::find($tenantId);
            }

            // Cerca per dominio (es. localhost, app.example.com)
            if ($tenantDomain = $request->header('X-Tenant-Domain')) {
                return app(IsTenant::class)::whereDomain($tenantDomain)->first();
            }

            // Fallback: usa il dominio della richiesta (localhost)
            $host = $request->getHost();
            return app(IsTenant::class)::whereDomain($host)->first();
        }

        // Per le richieste web, usa il dominio standard
        $host = $request->getHost();
        return app(IsTenant::class)::whereDomain($host)->first();
    }
}
