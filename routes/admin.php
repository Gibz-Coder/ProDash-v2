<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\SystemSettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes are for admin and manager users only.
| They are protected by authentication and role middleware.
|
*/

// Admin Pages (Inertia)
Route::middleware(['auth', 'verified', 'permission:Settings Manage'])->group(function () {
    Route::get('/admin/system-settings', function () {
        return Inertia::render('Admin/SystemSettings');
    })->name('admin.system-settings');
});

Route::middleware(['auth', 'verified', 'permission:Employees Manage'])->group(function () {
    Route::get('/admin/user-management', function () {
        return Inertia::render('Admin/UserManagement');
    })->name('admin.user-management');
});

Route::middleware(['auth', 'verified', 'permission:Roles Manage'])->group(function () {
    Route::get('/admin/role-management', function () {
        return Inertia::render('Admin/RoleManagement');
    })->name('admin.role-management');
});

// Admin API Routes
Route::prefix('admin/api')->middleware(['auth', 'verified'])->group(function () {
    
    // User Management - requires "Employees Manage" permission
    Route::middleware('permission:Employees Manage')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']);
        Route::get('/users/{user}', [UserManagementController::class, 'show']);
        Route::put('/users/{user}', [UserManagementController::class, 'update']);
        Route::patch('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus']);
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
        // Allow fetching roles for user management dropdown
        Route::get('/roles', [RoleManagementController::class, 'index']);
    });

    // Role Management - requires "Roles Manage" permission
    Route::middleware('permission:Roles Manage')->group(function () {
        Route::get('/roles', [RoleManagementController::class, 'index']);
        Route::post('/roles', [RoleManagementController::class, 'store']);
        Route::get('/roles/{role}', [RoleManagementController::class, 'show']);
        Route::put('/roles/{role}', [RoleManagementController::class, 'update']);
        Route::delete('/roles/{role}', [RoleManagementController::class, 'destroy']);
    });

    // System Settings - requires "Settings Manage" permission
    Route::middleware('permission:Settings Manage')->group(function () {
        Route::get('/settings', [SystemSettingsController::class, 'index']);
        Route::post('/settings/maintenance', [SystemSettingsController::class, 'toggleMaintenance']);
        Route::post('/settings/cache-clear', [SystemSettingsController::class, 'clearCache']);
        Route::post('/settings/optimize', [SystemSettingsController::class, 'optimize']);
        Route::get('/settings/logs', [SystemSettingsController::class, 'getLogs']);
        Route::post('/settings/logs/content', [SystemSettingsController::class, 'getLogContent']);
        Route::post('/settings/logs/clear', [SystemSettingsController::class, 'clearLogs']);
        Route::put('/settings/session', [SystemSettingsController::class, 'updateSessionTimeout']);
        Route::post('/settings/backup', [SystemSettingsController::class, 'createBackup']);
        Route::get('/settings/backups', [SystemSettingsController::class, 'listBackups']);
        Route::get('/settings/backup/download/{filename}', [SystemSettingsController::class, 'downloadBackup']);
        Route::delete('/settings/backup/{filename}', [SystemSettingsController::class, 'deleteBackup']);
    });
});
