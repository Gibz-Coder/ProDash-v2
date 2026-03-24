<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// RefreshDatabase is applied globally to Feature tests via Pest.php

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create a Role that grants "Manage Endline" permission and return a User
 * whose role slug matches it, so the PermissionMiddleware passes.
 */
function userWithEndlinePermission(): User
{
    $role = Role::firstOrCreate(
        ['slug' => 'admin'],
        [
            'name'        => 'Admin',
            'description' => 'Administrator',
            'permissions' => ['Manage Endline'],
        ]
    );

    return User::factory()->withoutTwoFactor()->create(['role' => $role->slug]);
}

/**
 * Insert a minimal row into endline_delay and return its id.
 *
 * @param array<string, mixed> $overrides
 */
function insertEndlineRow(array $overrides = []): int
{
    return DB::table('endline_delay')->insertGetId(array_merge([
        'lot_id'     => 'LOT-' . uniqid(),
        'model'      => 'MODEL-X',
        'lot_qty'    => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

// ===========================================================================
// GET /api/endline-delay/qc-monitor  (qcAnalysisIndex)
// ===========================================================================

test('qcAnalysisIndex returns only QC Analysis rows', function (): void {
    $user = userWithEndlinePermission();

    // Matching rows: defect_class = 'QC Analysis', any result state
    $pendingId   = insertEndlineRow(['defect_class' => 'QC Analysis', 'qc_ana_result' => null]);
    $completedId = insertEndlineRow(['defect_class' => 'QC Analysis', 'qc_ana_result' => 'Proceed']);

    // Non-matching: wrong defect_class
    insertEndlineRow(['defect_class' => "Tech'l Verification", 'qc_ana_result' => null]);

    $response = $this->actingAs($user)->getJson('/api/endline-delay/qc-monitor');

    $response->assertOk()
             ->assertJsonPath('success', true);

    $data = $response->json('data');
    $ids  = collect($data)->pluck('id')->all();

    expect($ids)->toContain($pendingId)
                ->toContain($completedId)
                ->toHaveCount(2);
});

test('qcAnalysisIndex returns rows ordered by created_at ascending', function (): void {
    $user = userWithEndlinePermission();

    $firstId  = insertEndlineRow(['defect_class' => 'QC Analysis', 'created_at' => now()->subMinutes(10)]);
    $secondId = insertEndlineRow(['defect_class' => 'QC Analysis', 'created_at' => now()->subMinutes(5)]);
    $thirdId  = insertEndlineRow(['defect_class' => 'QC Analysis', 'created_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/endline-delay/qc-monitor');

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toBe([$firstId, $secondId, $thirdId]);
});

// ===========================================================================
// GET /api/endline-delay/vi-monitor  (viTechnicalIndex)
// ===========================================================================

test('viTechnicalIndex returns only Tech\'l Verification rows', function (): void {
    $user = userWithEndlinePermission();

    $pendingId   = insertEndlineRow(['defect_class' => "Tech'l Verification", 'vi_techl_result' => null]);
    $completedId = insertEndlineRow(['defect_class' => "Tech'l Verification", 'vi_techl_result' => 'Proceed']);

    // Non-matching: wrong defect_class
    insertEndlineRow(['defect_class' => 'QC Analysis', 'vi_techl_result' => null]);

    $response = $this->actingAs($user)->getJson('/api/endline-delay/vi-monitor');

    $response->assertOk()
             ->assertJsonPath('success', true);

    $data = $response->json('data');
    $ids  = collect($data)->pluck('id')->all();

    expect($ids)->toContain($pendingId)
                ->toContain($completedId)
                ->toHaveCount(2);
});

test('viTechnicalIndex returns rows ordered by created_at ascending', function (): void {
    $user = userWithEndlinePermission();

    $firstId  = insertEndlineRow(['defect_class' => "Tech'l Verification", 'created_at' => now()->subMinutes(10)]);
    $secondId = insertEndlineRow(['defect_class' => "Tech'l Verification", 'created_at' => now()->subMinutes(5)]);
    $thirdId  = insertEndlineRow(['defect_class' => "Tech'l Verification", 'created_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/endline-delay/vi-monitor');

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toBe([$firstId, $secondId, $thirdId]);
});

// ===========================================================================
// POST /api/endline-delay/{id}/start-qc  (startQcAnalysis)
// ===========================================================================

test('startQcAnalysis stamps qc_ana_start and records updated_by', function (): void {
    $user = userWithEndlinePermission();
    $id   = insertEndlineRow(['qc_ana_start' => null]);

    $response = $this->actingAs($user)->postJson("/api/endline-delay/{$id}/start-qc");

    $response->assertOk()
             ->assertJsonPath('success', true);

    $row = DB::table('endline_delay')->find($id);

    expect($row->qc_ana_start)->not->toBeNull()
        ->and($row->updated_by)->toBe($user->emp_name);
});

test('startQcAnalysis returns 422 when qc_ana_start is already set (double-start)', function (): void {
    $user = userWithEndlinePermission();
    $id   = insertEndlineRow(['qc_ana_start' => now()->subMinutes(5)]);

    $response = $this->actingAs($user)->postJson("/api/endline-delay/{$id}/start-qc");

    $response->assertStatus(422)
             ->assertJsonPath('success', false);

    // Confirm the original start timestamp was not changed
    $row = DB::table('endline_delay')->find($id);
    expect($row->qc_ana_start)->not->toBeNull();
});

test('startQcAnalysis returns 404 for a non-existent record', function (): void {
    $user = userWithEndlinePermission();

    $this->actingAs($user)
         ->postJson('/api/endline-delay/999999/start-qc')
         ->assertNotFound();
});

// ===========================================================================
// POST /api/endline-delay/{id}/start-vi  (startViTechnical)
// ===========================================================================

test('startViTechnical stamps vi_techl_start and records updated_by', function (): void {
    $user = userWithEndlinePermission();
    $id   = insertEndlineRow(['vi_techl_start' => null]);

    $response = $this->actingAs($user)->postJson("/api/endline-delay/{$id}/start-vi");

    $response->assertOk()
             ->assertJsonPath('success', true);

    $row = DB::table('endline_delay')->find($id);

    expect($row->vi_techl_start)->not->toBeNull()
        ->and($row->updated_by)->toBe($user->emp_name);
});

test('startViTechnical returns 422 when vi_techl_start is already set (double-start)', function (): void {
    $user = userWithEndlinePermission();
    $id   = insertEndlineRow(['vi_techl_start' => now()->subMinutes(5)]);

    $response = $this->actingAs($user)->postJson("/api/endline-delay/{$id}/start-vi");

    $response->assertStatus(422)
             ->assertJsonPath('success', false);

    $row = DB::table('endline_delay')->find($id);
    expect($row->vi_techl_start)->not->toBeNull();
});

test('startViTechnical returns 404 for a non-existent record', function (): void {
    $user = userWithEndlinePermission();

    $this->actingAs($user)
         ->postJson('/api/endline-delay/999999/start-vi')
         ->assertNotFound();
});

// ===========================================================================
// Requirement 6.7 — unauthenticated requests return 401
// ===========================================================================

test('unauthenticated request to qc-monitor returns 401', function (): void {
    $this->getJson('/api/endline-delay/qc-monitor')->assertUnauthorized();
});

test('unauthenticated request to vi-monitor returns 401', function (): void {
    $this->getJson('/api/endline-delay/vi-monitor')->assertUnauthorized();
});

test('unauthenticated request to start-qc returns 401', function (): void {
    $id = insertEndlineRow();
    $this->postJson("/api/endline-delay/{$id}/start-qc")->assertUnauthorized();
});

test('unauthenticated request to start-vi returns 401', function (): void {
    $id = insertEndlineRow();
    $this->postJson("/api/endline-delay/{$id}/start-vi")->assertUnauthorized();
});
