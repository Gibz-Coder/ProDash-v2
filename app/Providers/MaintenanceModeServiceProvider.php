<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as BaseCheckForMaintenanceMode;
use App\Enums\UserRole;

class MaintenanceModeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Override the maintenance mode check
        $this->app->singleton(
            \Illuminate\Contracts\Foundation\MaintenanceMode::class,
            function ($app) {
                return new class($app) implements \Illuminate\Contracts\Foundation\MaintenanceMode {
                    protected $app;

                    public function __construct($app)
                    {
                        $this->app = $app;
                    }

                    public function activate(array $payload): void
                    {
                        file_put_contents(
                            $this->app->storagePath().'/framework/down',
                            json_encode($payload, JSON_PRETTY_PRINT)
                        );
                    }

                    public function deactivate(): void
                    {
                        if (file_exists($path = $this->app->storagePath().'/framework/down')) {
                            unlink($path);
                        }
                    }

                    public function active(): bool
                    {
                        // Check if maintenance file exists
                        if (!file_exists($this->app->storagePath().'/framework/down')) {
                            return false;
                        }

                        try {
                            // Check if we have a request with session
                            if ($this->app->bound('request')) {
                                $request = $this->app->make('request');
                                
                                \Log::info('Maintenance check', [
                                    'has_session' => $request->hasSession(),
                                    'session_data' => $request->hasSession() ? $request->session()->all() : null,
                                ]);
                                
                                // Check if session is available and has user data
                                if ($request->hasSession() && $request->session()->has('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d')) {
                                    // Get user ID from session
                                    $userId = $request->session()->get('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d');
                                    
                                    \Log::info('Found user ID in session', ['user_id' => $userId]);
                                    
                                    if ($userId) {
                                        // Load user from database
                                        $user = \App\Models\User::find($userId);
                                        
                                        \Log::info('Loaded user', [
                                            'user' => $user ? $user->toArray() : null,
                                            'role' => $user ? $user->role : null,
                                            'is_admin' => $user && $user->role === 'admin',
                                        ]);
                                        
                                        if ($user && $user->role === 'admin') {
                                            \Log::info('Admin user - bypassing maintenance');
                                            return false; // Not in maintenance for admin
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::error('Error in maintenance check', ['error' => $e->getMessage()]);
                        }

                        return true;
                    }

                    public function data(): array
                    {
                        if (!$this->active()) {
                            return [];
                        }

                        return json_decode(
                            file_get_contents($this->app->storagePath().'/framework/down'),
                            true
                        );
                    }
                };
            }
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
