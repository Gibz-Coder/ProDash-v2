<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions  Required permissions (user needs at least one)
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Refresh user from database to get the latest role
        $user = $request->user()->fresh();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Get user's role permissions
        // Convert database role format (SUPER USER) to slug format (super-user)
        $roleSlug = strtolower(str_replace(' ', '-', $user->role));
        $roleDetails = \App\Models\Role::where('slug', $roleSlug)->first();
        $userPermissions = $roleDetails ? ($roleDetails->permissions ?? []) : [];

        // Check if user has any of the required permissions
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            abort(403, 'Unauthorized access. Required permission: ' . implode(' or ', $permissions));
        }

        return $next($request);
    }
}
