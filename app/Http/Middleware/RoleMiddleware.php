<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        $roles = $request->attributes->get('staff_roles', []);
        if (!in_array($role, $roles)) {
            return response()->json(['message' => 'Forbidden - missing role'], 403);
        }
        return $next($request);
    }
}
