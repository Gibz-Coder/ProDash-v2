<?php

use App\Http\Middleware\CheckForMaintenanceMode;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            CheckForMaintenanceMode::class,  // Add maintenance mode check
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle HTTP exceptions with custom error pages
        $exceptions->render(function (\Throwable $e, $request) {
            // Handle 503 Service Unavailable (Maintenance Mode)
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException) {
                return \Inertia\Inertia::render('error/Maintenance', [
                    'status' => 503,
                    'message' => $e->getMessage() ?: 'Our application is currently undergoing scheduled maintenance.',
                    'retryAfter' => 30,
                ])->toResponse($request)->setStatusCode(503);
            }

            // Handle other HTTP exceptions
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $statusCode = $e->getStatusCode();
                
                if (in_array($statusCode, [401, 403, 404, 419, 429, 500, 503])) {
                    $errorPages = [
                        401 => 'error/401',
                        403 => 'error/403',
                        404 => 'error/404',
                        419 => 'error/419',
                        429 => 'error/429',
                        500 => 'error/500',
                        503 => 'error/503',
                    ];

                    $page = $errorPages[$statusCode] ?? 'error/500';

                    return \Inertia\Inertia::render($page, [
                        'status' => $statusCode,
                    ])->toResponse($request)->setStatusCode($statusCode);
                }
            }

            return null;
        });
    })->create();
