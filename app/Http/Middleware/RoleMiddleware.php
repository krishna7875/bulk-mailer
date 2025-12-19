<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // User not logged in
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // No roles passed
        if (empty($roles)) {
            abort(403, 'Role not specified.');
        }

        // Role mismatch
        if (!in_array(Auth::user()->role, $roles)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
