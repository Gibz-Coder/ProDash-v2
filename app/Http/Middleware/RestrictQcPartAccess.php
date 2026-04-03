<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictQcPartAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for unauthenticated users
        if (!$request->user()) {
            return $next($request);
        }

        $user = $request->user();
        $userRole = strtolower(str_replace(' ', '-', $user->role));

        // If user is QC Part, only allow access to endline routes
        if ($userRole === 'qc-part') {
            $allowedRoutes = ['endline', 'endline_details', 'endline_delay', 'endline_trend', 'qc_analysis', 'vi_technical', 'qc_ok', 'qc_defect_class', 'logout', 'settings.profile', 'settings.password', 'settings.two-factor'];
            
            // Check if current route is in allowed routes
            $currentRoute = $request->route()->getName();
            if ($currentRoute && !in_array($currentRoute, $allowedRoutes)) {
                // Redirect to endline page
                return redirect()->route('endline_delay');
            }
        }

        return $next($request);
    }
}
