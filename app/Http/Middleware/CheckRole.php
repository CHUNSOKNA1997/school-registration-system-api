<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role  'admin' or 'staff'
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()) {
            return response()->jsonError('Unauthenticated', 401);
        }

        // Check if user is active
        if (!$request->user()->is_active) {
            return response()->jsonError('Account is inactive', 403);
        }

        // Check role
        if ($role === 'admin' && !$request->user()->is_admin) {
            return response()->jsonError('Unauthorized. Admin access required.', 403);
        }

        if ($role === 'staff' && $request->user()->is_admin) {
            return response()->jsonError('Unauthorized. Staff access only.', 403);
        }

        return $next($request);
    }
}
