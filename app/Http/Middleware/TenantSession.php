<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('tenant_id')) {
            $request->session()->put('tenant_id', app('tenant')->id);

            return $next($request);
        }

        if ($request->session()->get('tenant_id') != app('tenant')->id) {
            abort(401);
        }

        return $next($request);
    }
}
