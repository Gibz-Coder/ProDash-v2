<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Home');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('lot-request', function () {
    return Inertia::render('dashboards/main/lot-request');
})->middleware(['auth', 'verified'])->name('lot_request');

Route::get('dashboard-2', function () {
    return Inertia::render('dashboards/main/dashboard-2');
})->middleware(['auth', 'verified'])->name('dashboard_2');

Route::get('process-wip', [App\Http\Controllers\ProcessWipController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('process_wip');

Route::get('process-wip/export', [App\Http\Controllers\ProcessWipController::class, 'export'])
    ->middleware(['auth', 'verified'])
    ->name('process_wip.export');

Route::get('api/process-wip/available-lots', [App\Http\Controllers\ProcessWipController::class, 'getAvailableLots'])
    ->middleware(['auth', 'verified'])
    ->name('process_wip.available_lots');

Route::get('mc-allocation', [App\Http\Controllers\McAllocationController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('mc_allocation');

Route::get('mc-allocation/export', [App\Http\Controllers\McAllocationController::class, 'export'])
    ->middleware(['auth', 'verified'])
    ->name('mc_allocation.export');

Route::put('mc-allocation/{id}', [App\Http\Controllers\McAllocationController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('mc_allocation.update');

Route::get('mc-allocation/reference-data', [App\Http\Controllers\McAllocationController::class, 'getReferenceData'])
    ->middleware(['auth', 'verified'])
    ->name('mc_allocation.reference_data');

Route::delete('mc-allocation/{id}', [App\Http\Controllers\McAllocationController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('mc_allocation.destroy');

Route::get('dashboard-3', function () {
    return Inertia::render('dashboards/main/dashboard-3');
})->middleware(['auth', 'verified'])->name('dashboard_3');

Route::get('endline', function () {
    return Inertia::render('dashboards/main/endline');
})->middleware(['auth', 'verified'])->name('endline');

Route::get('mems-dashboard', function () {
    return Inertia::render('dashboards/main/mems-dashboard');
})->middleware(['auth', 'verified'])->name('mems_dashboard');

Route::get('escalation', function () {
    return Inertia::render('dashboards/main/escalation');
})->middleware(['auth', 'verified'])->name('escalation');

Route::get('dashboard-7', function () {
    return Inertia::render('dashboards/main/dashboard-7');
})->middleware(['auth', 'verified'])->name('dashboard_7');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('data-entry', function () {
        // Block only 'user' role
        if (strtolower(auth()->user()->role) === 'user') {
            abort(403, 'Unauthorized access.');
        }
        return Inertia::render('dashboards/main/data-entry');
    })->name('data_entry');
});

Route::get('endtime', [App\Http\Controllers\EndtimeController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('endtime');

Route::get('keep-alive', [App\Http\Controllers\EndtimeController::class, 'keepAlive'])
    ->middleware(['auth', 'verified'])
    ->name('keep_alive');

Route::get('endtime/equipment-list', [App\Http\Controllers\EndtimeController::class, 'getEquipmentList'])
    ->middleware(['auth', 'verified'])
    ->name('endtime.equipment_list');

Route::get('endtime/remaining-lots-list', [App\Http\Controllers\EndtimeController::class, 'getRemainingLotsList'])
    ->middleware(['auth', 'verified'])
    ->name('endtime.remaining_lots_list');

Route::get('endtime/export', [App\Http\Controllers\EndtimeController::class, 'exportData'])
    ->middleware(['auth', 'verified'])
    ->name('endtime.export');

Route::get('endtime-add', [App\Http\Controllers\EndtimeController::class, 'lotEntry'])
    ->middleware(['auth', 'verified', 'permission:Endtime Manage'])
    ->name('endtime_add');

Route::post('endtime/store', [App\Http\Controllers\EndtimeController::class, 'store'])
    ->middleware(['auth', 'verified', 'permission:Endtime Manage'])
    ->name('endtime.store');

Route::put('endtime/{id}', [App\Http\Controllers\EndtimeController::class, 'update'])
    ->middleware(['auth', 'verified', 'permission:Endtime Manage'])
    ->name('endtime.update');

Route::delete('endtime/{id}', [App\Http\Controllers\EndtimeController::class, 'destroy'])
    ->middleware(['auth', 'verified', 'permission:Endtime Delete'])
    ->name('endtime.destroy');

Route::get('api/updatewip/lookup', [App\Http\Controllers\EndtimeController::class, 'lookupLot'])
    ->middleware(['auth', 'verified'])
    ->name('updatewip.lookup');

Route::get('api/equipment/search', [App\Http\Controllers\EndtimeController::class, 'searchEquipment'])
    ->middleware(['auth', 'verified'])
    ->name('equipment.search');

Route::get('api/equipment/lookup', [App\Http\Controllers\EndtimeController::class, 'lookupEquipment'])
    ->middleware(['auth', 'verified'])
    ->name('equipment.lookup');

Route::get('api/employee/search', [App\Http\Controllers\EndtimeController::class, 'searchEmployee'])
    ->middleware(['auth', 'verified'])
    ->name('employee.search');

Route::get('api/employee/lookup', [App\Http\Controllers\EndtimeController::class, 'lookupEmployee'])
    ->middleware(['auth', 'verified'])
    ->name('employee.lookup');

Route::get('api/endtime/lookup-ongoing', [App\Http\Controllers\EndtimeController::class, 'lookupOngoingLot'])
    ->middleware(['auth', 'verified'])
    ->name('endtime.lookup_ongoing');

// MEMS Dashboard - Machine Utilization Trend API
Route::get('api/mems/utilization/trend', [App\Http\Controllers\MachineUtilizationTrendController::class, 'getTrendData'])
    ->middleware(['auth', 'verified'])
    ->name('mems.utilization.trend');

Route::get('api/mems/utilization/latest', [App\Http\Controllers\MachineUtilizationTrendController::class, 'getLatestUtilization'])
    ->middleware(['auth', 'verified'])
    ->name('mems.utilization.latest');

Route::get('api/mems/equipment/list', [App\Http\Controllers\MachineUtilizationTrendController::class, 'getEquipmentList'])
    ->middleware(['auth', 'verified'])
    ->name('mems.equipment.list');

// Manual snapshot capture endpoint
Route::post('api/mems/snapshot/capture', [App\Http\Controllers\MachineUtilizationTrendController::class, 'captureSnapshot'])
    ->middleware(['auth', 'verified'])
    ->name('mems.snapshot.capture');

// Clear snapshots endpoint
Route::delete('api/mems/snapshot/clear', [App\Http\Controllers\MachineUtilizationTrendController::class, 'clearSnapshots'])
    ->middleware(['auth', 'verified'])
    ->name('mems.snapshot.clear');

// Get snapshot statistics
Route::get('api/mems/snapshot/stats', [App\Http\Controllers\MachineUtilizationTrendController::class, 'getSnapshotStats'])
    ->middleware(['auth', 'verified'])
    ->name('mems.snapshot.stats');

// Get filter options
Route::get('api/mems/filters/options', [App\Http\Controllers\MachineUtilizationTrendController::class, 'getFilterOptions'])
    ->middleware(['auth', 'verified'])
    ->name('mems.filters.options');

// Real-time Equipment Status API (direct from equipment table)
Route::get('api/equipment/status/utilization', [App\Http\Controllers\EquipmentStatusController::class, 'getMachineUtilizationStatus'])
    ->middleware(['auth', 'verified'])
    ->name('equipment.status.utilization');

Route::get('api/equipment/status/line', [App\Http\Controllers\EquipmentStatusController::class, 'getLineUtilization'])
    ->middleware(['auth', 'verified'])
    ->name('equipment.status.line');

Route::get('api/equipment/status/machine-type', [App\Http\Controllers\EquipmentStatusController::class, 'getMachineTypeStatus'])
    ->middleware(['auth', 'verified'])
    ->name('equipment.status.machine-type');

Route::get('api/equipment/status/machine-size', [App\Http\Controllers\EquipmentStatusController::class, 'getMachineSizeStatus'])
    ->middleware(['auth', 'verified'])
    ->name('equipment.status.machine-size');

Route::get('api/equipment/status/raw', [App\Http\Controllers\EquipmentStatusController::class, 'getRawEquipmentData'])
    ->middleware(['auth', 'verified'])
    ->name('equipment.status.raw');

Route::get('api/equipment/raw-machine', [App\Http\Controllers\EquipmentStatusController::class, 'getRawMachineData'])
    ->middleware(['auth', 'verified'])
    ->name('equipment.raw.machine');

Route::get('api/equipment/details/{equipmentNo}', [App\Http\Controllers\EquipmentStatusController::class, 'getEquipmentDetails'])
    ->middleware(['auth', 'verified'])
    ->name('equipment.details');

Route::put('api/equipment/{equipmentNo}/remarks', [App\Http\Controllers\EquipmentStatusController::class, 'updateRemarks'])
    ->middleware(['auth', 'verified'])
    ->name('equipment.remarks.update');

// Get line utilization for gauge
Route::get('api/mems/utilization/line', [App\Http\Controllers\MachineUtilizationTrendController::class, 'getLineUtilization'])
    ->middleware(['auth', 'verified'])
    ->name('mems.utilization.line');

// Get machine type status
Route::get('api/mems/utilization/machine-type', [App\Http\Controllers\MachineUtilizationTrendController::class, 'getMachineTypeStatus'])
    ->middleware(['auth', 'verified'])
    ->name('mems.utilization.machine-type');

// Get endtime remaining by class (real-time from endtime table)
Route::get('api/mems/endtime/remaining', [App\Http\Controllers\MemsDashboardController::class, 'getEndtimeRemaining'])
    ->middleware(['auth', 'verified'])
    ->name('mems.endtime.remaining');

Route::get('api/mems/endtime/raw-lots', [App\Http\Controllers\MemsDashboardController::class, 'getRawLotsData'])
    ->middleware(['auth', 'verified'])
    ->name('mems.endtime.raw-lots');

// Get available lots for assignment (from updatewip table)
Route::get('api/mems/available-lots', [App\Http\Controllers\MemsDashboardController::class, 'getAvailableLotsForAssignment'])
    ->middleware(['auth', 'verified'])
    ->name('mems.available-lots');

// Get filter options for lot assignment
Route::get('api/mems/filter-options', [App\Http\Controllers\MemsDashboardController::class, 'getFilterOptions'])
    ->middleware(['auth', 'verified'])
    ->name('mems.filter-options');

// Get endtime per cutoff
Route::get('api/mems/endtime/per-cutoff', [App\Http\Controllers\MachineUtilizationTrendController::class, 'getEndtimePerCutoff'])
    ->middleware(['auth', 'verified'])
    ->name('mems.endtime.per-cutoff');

// Session keep-alive endpoint
Route::post('api/session/keep-alive', function () {
    // Simply touching the session will refresh its lifetime
    session()->put('last_activity', now());
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
})
    ->middleware(['auth', 'verified'])
    ->name('session.keep-alive');

// Health check endpoint for scheduler monitoring
Route::get('api/health/scheduler', function () {
    $lastSnapshot = App\Models\EquipmentSnapshot::latest('snapshot_at')->first();
    
    if (!$lastSnapshot) {
        return response()->json([
            'status' => 'error',
            'message' => 'No snapshots found. Scheduler may not be running.',
        ], 500);
    }
    
    $minutesAgo = now()->diffInMinutes($lastSnapshot->snapshot_at);
    
    if ($minutesAgo > 35) { // 30 min interval + 5 min grace period
        return response()->json([
            'status' => 'warning',
            'message' => "Last snapshot was {$minutesAgo} minutes ago. Scheduler may be delayed.",
            'last_snapshot' => $lastSnapshot->snapshot_at->toIso8601String(),
        ], 200);
    }
    
    return response()->json([
        'status' => 'ok',
        'message' => 'Scheduler is running normally',
        'last_snapshot' => $lastSnapshot->snapshot_at->toIso8601String(),
        'minutes_ago' => $minutesAgo,
    ]);
})->name('health.scheduler');

Route::post('endtime/{id}/submit', [App\Http\Controllers\EndtimeController::class, 'submitLot'])
    ->middleware(['auth', 'verified', 'permission:Endtime Manage'])
    ->name('endtime.submit');

Route::get('endtime-submit', function () {
    return Inertia::render('dashboards/subs/endtime-submit');
})->middleware(['auth', 'verified', 'permission:Endtime Manage'])->name('endtime_submit');

// UpdateWip routes
Route::post('updatewip', [App\Http\Controllers\UpdateWipController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('updatewip.store');

Route::get('updatewip/download-template', [App\Http\Controllers\UpdateWipController::class, 'downloadTemplate'])
    ->middleware(['auth', 'verified'])
    ->name('updatewip.download_template');

// WIP Trend routes
Route::post('wip-trend/update', [App\Http\Controllers\WipTrendController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('wip_trend.update');

Route::get('wip-trend/download-template', [App\Http\Controllers\WipTrendController::class, 'downloadTemplate'])
    ->middleware(['auth', 'verified'])
    ->name('wip_trend.download_template');

// Process Result routes
Route::post('process-result/update', [App\Http\Controllers\ProcessResultController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('process_result.update');

Route::get('process-result/download-template', [App\Http\Controllers\ProcessResultController::class, 'downloadTemplate'])
    ->middleware(['auth', 'verified'])
    ->name('process_result.download_template');

// Process Trackout routes
Route::post('process-trackout/update', [App\Http\Controllers\ProcessTrackoutController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('process_trackout.update');

Route::get('process-trackout/download-template', [App\Http\Controllers\ProcessTrackoutController::class, 'downloadTemplate'])
    ->middleware(['auth', 'verified'])
    ->name('process_trackout.download_template');

// Monthly Plan routes
Route::post('monthly-plan/update', [App\Http\Controllers\MonthlyPlanController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('monthly_plan.update');

Route::get('monthly-plan/download-template', [App\Http\Controllers\MonthlyPlanController::class, 'downloadTemplate'])
    ->middleware(['auth', 'verified'])
    ->name('monthly_plan.download_template');

Route::get('endtime-ranking', [App\Http\Controllers\EndtimeRankingController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('endtime_ranking');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';

// Error page testing routes (remove in production)
if (app()->environment('local')) {
    Route::prefix('test-errors')->group(function () {
        Route::get('/401', fn() => Inertia::render('error/401', ['status' => 401]))->name('test.error.401');
        Route::get('/403', fn() => Inertia::render('error/403', ['status' => 403]))->name('test.error.403');
        Route::get('/404', fn() => Inertia::render('error/404', ['status' => 404]))->name('test.error.404');
        Route::get('/419', fn() => Inertia::render('error/419', ['status' => 419]))->name('test.error.419');
        Route::get('/429', fn() => Inertia::render('error/429', ['status' => 429]))->name('test.error.429');
        Route::get('/500', fn() => Inertia::render('error/500', ['status' => 500]))->name('test.error.500');
        Route::get('/503', fn() => Inertia::render('error/503', ['status' => 503]))->name('test.error.503');
        Route::get('/maintenance', fn() => Inertia::render('error/Maintenance', [
            'message' => 'We are performing scheduled maintenance.',
            'retryAfter' => 30
        ]))->name('test.error.maintenance');
    });
}
