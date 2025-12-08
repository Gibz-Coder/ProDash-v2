<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Refresh user from database to get the latest role
        $user = $request->user()->fresh();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Debug logging
        \Log::info('RoleMiddleware Check', [
            'user' => $user->emp_name,
            'user_role' => $user->role,
            'allowed_roles' => $roles,
            'roles_array' => print_r($roles, true),
        ]);

        // Convert roles to lowercase for case-insensitive comparison
        $normalizedRoles = array_map('strtolower', $roles);
        $userRole = strtolower($user->role);
        
        \Log::info('RoleMiddleware Normalized', [
            'user_role_normalized' => $userRole,
            'allowed_roles_normalized' => $normalizedRoles,
            'in_array_result' => in_array($userRole, $normalizedRoles),
        ]);
        
        // Check if user has any of the allowed roles
        if (!in_array($userRole, $normalizedRoles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
