<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->jsonError('Unauthenticated', 401);
        }

        if (!$request->user()->is_admin) {
            return response()->jsonError('Unauthorized. Admin access required.', 403);
        }

        return $next($request);
    }
}
