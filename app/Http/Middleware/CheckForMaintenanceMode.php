<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\UserRole;
use Inertia\Inertia;

class CheckForMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If not in maintenance mode, continue normally
        if (!app()->isDownForMaintenance()) {
            return $next($request);
        }

        // Check for secret bypass token in URL or cookie
        $maintenanceData = app(\Illuminate\Contracts\Foundation\MaintenanceMode::class)->data();
        
        if (!empty($maintenanceData['secret'])) {
            // Check if secret is in URL path
            if ($request->path() === $maintenanceData['secret']) {
                // Set cookie and redirect to home
                return redirect('/')->cookie(
                    'laravel_maintenance',
                    $maintenanceData['secret'],
                    now()->addHours(24)
                );
            }

            // Check if user has valid bypass cookie
            if ($request->cookie('laravel_maintenance') === $maintenanceData['secret']) {
                return $next($request);
            }
        }

        // Allow login and logout routes during maintenance
        if (in_array($request->path(), ['login', 'logout'])) {
            return $next($request);
        }

        // Check if user is authenticated and is an admin
        if (auth()->check()) {
            $user = auth()->user();
            
            // Check if role is admin (handle both string and enum)
            $isAdmin = false;
            
            if (is_string($user->role)) {
                // Role is stored as string
                $isAdmin = $user->role === 'admin';
            } elseif ($user->role instanceof UserRole) {
                // Role is an enum
                $isAdmin = $user->role === UserRole::Admin;
            } elseif (is_object($user->role) && method_exists($user->role, 'value')) {
                // Role is a backed enum with value property
                $isAdmin = $user->role->value === 'admin';
            }
            
            if ($isAdmin) {
                return $next($request);
            }
        }

        // For non-admin users, throw a 503 exception
        // This will be caught by the exception handler in bootstrap/app.php
        throw new \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException(
            60,
            'Our application is currently undergoing scheduled maintenance. We\'ll be back online shortly.'
        );
    }
}
