<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain as Father;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyBySubDomain extends Father
{
    public function handle($request, Closure $next): Response
    {
        $subdomain = $this->makeSubdomain($request->getHost());

        if (is_object($subdomain) && $subdomain instanceof Exception) {
            return $next($request);
        }

        return $this->initializeTenancy(
            $request,
            $next,
            $subdomain
        );
    }
}
