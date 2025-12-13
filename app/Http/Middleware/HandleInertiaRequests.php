<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function onError(Request $request, $response)
    {
        // Handle Inertia error responses
        if ($response->status() >= 400 && $response->status() < 600) {
            $errorPages = [
                401 => 'error/401',
                403 => 'error/403',
                404 => 'error/404',
                419 => 'error/419',
                429 => 'error/429',
                500 => 'error/500',
                503 => 'error/503',
            ];

            if (isset($errorPages[$response->status()])) {
                return \Inertia\Inertia::render($errorPages[$response->status()], [
                    'status' => $response->status(),
                ]);
            }
        }

        return $response;
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        $permissions = [];
        
        if ($user && $user->role) {
            // Convert database role format (SUPER USER) to slug format (super-user)
            $roleSlug = strtolower(str_replace(' ', '-', $user->role));
            $roleDetails = \App\Models\Role::where('slug', $roleSlug)->first();
            $permissions = $roleDetails ? ($roleDetails->permissions ?? []) : [];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'emp_no' => $user->emp_no,
                    'emp_name' => $user->emp_name,
                    'name' => $user->name, // This will use the accessor
                    'role' => $user->role,
                    'position' => $user->position,
                    'title_class' => $user->title_class,
                    'rank' => $user->rank,
                    'avatar' => $user->avatar,
                ] : null,
                'permissions' => $permissions,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
