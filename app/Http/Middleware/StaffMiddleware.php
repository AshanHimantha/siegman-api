<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $staff = Staff::where('user_id', $user->id)->first();
        if (!$staff) {
            return response()->json(['message' => 'Forbidden - staff only'], 403);
        }

        $request->attributes->set('staff_roles', $staff->roles ?? []);

        return $next($request);
    }
}
